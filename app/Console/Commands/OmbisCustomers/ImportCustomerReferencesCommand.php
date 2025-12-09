<?php

declare(strict_types=1);

namespace App\Console\Commands\OmbisCustomers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SmartDato\Ombis\Ombis;
use Throwable;

class ImportCustomerReferencesCommand extends Command
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

    private const BILLING_ADDRESS_REFERENCES_FILE = 'billing_address_references.json';

    private const SHIPPING_ADDRESS_REFERENCES_FILE = 'shipping_address_references.json';

    private const REFERENCES_FILE = 'references.json';

    private const LEGAL_INFORMATION_FILE = 'legal_information.json';

    private Ombis $connector;

    private array $cachePaymentMethod = [];
    private array $cacheCurrencyByCustomer = [];
    private array $cacheBillingAddrByCustomer = [];
    private array $cacheShippingAddrByCustomer = [];
    private array $cacheDeliveryAddrById = [];
    private array $cacheAddressReferenceByUri = [];
    private array $cacheAddressReferenceByCustomerAndName = [];
    private array $cacheLandRegionByLandAndRegion = [];
    private array $cacheLegalInformationByCustomer = [];

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
            $billingReferences = $this->fetchBillingAddressReferences($billing, $folderName, $customerId);
            $shipping = $this->fetchShippingAddress($customerId, $folderName, $fields);
            $shippingReferences = $this->fetchShippingAddressReferences($shipping, $folderName, $customerId);
            $delivery = $this->fetchDeliveryAddresses($deliveryReferences, $folderName);
            $legalInformation = $this->fetchLegalInformation($customerId, $folderName);

            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::PAYMENT_METHOD_FILE, $paymentMethod, $folderName, 'payment method');
            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::CURRENCY_FILE, $currency, $folderName, 'currency');
            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::BILLING_ADDRESS_FILE, $billing, $folderName, 'billing address');
            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::BILLING_ADDRESS_REFERENCES_FILE, $billingReferences, $folderName, 'billing address references');
            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::SHIPPING_ADDRESS_FILE, $shipping, $folderName, 'shipping address');
            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::SHIPPING_ADDRESS_REFERENCES_FILE, $shippingReferences, $folderName, 'shipping address references');
            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::DELIVERY_ADDRESSES_FILE, $delivery, $folderName, 'delivery addresses');
            $this->writeResource($refsRelativePath . DIRECTORY_SEPARATOR . self::LEGAL_INFORMATION_FILE, $legalInformation, $folderName, 'legal information');

            $references = [
                'payment_method' => $paymentMethod,
                'currency' => $currency,
                'billing_address' => $billing,
                'billing_address_references' => $billingReferences,
                'shipping_address' => $shipping,
                'shipping_address_references' => $shippingReferences,
                'delivery_addresses' => $delivery,
                'legal_information' => $legalInformation,
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

    private function fetchBillingAddressReferences(?array $billing, string $folderName, int|string $customerId): ?array
    {
        return $this->fetchAddressReferences($billing, $folderName, $customerId, 'billing');
    }

    private function fetchShippingAddressReferences(?array $shipping, string $folderName, int|string $customerId): ?array
    {
        return $this->fetchAddressReferences($shipping, $folderName, $customerId, 'shipping');
    }

    private function fetchAddressReferences(?array $resource, string $folderName, int|string $customerId, string $type): ?array
    {
        $references = $this->extractAddressReferences($resource);

        if ($references === []) {
            return null;
        }

        $resolved = [];

        foreach ($references as $reference) {
            $name = is_string($reference['Name'] ?? null) ? $reference['Name'] : null;
            $uri = is_string($reference['URI'] ?? null) ? $reference['URI'] : null;

            if ($name === null) {
                continue;
            }

            $resourceReference = $this->getAddressReferenceByName($customerId, $name, $type, 200);

            if ($resourceReference === null && $uri !== null) {
                $resourceReference = $this->getReferenceByUri($uri, 200);
            }

            if ($resourceReference !== null) {
                $resolved[$name] = $resourceReference;

                if (strtolower($name) === 'land') {
                    $landRegion = $this->resolveLandRegionReference($resourceReference, $folderName, $type);

                    if ($landRegion !== null && ! array_key_exists('Region', $resolved)) {
                        $resolved['Region'] = $landRegion;
                    }
                }

                continue;
            }

            Log::warning('Invalid response received for Ombis customer reference.', [
                'customer_folder' => $folderName,
                'resource' => sprintf('%s %s reference', $type, $name),
            ]);
        }

        return $resolved === [] ? null : $resolved;
    }

    private function resolveLandRegionReference(array $landResource, string $folderName, string $type): ?array
    {
        $fields = is_array($landResource['Fields'] ?? null) ? $landResource['Fields'] : null;
        $landId = $this->extractIdentifier($fields['ID'] ?? null);

        if ($landId === null) {
            return null;
        }

        $references = $this->extractAddressReferences($landResource);

        foreach ($references as $reference) {
            if (strtolower($reference['Name'] ?? '') !== 'region') {
                continue;
            }

            $regionId = $this->extractIdentifier($reference['ID'] ?? null);

            if ($regionId === null) {
                $regionFields = is_array($reference['Fields'] ?? null) ? $reference['Fields'] : null;
                $regionId = $this->extractIdentifier($regionFields['ID'] ?? null);
            }

            if ($regionId === null) {
                $regionId = $this->extractRegionIdFromUri($reference['URI'] ?? null);
            }

            if ($regionId === null) {
                continue;
            }

            $region = $this->getLandRegion($landId, $regionId, 200);

            if ($region !== null) {
                return $region;
            }

            Log::warning('Unable to resolve land region reference.', [
                'customer_folder' => $folderName,
                'type' => $type,
                'land_id' => $landId,
                'region_id' => $regionId,
            ]);
        }

        return null;
    }

    private function extractRegionIdFromUri(?string $uri): ?string
    {
        if (!is_string($uri) || $uri === '') {
            return null;
        }

        if (preg_match('/region\/(\d+)/', $uri, $matches) === 1) {
            return $matches[1];
        }

        return null;
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

    private function fetchLegalInformation(int|string $customerId, string $folderName): ?array
    {
        $resource = $this->getLegalInformation($customerId, 200);

        if ($resource === null) {
            Log::warning('Invalid response received for Ombis customer reference.', [
                'customer_folder' => $folderName,
                'resource' => 'legal information',
            ]);
        }

        return $resource;
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

    private function getLegalInformation(int|string $customerId, int $sleepMs = 0): ?array
    {
        $key = (string)$customerId;

        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $this->cacheLegalInformationByCustomer)) {
            return $this->cacheLegalInformationByCustomer[$key];
        }

        try {
            $raw = $this->connector->legalInformation($customerId);
        } catch (Throwable $exception) {
            Log::warning('Ombis legal information request failed', [
                'customer_id' => $customerId,
                'err' => $exception->getMessage(),
            ]);

            $this->cacheLegalInformationByCustomer[$key] = null;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            return null;
        }

        $decoded = $this->toArray($raw);
        $this->cacheLegalInformationByCustomer[$key] = $decoded;

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

    private function getLandRegion(int|string $landId, int|string $regionId, int $sleepMs = 0): ?array
    {
        $landKey = (string)$landId;
        $regionKey = (string)$regionId;

        if ($landKey === '' || $regionKey === '') {
            return null;
        }

        $cacheKey = $landKey . '|' . $regionKey;

        if (array_key_exists($cacheKey, $this->cacheLandRegionByLandAndRegion)) {
            return $this->cacheLandRegionByLandAndRegion[$cacheKey];
        }

        try {
            $raw = $this->connector->requestLandRegion($landId, $regionId);
        } catch (Throwable $exception) {
            Log::warning('Ombis land region request failed', [
                'landId' => $landId,
                'regionId' => $regionId,
                'err' => $exception->getMessage(),
            ]);

            $this->cacheLandRegionByLandAndRegion[$cacheKey] = null;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            return null;
        }

        $decoded = $this->toArray($raw);
        $this->cacheLandRegionByLandAndRegion[$cacheKey] = $decoded;

        if ($sleepMs > 0) {
            usleep($sleepMs * 1000);
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed>|null $resource
     * @return array<int, array<string, mixed>>
     */
    private function extractAddressReferences(?array $resource): array
    {
        if (!is_array($resource)) {
            return [];
        }

        $links = is_array($resource['Links'] ?? null) ? $resource['Links'] : null;
        $references = is_array($links['References'] ?? null) ? $links['References'] : null;

        if ($references === null) {
            return [];
        }

        return array_values(array_filter(
            $references,
            static fn ($reference) => is_array($reference)
        ));
    }

    private function resolveReferenceUri(?string $uri): ?string
    {
        if (!is_string($uri) || $uri === '') {
            return null;
        }

        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            return $uri;
        }

        $base = config('services.ombis.url', env('OMBIS_API_URL'));

        if (!is_string($base) || $base === '') {
            return null;
        }

        $parsed = parse_url($base);

        if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])) {
            return null;
        }

        $authority = $parsed['scheme'] . '://' . $parsed['host'];
        if (isset($parsed['port'])) {
            $authority .= ':' . $parsed['port'];
        }

        if (str_starts_with($uri, '/')) {
            return $authority . $uri;
        }

        $path = $parsed['path'] ?? '';
        $prefix = rtrim($authority . '/' . ltrim($path, '/'), '/');

        return $prefix . '/' . ltrim($uri, '/');
    }

    private function getReferenceByUri(string $uri, int $sleepMs = 0): ?array
    {
        $key = $uri;

        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $this->cacheAddressReferenceByUri)) {
            return $this->cacheAddressReferenceByUri[$key];
        }

        $endpoint = $this->resolveReferenceUri($uri);

        if ($endpoint === null) {
            $this->cacheAddressReferenceByUri[$key] = null;

            return null;
        }

        $username = config('services.ombis.username', env('OMBIS_API_USER'));
        $password = config('services.ombis.password', env('OMBIS_API_PASSWORD'));

        if (!is_string($username) || $username === '' || !is_string($password) || $password === '') {
            Log::warning('Ombis reference credentials missing.');
            $this->cacheAddressReferenceByUri[$key] = null;

            return null;
        }

        try {
            $response = Http::withBasicAuth($username, $password)
                ->acceptJson()
                ->timeout(30)
                ->get($endpoint);
        } catch (Throwable $exception) {
            Log::warning('Ombis reference request failed', [
                'uri' => $uri,
                'exception' => $exception->getMessage(),
            ]);

            $this->cacheAddressReferenceByUri[$key] = null;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            return null;
        }

        if (!$response->successful()) {
            Log::warning('Ombis reference request failed', [
                'uri' => $uri,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $this->cacheAddressReferenceByUri[$key] = null;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            return null;
        }

        $decoded = $response->json();

        if (!is_array($decoded)) {
            Log::warning('Ombis decode failed', [
                'err' => 'reference response not an array',
                'uri' => $uri,
            ]);

            $this->cacheAddressReferenceByUri[$key] = null;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            return null;
        }

        $this->cacheAddressReferenceByUri[$key] = $decoded;

        if ($sleepMs > 0) {
            usleep($sleepMs * 1000);
        }

        return $decoded;
    }

    private function getAddressReferenceByName(int|string $customerId, string $name, string $type, int $sleepMs = 0): ?array
    {
        $normalized = strtolower($name);
        $method = match ($type) {
            'billing' => match ($normalized) {
                'geburtsland' => 'requestCustomerBillingAddressGeburtsland',
                'region' => 'requestCustomerBillingAddressRegion',
                'provinz' => 'requestCustomerBillingAddressProvinz',
                'gemeinde' => 'requestCustomerBillingAddressGemeinde',
                'dateperiod' => 'requestCustomerBillingAddressDatePeriod',
                default => null,
            },
            'shipping' => match ($normalized) {
                'region' => 'requestCustomerShippingAddressRegion',
                'provinz' => 'requestCustomerShippingAddressProvinz',
                'gemeinde' => 'requestCustomerShippingAddressGemeinde',
                default => null,
            },
            default => null,
        };

        if ($method === null || !method_exists($this->connector, $method)) {
            return null;
        }

        $cacheKey = sprintf('%s|%s|%s', (string)$customerId, $type, $method);

        if (array_key_exists($cacheKey, $this->cacheAddressReferenceByCustomerAndName)) {
            return $this->cacheAddressReferenceByCustomerAndName[$cacheKey];
        }

        try {
            $raw = $this->connector->{$method}($customerId);
        } catch (Throwable $exception) {
            Log::warning('Ombis address reference request failed', [
                'customerId' => $customerId,
                'reference' => $name,
                'type' => $type,
                'err' => $exception->getMessage(),
            ]);

            $this->cacheAddressReferenceByCustomerAndName[$cacheKey] = null;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            return null;
        }

        $decoded = $this->toArray($raw);
        $this->cacheAddressReferenceByCustomerAndName[$cacheKey] = $decoded;

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

