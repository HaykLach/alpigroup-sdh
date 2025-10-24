<?php

namespace App\Contracts\Processor;

interface ProcessorInterface
{
    public const IMPORT_STATUS_FAILED = 'failed';

    public const IMPORT_STATUS_CREATED = 'created';

    public const IMPORT_STATUS_UPDATED = 'updated';

    public const IMPORT_STATUS_SKIPPED = 'skipped';

    /**
     * every command logic must be written inside processor
     * all processors must implement this interface
     */
    public function process(string $commandName): array;
}
