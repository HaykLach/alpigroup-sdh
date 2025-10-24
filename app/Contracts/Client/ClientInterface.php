<?php

declare(strict_types=1);

namespace App\Contracts\Client;

use Psr\Http\Message\ResponseInterface;

interface ClientInterface
{
    public function request(string $method, string $relativePath, ?array $body = null, array $headers = []): ResponseInterface;
}
