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
    protected $signature = 'ombis:customer-references:import';

    protected $description = 'Fetch Ombis customer references and store them locally.';

    private const DETAILS_FILE = 'customer-details.json';

    private const REFS_DIRECTORY = 'refs';

    private const PAYMENT_METHOD_FILE = 'payment_method.json';

    private const CURRENCY_FILE = 'currency.json';

    private const BILLING_ADDRESS_FILE = 'billing_address.json';

    private const SHIPPING_ADDRESS_FILE = 'shipping_address.json';

    private const DELIVERY_ADDRESSES_FILE = 'delivery_addresses.json';

    private const REFERENCES_FILE = 'references.json';

    private Ombis $connector;

    private array $cachePaymentMethod = [];
    private array $cacheCurrencyByCustomer = [];
    private array $cacheBillingAddrByCustomer = [];
    private array $cacheShippingAddrByCustomer = [];
    private array $cacheDeliveryAddrById = [];

    public function handle(): int
    {
        $this->connector = new Ombis();

        $rootPath = Storage::path('ombis_customers/upload');
        if (!is_dir($rootPath)) {
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
            $relativeCustomerPath = $folderName;
            $detailsRelativePath = 'ombis_customers/upload/' . $folderName . DIRECTORY_SEPARATOR . self::DETAILS_FILE;

            if (!Storage::disk('local')->exists($detailsRelativePath)) {
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

            $refsRelativePath = 'ombis_customers/upload/' . $relativeCustomerPath . DIRECTORY_SEPARATOR . self::REFS_DIRECTORY;
            Storage::disk('local')->makeDirectory($refsRelativePath);

            $paymentMethod = $this->fetchPaymentMethod($paymentId, $folderName);
            $currency = $this->fetchCurrency($customerId, $folderName);
            $billing = $this->fetchBillingAddress($customerId, $folderName);
            $shipping = $this->fetchShippingAddress($customerId, $folderName, $fields);
            $delivery = $this->fetchDeliveryAddresses($deliveryReferences, $folderName);

            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::PAYMENT_METHOD_FILE, $paymentMethod, $folderName, 'payment method');
            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::CURRENCY_FILE, $currency, $folderName, 'currency');
            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::BILLING_ADDRESS_FILE, $billing, $folderName, 'billing address');
            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::SHIPPING_ADDRESS_FILE, $shipping, $folderName, 'shipping address');
            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::DELIVERY_ADDRESSES_FILE, $delivery, $folderName, 'delivery addresses');

            $references = [
                'payment_method' => $paymentMethod,
                'currency' => $currency,
                'billing_address' => $billing,
                'shipping_address' => $shipping,
                'delivery_addresses' => $delivery,
            ];

            $this->writeSummary($refsRelativePath . DIRECTORY_SEPARATOR . self::REFERENCES_FILE, $references, $folderName);

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
        $directories = glob($rootPath . DIRECTORY_SEPARATOR . 'customer_*', GLOB_ONLYDIR);

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
            return (string)$value;
        }

        if (!is_string($value)) {
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

        if (!is_array($references)) {
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

        $resource = $this->getPaymentMethod($paymentId, 200);

        if ($resource === null) {
            Log::warning('Invalid response received for Ombis customer reference.', [
                'customer_folder' => $folderName,
                'resource' => 'payment method',
            ]);
        }

        return $resource;
    }

    private function fetchCurrency(int|string $customerId, string $folderName): ?array
    {
        $resource = $this->getCurrencyByCustomer($customerId, 200);

        if ($resource === null) {
            Log::warning('Invalid response received for Ombis customer reference.', [
                'customer_folder' => $folderName,
                'resource' => 'currency',
            ]);
        }

        return $resource;
    }

    private function fetchBillingAddress(int|string $customerId, string $folderName): ?array
    {
        $resource = $this->getBillingAddressByCustomer($customerId, 200);

        if ($resource === null) {
            Log::warning('Invalid response received for Ombis customer reference.', [
                'customer_folder' => $folderName,
                'resource' => 'billing address',
            ]);
        }

        return $resource;
    }

    private function fetchShippingAddress(int|string $customerId, string $folderName, array $fields): ?array
    {
        if (($fields['BevorzugteLieferadresse'] ?? null) === null && ($fields['Postadresse'] ?? null) === null) {
            $this->warn(sprintf('Customer %s has no preferred or postal shipping reference.', $folderName));
        }

        $resource = $this->getShippingAddressByCustomer($customerId, 200);

        if ($resource === null) {
            Log::warning('Invalid response received for Ombis customer reference.', [
                'customer_folder' => $folderName,
                'resource' => 'shipping address',
            ]);
        }

        return $resource;
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
            $label = sprintf('delivery address %s', $reference);
            $address = $this->getDeliveryAddressById($reference, 200);

            if ($address !== null) {
                $addresses[$reference] = $address;
                continue;
            }

            Log::warning('Invalid response received for Ombis customer reference.', [
                'customer_folder' => $folderName,
                'resource' => $label,
            ]);
        }

        return $addresses === [] ? null : $addresses;
    }

    private function toArray(mixed $data): ?array
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_string($data)) {
            try {
                $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

                return is_array($decoded) ? $decoded : null;
            } catch (Throwable $exception) {
                Log::warning('Ombis decode failed', [
                    'err' => $exception->getMessage(),
                ]);

                return null;
            }
        }

        return null;
    }

    private function getPaymentMethod(int|string $paymentId, int $sleepMs = 0): ?array
    {
        $key = (string)$paymentId;

        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $this->cachePaymentMethod)) {
            return $this->cachePaymentMethod[$key];
        }

        try {
            $raw = $this->connector->requestPaymentMethod($paymentId);
        } catch (Throwable $exception) {
            Log::warning('Ombis payment method request failed', [
                'id' => $paymentId,
                'err' => $exception->getMessage(),
            ]);

            $this->cachePaymentMethod[$key] = null;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            return null;
        }

        $decoded = $this->toArray($raw);
        $this->cachePaymentMethod[$key] = $decoded;

        if ($sleepMs > 0) {
            usleep($sleepMs * 1000);
        }

        return $decoded;
    }

    private function getCurrencyByCustomer(int|string $customerId, int $sleepMs = 0): ?array
    {
        $key = (string)$customerId;

        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $this->cacheCurrencyByCustomer)) {
            return $this->cacheCurrencyByCustomer[$key];
        }

        try {
            $raw = $this->connector->requestCustomerCurrency($customerId);
        } catch (Throwable $exception) {
            Log::warning('Ombis currency request failed', [
                'customerId' => $customerId,
                'err' => $exception->getMessage(),
            ]);

            $this->cacheCurrencyByCustomer[$key] = null;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            return null;
        }

        $decoded = $this->toArray($raw);
        $this->cacheCurrencyByCustomer[$key] = $decoded;

        if ($sleepMs > 0) {
            usleep($sleepMs * 1000);
        }

        return $decoded;
    }

    private function getBillingAddressByCustomer(int|string $customerId, int $sleepMs = 0): ?array
    {
        $key = (string)$customerId;

        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $this->cacheBillingAddrByCustomer)) {
            return $this->cacheBillingAddrByCustomer[$key];
        }

        try {
            $raw = $this->connector->requestCustomerBillingAddress($customerId);
        } catch (Throwable $exception) {
            Log::warning('Ombis billing address request failed', [
                'customerId' => $customerId,
                'err' => $exception->getMessage(),
            ]);

            $this->cacheBillingAddrByCustomer[$key] = null;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            return null;
        }

        $decoded = $this->toArray($raw);
        $this->cacheBillingAddrByCustomer[$key] = $decoded;

        if ($sleepMs > 0) {
            usleep($sleepMs * 1000);
        }

        return $decoded;
    }

    private function getShippingAddressByCustomer(int|string $customerId, int $sleepMs = 0): ?array
    {
        $key = (string)$customerId;

        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $this->cacheShippingAddrByCustomer)) {
            return $this->cacheShippingAddrByCustomer[$key];
        }

        try {
            $raw = $this->connector->requestCustomerShippingAddress($customerId);
        } catch (Throwable $exception) {
            Log::warning('Ombis shipping address request failed', [
                'customerId' => $customerId,
                'err' => $exception->getMessage(),
            ]);

            $this->cacheShippingAddrByCustomer[$key] = null;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            return null;
        }

        $decoded = $this->toArray($raw);
        $this->cacheShippingAddrByCustomer[$key] = $decoded;

        if ($sleepMs > 0) {
            usleep($sleepMs * 1000);
        }

        return $decoded;
    }

    private function getDeliveryAddressById(int|string $addressId, int $sleepMs = 0): ?array
    {
        $key = (string)$addressId;

        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $this->cacheDeliveryAddrById)) {
            return $this->cacheDeliveryAddrById[$key];
        }

        try {
            $raw = $this->connector->requestDeliveryAddress($addressId);
        } catch (Throwable $exception) {
            Log::warning('Ombis delivery address request failed', [
                'id' => $addressId,
                'err' => $exception->getMessage(),
            ]);

            $this->cacheDeliveryAddrById[$key] = null;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            return null;
        }

        $decoded = $this->toArray($raw);
        $this->cacheDeliveryAddrById[$key] = $decoded;

        if ($sleepMs > 0) {
            usleep($sleepMs * 1000);
        }

        return $decoded;
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

