<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class CustomerImportService
{
    public function ensureDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        if (@mkdir($dir, 0755, true) === false && ! is_dir($dir)) {
            throw new RuntimeException(sprintf('Unable to create directory: %s', $dir));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function decodeJsonFile(string $path): array
    {
        if (! is_readable($path)) {
            throw new RuntimeException(sprintf('JSON file is not readable: %s', $path));
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read JSON file: %s', $path));
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException(sprintf('Invalid JSON in file %s: %s', $path, $exception->getMessage()), 0, $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException(sprintf('Decoded JSON must be an array: %s', $path));
        }

        return $decoded;
    }

    /**
     * @return int[]
     */
    public function extractCustomerIds(array $payload): array
    {
        if (! isset($payload['Data']) || ! is_array($payload['Data'])) {
            return [];
        }

        $ids = [];
        foreach ($payload['Data'] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $fields = $item['Fields'] ?? null;
            if (! is_array($fields)) {
                continue;
            }

            $id = $fields['ID'] ?? null;
            if ($id === null || $id === '') {
                continue;
            }

            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<string, mixed>
     */
    public function formatCustomerDetail(array $payload): array
    {
        $fields = [];
        if (isset($payload['Fields']) && is_array($payload['Fields'])) {
            $fields = $payload['Fields'];
        }

        $links = [];
        if (isset($payload['Links']) && is_array($payload['Links'])) {
            $links = $payload['Links'];
        }

        $additional = $payload;
        unset($additional['Fields'], $additional['Links']);

        $id = null;
        if (isset($fields['ID']) && is_numeric($fields['ID'])) {
            $id = (int) $fields['ID'];
        }

        $number = $fields['Nummer'] ?? null;
        $searchTerm = $fields['Suchbegriff'] ?? null;

        return [
            'id' => $id,
            'number' => is_numeric($number) ? (int) $number : $number,
            'search_term' => $searchTerm,
            'uri' => $payload['URI'] ?? null,
            'fields' => $fields,
            'links' => $links,
            'additional' => $additional,
        ];
    }
}
