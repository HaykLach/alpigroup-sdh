<?php

declare(strict_types=1);

namespace App\Services\Ombis\DTO;

final class ImportSummaryDTO
{
    /**
     * @param array<int, ImportResultDTO> $details
     */
    public function __construct(
        public int $total,
        public int $success = 0,
        public int $partial = 0,
        public int $failed = 0,
        public array $details = [],
    ) {
    }
}
