<?php

declare(strict_types=1);

namespace App\Console\Commands\OmbisCustomers;

use App\Services\Ombis\CustomerImporter;
use App\Services\Ombis\DTO\ImportResultDTO;
use App\Services\Ombis\DTO\ImportSummaryDTO;
use Illuminate\Console\Command;

final class ImportCustomersToDatabaseCommand extends Command
{
    protected $signature = 'ombis:import-customer {customerId?} {--all}';

    protected $description = 'Import Ombis customer references from cached JSON files.';

    public function __construct(private readonly CustomerImporter $importer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $customerId = $this->argument('customerId');
        $importAll = (bool) $this->option('all');

        if ($importAll) {
            return $this->handleImportAll();
        }

        if ($customerId === null) {
            $this->error('Please provide a customerId or use the --all option.');

            return Command::FAILURE;
        }

        if (!is_numeric($customerId)) {
            $this->error('Customer ID must be numeric.');

            return Command::FAILURE;
        }

        $customerId = (int) $customerId;
        $result = $this->importer->importOne($customerId);
        $this->renderResult($result);
        $this->renderSummary(new ImportSummaryDTO(
            total: 1,
            success: $result->errors === [] && $result->warnings === [] ? 1 : 0,
            partial: $result->errors === [] && $result->warnings !== [] ? 1 : 0,
            failed: $result->errors !== [] ? 1 : 0,
            details: [$result],
        ));

        return $result->errors === [] ? Command::SUCCESS : Command::FAILURE;
    }

    private function handleImportAll(): int
    {
        $summary = $this->importer->importAll();

        foreach ($summary->details as $result) {
            $this->renderResult($result);
        }

        $this->renderSummary($summary);

        return $summary->failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function renderResult(ImportResultDTO $result): void
    {
        $status = $this->determineStatus($result);
        $icon = match ($status) {
            'failed' => '✖',
            'partial' => '△',
            default => '✔',
        };

        $statusLabel = match ($status) {
            'failed' => 'failed',
            'partial' => 'partial',
            default => $result->createdOrUpdated ? 'updated' : 'unchanged',
        };

        $sections = $this->formatSections($result);
        $sectionSummary = $sections === [] ? '' : ' (' . implode(', ', $sections) . ')';

        $this->line(sprintf(
            '%s Customer %d: %s%s',
            $icon,
            $result->customerId,
            $statusLabel,
            $sectionSummary,
        ));
    }

    private function renderSummary(ImportSummaryDTO $summary): void
    {
        $this->info(sprintf(
            'Summary: %d total, %d success, %d partial, %d failed.',
            $summary->total,
            $summary->success,
            $summary->partial,
            $summary->failed,
        ));
    }

    private function determineStatus(ImportResultDTO $result): string
    {
        if ($result->errors !== []) {
            return 'failed';
        }

        if ($result->warnings !== []) {
            return 'partial';
        }

        return 'success';
    }

    /**
     * @return array<int, string>
     */
    private function formatSections(ImportResultDTO $result): array
    {
        $sections = [];
        $order = ['billing', 'shipping', 'payment'];

        foreach ($order as $key) {
            if (!array_key_exists($key, $result->sections)) {
                continue;
            }

            $data = $result->sections[$key];
            $icon = match ($data['status']) {
                'success' => '✔',
                'error' => '✖',
                default => '△',
            };

            $message = $data['message'] !== '' ? ' ' . $data['message'] : '';
            $sections[] = sprintf('%s %s%s', $key, $icon, $message);
        }

        return $sections;
    }
}
