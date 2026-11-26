<?php

namespace Everest\Exceptions\Service\Database;

use Illuminate\Http\Response;
use Everest\Exceptions\DisplayException;

class DatabaseClientFeatureNotEnabledException extends DisplayException
{
    public function __construct()
    {
        parent::__construct('Client database creation is not enabled in this Panel.', null, self::LEVEL_WARNING);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
