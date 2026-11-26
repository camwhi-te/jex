<?php

namespace Everest\Http\Controllers\Api\Application\Settings;

use Carbon\Carbon;
use Everest\Facades\Activity;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;
use Everest\Http\Requests\Api\Application\Settings\GetDebugLogsRequest;

class DebugController extends ApplicationApiController
{
    protected string $logPath;

    /**
     * DebugController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->logPath = storage_path('logs');
    }

    /**
     * Returns a listing of the log files currently on disk, along with a
     * rough count of the errors and warnings contained within each, so
     * admins can quickly spot misconfigurations without leaving the Panel.
     */
    public function index(GetDebugLogsRequest $request): array
    {
        $files = collect(File::exists($this->logPath) ? File::files($this->logPath) : [])
            ->filter(fn ($file) => $file->getExtension() === 'log')
            ->map(fn ($file) => [
                'object' => 'log_file',
                'attributes' => [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'modified_at' => Carbon::createFromTimestamp($file->getMTime())->toIso8601String(),
                    'errors' => $this->countMatching($file->getPathname(), '.ERROR:'),
                    'warnings' => $this->countMatching($file->getPathname(), '.WARNING:'),
                ],
            ])
            ->sortByDesc(fn ($file) => $file['attributes']['modified_at'])
            ->values();

        return [
            'object' => 'list',
            'data' => $files,
        ];
    }

    /**
     * Downloads a single log file so an admin can inspect or share it
     * when diagnosing an issue with the Panel.
     *
     * @throws NotFoundHttpException
     */
    public function download(GetDebugLogsRequest $request, string $file): BinaryFileResponse
    {
        $path = $this->resolvePath($file);

        Activity::event('admin:settings:debug.download')
            ->property('file', basename($path))
            ->description('Downloaded a Panel log file')
            ->log();

        return response()->download($path);
    }

    /**
     * Bundles every log file currently on disk into a single zip archive,
     * making it easier to share the full picture when diagnosing issues.
     *
     * @throws \Throwable
     */
    public function archive(GetDebugLogsRequest $request): BinaryFileResponse
    {
        $files = collect(File::exists($this->logPath) ? File::files($this->logPath) : [])
            ->filter(fn ($file) => $file->getExtension() === 'log');

        // An empty zip archive is never written to disk, so there would be nothing to send.
        if ($files->isEmpty()) {
            throw new NotFoundHttpException('There are no log files to archive.');
        }

        // Write the archive to the file created by tempnam() itself, rather than to a
        // second path, so that no empty temporary file is left behind.
        $zipPath = tempnam(sys_get_temp_dir(), 'panel-logs-');

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($zipPath);

            throw new \RuntimeException('Unable to create a temporary archive of the Panel logs.');
        }

        foreach ($files as $file) {
            $zip->addFile($file->getPathname(), $file->getFilename());
        }

        if (!$zip->close()) {
            @unlink($zipPath);

            throw new \RuntimeException('Unable to write the temporary archive of the Panel logs.');
        }

        Activity::event('admin:settings:debug.archive')
            ->description('Downloaded an archive of the Panel logs')
            ->log();

        return response()
            ->download($zipPath, 'panel-logs-' . Carbon::now()->format('Y-m-d_His') . '.zip')
            ->deleteFileAfterSend(true);
    }

    /**
     * Resolves a log filename to an absolute path on disk, guarding against
     * directory traversal outside of the logs directory.
     *
     * @throws NotFoundHttpException
     */
    private function resolvePath(string $file): string
    {
        $filename = basename($file);
        $path = $this->logPath . DIRECTORY_SEPARATOR . $filename;

        if (!str_ends_with($filename, '.log') || !File::exists($path)) {
            throw new NotFoundHttpException('The requested log file could not be found.');
        }

        return $path;
    }

    /**
     * Counts the number of lines within a log file containing the given
     * needle, streaming the file so large logs don't exhaust memory.
     */
    private function countMatching(string $path, string $needle): int
    {
        $count = 0;
        $handle = fopen($path, 'r');

        while (($line = fgets($handle)) !== false) {
            if (str_contains($line, $needle)) {
                ++$count;
            }
        }

        fclose($handle);

        return $count;
    }
}
