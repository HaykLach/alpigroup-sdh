<?php

declare(strict_types=1);

namespace App\Console\Commands\OmbisCustomers;

use App\Repositories\CustomerImportRepository;
use App\Services\CustomerImportService;
use Illuminate\Console\Command;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SmartDato\Ombis\Ombis;
use Throwable;

class ImportOmbisCustomersCommand extends Command
{
    protected $signature = 'ombis:customers:import {--dir=} {--filename=customers.json} {--limit=}';

    protected $description = 'Import customers from Ombis';

    protected Ombis $connector;

    public function __construct(
        private readonly CustomerImportService    $customerImportService,
        private readonly CustomerImportRepository $customerImportRepository,
        private readonly LoggerInterface          $logger,
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $directory = $this->resolveDirectory($this->option('dir'));
        $fileName = $this->resolveFileName($this->option('filename'));

        try {
            $limit = $this->resolveLimit($this->option('limit'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return Command::FAILURE;
        }

        try {
            $this->customerImportService->ensureDirectory($directory);
        } catch (RuntimeException $exception) {
            $this->logger->error('Unable to prepare directory for Ombis customer import.', [
                'directory' => $directory,
                'exception' => $exception->getMessage(),
            ]);
            $this->error($exception->getMessage());

            return Command::FAILURE;
        }

        $listPath = $directory . DIRECTORY_SEPARATOR . $fileName;

        $this->connector = new Ombis();
        try {
            $this->connector->requestCustomers($listPath);
        } catch (Throwable $exception) {
            $this->logger->error('Failed to request Ombis customer list.', [
                'directory' => $directory,
                'file' => $listPath,
                'exception' => $exception->getMessage(),
            ]);
            $this->error(sprintf('Failed to request customers: %s', $exception->getMessage()));

            return Command::FAILURE;
        }

        $customerImport = $this->customerImportRepository->insert($directory, $fileName, 'pending');
        $this->logger->info('Customer list downloaded.', [
            'import_id' => $customerImport->id,
            'directory' => $directory,
            'file' => $listPath,
        ]);

        $status = 'success';
        $failures = 0;
        $fetched = 0;
        $customerIdsCount = 0;
        $normalizedDetails = [];

        try {
            $listPayload = $this->customerImportService->decodeJsonFile($listPath);
            $customerIds = $this->customerImportService->extractCustomerIds($listPayload);
            $customerIdsCount = count($customerIds);

            if ($limit !== null) {
                $customerIds = array_slice($customerIds, 0, $limit);
            }

            foreach ($customerIds as $customerId) {
                $detailFileName = null;
                $detailPath = null;

                try {
                    $detailFileName = $this->connector->requestCustomerDetail($customerId, $directory);

                    $detailPayload = $this->customerImportService->decodeJsonFile($detailFileName);
                    $normalizedDetails[] = $this->customerImportService->formatCustomerDetail($detailPayload);

                    ++$fetched;

                    $this->logger->info('Fetched customer detail.', [
                        'import_id' => $customerImport->id,
                        'customer_id' => $customerId,
                        'directory' => $directory,
                        'detail_file' => $detailFileName,
                    ]);
                } catch (Throwable $exception) {
                    ++$failures;
                    $status = 'failed';

                    $this->logger->error('Failed to fetch customer detail.', [
                        'import_id' => $customerImport->id,
                        'customer_id' => $customerId,
                        'directory' => $directory,
                        'file' => $detailPath,
                        'detail_file' => $detailFileName,
                        'exception' => $exception->getMessage(),
                    ]);
                }
            }
        } catch (Throwable $exception) {
            $status = 'failed';

            $this->logger->error('Failed to process customer list.', [
                'import_id' => $customerImport->id,
                'directory' => $directory,
                'file' => $listPath,
                'exception' => $exception->getMessage(),
            ]);
            $this->error(sprintf('Failed to process customer list: %s', $exception->getMessage()));

            $this->customerImportRepository->updateStatus($customerImport->id, $status);
            $this->renderSummary($customerImport->id, $directory, $fileName, $customerIdsCount, $fetched, $failures);

            return Command::FAILURE;
        }

        if ($failures > 0) {
            $status = 'failed';
        }

        $this->customerImportRepository->updateStatus($customerImport->id, $status);

        if (!empty($normalizedDetails)) {
            $this->logger->debug('Formatted customer details prepared.', [
                'import_id' => $customerImport->id,
                'count' => count($normalizedDetails),
            ]);
        }

        $this->renderSummary($customerImport->id, $directory, $fileName, $customerIdsCount, $fetched, $failures);

        return $status === 'success' ? Command::SUCCESS : Command::FAILURE;
    }

    private function resolveDirectory(mixed $directory): string
    {
        if (is_string($directory) && $directory !== '') {
            $normalized = rtrim($directory, DIRECTORY_SEPARATOR);

            return $normalized === '' ? DIRECTORY_SEPARATOR : $normalized;
        }

        return 'ombis_customers/upload';
    }

    private function resolveFileName(mixed $fileName): string
    {
        if (is_string($fileName) && $fileName !== '') {
            return $fileName;
        }

        return 'customers_' . now()->format('Ymd_His') . '.json';
    }

    private function resolveLimit(mixed $limit): ?int
    {
        if ($limit === null || $limit === '') {
            return null;
        }

        if (!is_string($limit) && !is_int($limit)) {
            throw new RuntimeException('Limit option must be an integer.');
        }

        $value = (string)$limit;
        if ($value === '' || !ctype_digit($value)) {
            throw new RuntimeException('Limit option must be a positive integer.');
        }

        $intValue = (int)$value;
        if ($intValue <= 0) {
            throw new RuntimeException('Limit option must be greater than zero.');
        }

        return $intValue;
    }

    private function renderSummary(
        string $importId,
        string $directory,
        string $fileName,
        int    $customerIdsCount,
        int    $fetched,
        int    $failures,
    ): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Import ID', $importId],
                ['Directory', $directory],
                ['List File', $fileName],
                ['Customer IDs Found', $customerIdsCount],
                ['Details Fetched', $fetched],
                ['Failures', $failures],
            ],
        );
    }
}
