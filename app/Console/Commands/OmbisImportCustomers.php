<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SmartDato\Ombis\Ombis;
use Throwable;

class OmbisImportCustomers extends Command
{
    protected $signature = 'ombis:customers:import';

    protected $description = 'Fetch Ombis customer references and store them locally.';

    private const DETAILS_FILE = 'customer_details.json';

    private const REFS_DIRECTORY = 'refs';

    private const PAYMENT_METHOD_FILE = 'payment_method.json';

    private const CURRENCY_FILE = 'currency.json';

    private const BILLING_ADDRESS_FILE = 'billing_address.json';

    private const SHIPPING_ADDRESS_FILE = 'shipping_address.json';

    private const DELIVERY_ADDRESSES_FILE = 'delivery_addresses.json';

    private const REFERENCES_FILE = 'references.json';

    private Ombis $connector;

    public function handle(): int
    {
        $this->connector = new Ombis();

        $rootPath = storage_path('app/customers');
        if (! is_dir($rootPath)) {
            $this->error(sprintf('Customers directory not found at %s', $rootPath));

            return Command::FAILURE;
        }

        $customerDirectories = $this->discoverCustomerDirectories($rootPath);
        if ($customerDirectories === []) {
            $this->info('No customer folders found.');

            return Command::SUCCESS;
        }

        $processed = 0;
        $skipped = 0;

        foreach ($customerDirectories as $directory) {
            $folderName = basename($directory);
            $relativeCustomerPath = 'customers'.DIRECTORY_SEPARATOR.$folderName;
            $detailsRelativePath = $relativeCustomerPath.DIRECTORY_SEPARATOR.self::DETAILS_FILE;

            if (! Storage::disk('local')->exists($detailsRelativePath)) {
                ++$skipped;
                $this->warn(sprintf('Skipping %s: %s missing.', $folderName, self::DETAILS_FILE));
                continue;
            }

            $details = $this->readJsonFile($detailsRelativePath);
            if ($details === null) {
                ++$skipped;
                $this->warn(sprintf('Skipping %s: unable to decode %s.', $folderName, self::DETAILS_FILE));
                continue;
            }

            $fields = is_array($details['Fields'] ?? null) ? $details['Fields'] : [];

            $customerId = $fields['ID'] ?? null;
            if ($customerId === null) {
                ++$skipped;
                $this->warn(sprintf('Skipping %s: missing customer ID.', $folderName));
                continue;
            }

            $paymentId = $this->extractIdentifier($fields['Zahlungsart'] ?? null);
            $deliveryReferences = $this->normalizeDeliveryReferences($fields['Lieferadresse'] ?? []);

            $refsRelativePath = $relativeCustomerPath.DIRECTORY_SEPARATOR.self::REFS_DIRECTORY;
            Storage::disk('local')->makeDirectory($refsRelativePath);

            $paymentMethod = $this->fetchPaymentMethod($paymentId, $folderName);
            $currency = $this->fetchCurrency($customerId, $folderName);
            $billing = $this->fetchBillingAddress($customerId, $folderName);
            $shipping = $this->fetchShippingAddress($customerId, $folderName, $fields);
            $delivery = $this->fetchDeliveryAddresses($deliveryReferences, $folderName);

            $this->writeResource($refsRelativePath.DIRECTORY_SEPARATOR.self::PAYMENT_METHOD_FILE, $paymentMethod, $folderName, 'payment method');
            $this->writeResource($refsRelativePath.DIRECTORY_SEPARATOR.self::CURRENCY_FILE, $currency, $folderName, 'currency');
            $this->writeResource($refsRelativePath.DIRECTORY_SEPARATOR.self::BILLING_ADDRESS_FILE, $billing, $folderName, 'billing address');
            $this->writeResource($refsRelativePath.DIRECTORY_SEPARATOR.self::SHIPPING_ADDRESS_FILE, $shipping, $folderName, 'shipping address');
            $this->writeResource($refsRelativePath.DIRECTORY_SEPARATOR.self::DELIVERY_ADDRESSES_FILE, $delivery, $folderName, 'delivery addresses');

            $references = [
                'payment_method' => $paymentMethod,
                'currency' => $currency,
                'billing_address' => $billing,
                'shipping_address' => $shipping,
                'delivery_addresses' => $delivery,
            ];

            $this->writeSummary($refsRelativePath.DIRECTORY_SEPARATOR.self::REFERENCES_FILE, $references, $folderName);

            ++$processed;
        }

        $this->info(sprintf('Processed %d customer(s), skipped %d.', $processed, $skipped));

        return Command::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function discoverCustomerDirectories(string $rootPath): array
    {
        $directories = glob($rootPath.DIRECTORY_SEPARATOR.'customer_*', GLOB_ONLYDIR);

        if ($directories === false) {
            return [];
        }

        sort($directories);

        return $directories;
    }

    private function readJsonFile(string $relativePath): ?array
    {
        try {
            $contents = Storage::disk('local')->get($relativePath);
        } catch (Throwable $exception) {
            Log::warning('Unable to read Ombis customer file.', [
                'path' => $relativePath,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            Log::warning('Unable to decode Ombis customer JSON.', [
                'path' => $relativePath,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function extractIdentifier(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        if (preg_match('/(\d+)/', $value, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeDeliveryReferences(mixed $references): array
    {
        if (is_string($references) && $references !== '') {
            $references = [$references];
        }

        if (! is_array($references)) {
            return [];
        }

        $normalized = [];

        foreach ($references as $reference) {
            $identifier = $this->extractIdentifier($reference);
            if ($identifier !== null) {
                $normalized[] = $identifier;
            }
        }

        return $normalized;
    }

    private function fetchPaymentMethod(?string $paymentId, string $folderName): ?array
    {
        if ($paymentId === null) {
            $this->warn(sprintf('Customer %s has no payment method reference.', $folderName));

            return null;
        }

        return $this->fetchResource(
            fn () => $this->connector->requestPaymentMethod($paymentId),
            'payment method',
            $folderName
        );
    }

    private function fetchCurrency(int|string $customerId, string $folderName): ?array
    {
        return $this->fetchResource(
            fn () => $this->connector->requestCustomerCurrency($customerId),
            'currency',
            $folderName
        );
    }

    private function fetchBillingAddress(int|string $customerId, string $folderName): ?array
    {
        return $this->fetchResource(
            fn () => $this->connector->requestCustomerBillingAddress($customerId),
            'billing address',
            $folderName
        );
    }

    private function fetchShippingAddress(int|string $customerId, string $folderName, array $fields): ?array
    {
        if (($fields['BevorzugteLieferadresse'] ?? null) === null && ($fields['Postadresse'] ?? null) === null) {
            $this->warn(sprintf('Customer %s has no preferred or postal shipping reference.', $folderName));
        }

        return $this->fetchResource(
            fn () => $this->connector->requestCustomerShippingAddress($customerId),
            'shipping address',
            $folderName
        );
    }

    /**
     * @param array<int, string> $references
     */
    private function fetchDeliveryAddresses(array $references, string $folderName): ?array
    {
        if ($references === []) {
            $this->warn(sprintf('Customer %s has no delivery address references.', $folderName));

            return null;
        }

        $addresses = [];

        foreach ($references as $reference) {
            $address = $this->fetchResource(
                fn () => $this->connector->requestDeliveryAddress($reference),
                sprintf('delivery address %s', $reference),
                $folderName
            );

            if ($address !== null) {
                $addresses[$reference] = $address;
            }
        }

        return $addresses === [] ? null : $addresses;
    }

    private function fetchResource(callable $callback, string $label, string $folderName): ?array
    {
        try {
            $response = $callback();
        } catch (Throwable $exception) {
            Log::warning('Failed to request Ombis customer reference.', [
                'customer_folder' => $folderName,
                'resource' => $label,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        } finally {
            usleep(200_000);
        }

        $normalized = $this->normalizeResponse($response);

        if ($normalized === null) {
            Log::warning('Invalid response received for Ombis customer reference.', [
                'customer_folder' => $folderName,
                'resource' => $label,
            ]);
        }

        return $normalized;
    }

    private function normalizeResponse(array|string $response): ?array
    {
        if (is_array($response)) {
            return $response;
        }

        if (! is_string($response) || $response === '') {
            return null;
        }

        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            Log::warning('Unable to decode Ombis response.', [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function writeResource(string $relativePath, ?array $data, string $folderName, string $resource): void
    {
        if ($data === null) {
            return;
        }

        $this->writeJson($relativePath, $data, $folderName, $resource);
    }

    private function writeSummary(string $relativePath, array $references, string $folderName): void
    {
        $this->writeJson($relativePath, $references, $folderName, 'references summary');
    }

    private function writeJson(string $relativePath, mixed $data, string $folderName, string $resource): void
    {
        try {
            $encoded = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            Storage::disk('local')->put($relativePath, $encoded);
        } catch (Throwable $exception) {
            Log::warning('Unable to store Ombis customer reference.', [
                'customer_folder' => $folderName,
                'resource' => $resource,
                'path' => $relativePath,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}

