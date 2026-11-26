<?php

namespace Everest\Http\Controllers\Api\Remote\Backups;

use Everest\Models\Backup;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Everest\Facades\Activity;
use Illuminate\Http\Response;
use Everest\Exceptions\DisplayException;
use Everest\Extensions\Backups\BackupManager;
use Everest\Extensions\Filesystem\S3Filesystem;
use Everest\Http\Requests\Api\Remote\ReportBackupCompleteRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;

class BackupStatusController extends ApplicationApiController
{
    /**
     * BackupStatusController constructor.
     */
    public function __construct(private BackupManager $backupManager)
    {
    }

    /**
     * Handles updating the state of a backup.
     *
     * @throws \Throwable
     */
    public function index(ReportBackupCompleteRequest $request, string $backup): Response
    {
        $model = $this->findBackupForNode($request, $backup);

        if ($model->is_successful) {
            throw new BadRequestHttpException('Cannot update the status of a backup that is already marked as completed.');
        }

        $action = $request->boolean('successful') ? 'server:backup.complete' : 'server:backup.fail';
        $log = Activity::event($action)->subject($model, $model->server)->property('name', $model->name);

        $log->transaction(function () use ($model, $request) {
            $successful = $request->boolean('successful');

            $model->fill([
                'is_successful' => $successful,
                // Change the lock state to unlocked if this was a failed backup so that it can be
                // deleted easily. Also does not make sense to have a locked backup on the system
                // that is failed.
                'is_locked' => $successful ? $model->is_locked : false,
                'checksum' => $successful ? ($request->input('checksum_type') . ':' . $request->input('checksum')) : null,
                'bytes' => $successful ? $request->input('size') : 0,
                'completed_at' => CarbonImmutable::now(),
            ])->save();

            // If the backup was stored on S3, make sure we mark it as being completed in S3
            // correctly. Use the backup's own disk rather than the current default adapter.
            if ($model->disk === Backup::ADAPTER_AWS_S3) {
                $adapter = $this->backupManager->adapter(Backup::ADAPTER_AWS_S3);
                if ($adapter instanceof S3Filesystem) {
                    $this->completeMultipartUpload($model, $adapter, $successful, $request->input('parts'));
                }
            }
        });

        return $this->returnNoContent();
    }

    /**
     * Handles toggling the restoration status of a server. The server status field should be
     * set back to null, even if the restoration failed. This is not an unsolvable state for
     * the server, and the user can keep trying to restore, or just use the reinstall button.
     *
     * The only thing the successful field does is update the entry value for the audit logs
     * table tracking for this restoration.
     *
     * @throws \Throwable
     */
    public function restore(Request $request, string $backup): Response
    {
        $model = $this->findBackupForNode($request, $backup);

        $model->server->update(['status' => null]);

        Activity::event($request->boolean('successful') ? 'server:backup.restore-complete' : 'server:backup.restore-failed')
            ->subject($model, $model->server)
            ->property('name', $model->name)
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Returns the backup with the given UUID, provided its server is on the node making the
     * request. Backups on other nodes are reported as not found.
     */
    private function findBackupForNode(Request $request, string $uuid): Backup
    {
        /** @var Backup $model */
        $model = Backup::query()->where('uuid', $uuid)->firstOrFail();

        if ($model->server->node_id !== $request->attributes->get('node')->id) {
            throw new NotFoundHttpException();
        }

        return $model;
    }

    /**
     * Marks a multipart upload in a given S3-compatible instance as failed or successful for
     * the given backup.
     *
     * @throws \Exception
     * @throws DisplayException
     */
    protected function completeMultipartUpload(Backup $backup, S3Filesystem $adapter, bool $successful, ?array $parts): void
    {
        // This should never really happen, but if it does don't let us fall victim to Amazon's
        // wildly fun error messaging. Just stop the process right here.
        if (empty($backup->upload_id)) {
            // A failed backup doesn't need to error here, this can happen if the backup encounters
            // an error before we even start the upload. AWS gives you tooling to clear these failed
            // multipart uploads as needed too.
            if (!$successful) {
                return;
            }

            throw new DisplayException('Cannot complete backup request: no upload_id present on model.');
        }

        $params = [
            'Bucket' => $adapter->getBucket(),
            'Key' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid),
            'UploadId' => $backup->upload_id,
        ];

        $client = $adapter->getClient();
        if (!$successful) {
            $client->execute($client->getCommand('AbortMultipartUpload', $params));

            return;
        }

        // Otherwise send a CompleteMultipartUpload request.
        $params['MultipartUpload'] = [
            'Parts' => [],
        ];

        if (is_null($parts)) {
            $params['MultipartUpload']['Parts'] = $client->execute($client->getCommand('ListParts', $params))['Parts'];
        } else {
            foreach ($parts as $part) {
                $params['MultipartUpload']['Parts'][] = [
                    'ETag' => $part['etag'],
                    'PartNumber' => $part['part_number'],
                ];
            }
        }

        $client->execute($client->getCommand('CompleteMultipartUpload', $params));
    }
}
