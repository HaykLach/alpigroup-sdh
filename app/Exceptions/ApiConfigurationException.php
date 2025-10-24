<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiConfigurationException extends HttpException
{
    private string $configName;

    public function __construct(string $configName)
    {
        $this->configName = $configName;
        parent::__construct(Response::HTTP_INTERNAL_SERVER_ERROR, "Configuration for `{$configName}` shouldn't be empty.");
    }

    public function getErrorCode(): string
    {
        return strtoupper($this->configName).'_NOT_CONFIGURED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
