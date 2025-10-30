<?php

declare(strict_types=1);

namespace App\Services\Ombis\DTO;

/**
 * @phpstan-type OmbisSection array{status: string, message: string}
 */
final class ImportResultDTO
{
    /**
     * @param array<int, string> $messages
     * @param array<int, string> $warnings
     * @param array<int, string> $errors
     * @param array<string, OmbisSection> $sections
     */
    public function __construct(
        public int $customerId,
        public bool $createdOrUpdated = false,
        public array $messages = [],
        public array $warnings = [],
        public array $errors = [],
        public array $sections = [],
    ) {
    }
}
