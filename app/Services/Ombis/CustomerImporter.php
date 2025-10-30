<?php

declare(strict_types=1);

namespace App\Services\Ombis;

use App\Enums\Pim\PimCustomerCustomFields;
use App\Models\Pim\Country\PimCountry;
use App\Models\Pim\Customer\PimCustomer;
use App\Models\Pim\Customer\PimCustomerAddress;
use App\Models\Pim\PaymentMethod\PimPaymentMethod;
use App\Services\Ombis\DTO\ImportResultDTO;
use App\Services\Ombis\DTO\ImportSummaryDTO;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

final class CustomerImporter
{
    private const BASE_PATH = 'ombis_customers/upload';
    private const BILLING_FILE = 'billing_address.json';
    private const SHIPPING_FILE = 'shipping_address.json';
    private const PAYMENT_FILE = 'payment_method.json';

    /**
     * @var array<int, string>|null
     */
    private ?array $customerColumns = null;

    /**
     * @var array<int, string>|null
     */
    private ?array $addressColumns = null;

    /**
     * @var array<int, string>|null
     */
    private ?array $paymentColumns = null;

    /**
     * @var array<string, string>
     */
    private array $countryCache = [];

    public function __construct(private readonly DatabaseManager $databaseManager)
    {
    }

    public function importOne(int $customerId): ImportResultDTO
    {
        $result = new ImportResultDTO(customerId: $customerId);

        $disk = Storage::disk('local');
        $directory = self::BASE_PATH . '/customer_' . $customerId;

        if (!$disk->directoryExists($directory)) {
            $message = sprintf('Customer directory not found at %s.', $directory);
            Log::warning('Ombis customer import directory missing.', [
                'customer_id' => $customerId,
                'path' => $directory,
            ]);
            $result->warnings[] = $message;
            $this->setSection($result, 'billing', 'warning', 'missing directory');
            $this->setSection($result, 'shipping', 'warning', 'missing directory');
            $this->setSection($result, 'payment', 'warning', 'missing directory');

            return $result;
        }

        $billingPath = $directory . '/' . self::BILLING_FILE;
        $shippingPath = $directory . '/' . self::SHIPPING_FILE;
        $paymentPath = $directory . '/' . self::PAYMENT_FILE;

        $billingPayload = $this->loadJsonForSection($result, $customerId, $billingPath, 'billing');
        $shippingPayload = $this->loadJsonForSection($result, $customerId, $shippingPath, 'shipping');
        $paymentPayload = $this->loadJsonForSection($result, $customerId, $paymentPath, 'payment');

        try {
            $this->databaseManager->connection()->transaction(function () use ($customerId, $billingPayload, $shippingPayload, $paymentPayload, $result): void {
                $normalizedCustomerData = array_merge(
                    $billingPayload !== null ? $this->flatten($billingPayload) : [],
                    $shippingPayload !== null ? $this->flatten($shippingPayload) : [],
                    $paymentPayload !== null ? $this->flatten($paymentPayload) : [],
                );

                $customer = $this->upsertCustomer($customerId, $normalizedCustomerData, $result);

                if ($customer === null) {
                    if ($billingPayload !== null) {
                        $this->setSection($result, 'billing', 'warning', 'customer missing');
                    }
                    if ($shippingPayload !== null) {
                        $this->setSection($result, 'shipping', 'warning', 'customer missing');
                    }
                    if ($paymentPayload !== null) {
                        $this->setSection($result, 'payment', 'warning', 'customer missing');
                    }

                    return;
                }

                if ($billingPayload !== null) {
                    $this->upsertAddress($customer, $billingPayload, 'billing', $result);
                }

                if ($shippingPayload !== null) {
                    $this->upsertAddress($customer, $shippingPayload, 'shipping', $result);
                }

                if ($paymentPayload !== null) {
                    $this->upsertPaymentMethod($customer, $paymentPayload, $result);
                }
            });
        } catch (Throwable $exception) {
            Log::error('Failed to import Ombis customer.', [
                'customer_id' => $customerId,
                'exception' => $exception->getMessage(),
            ]);

            $result->errors[] = sprintf('Failed to import customer %d: %s', $customerId, $exception->getMessage());
        }

        return $result;
    }

    public function importAll(): ImportSummaryDTO
    {
        $disk = Storage::disk('local');
        if (!$disk->directoryExists(self::BASE_PATH)) {
            return new ImportSummaryDTO(total: 0);
        }

        $directories = $disk->directories(self::BASE_PATH);
        $customerIds = [];
        foreach ($directories as $directory) {
            $name = basename($directory);
            if (!Str::startsWith($name, 'customer_')) {
                continue;
            }

            $idPart = Str::after($name, 'customer_');
            if ($idPart === '' || !ctype_digit($idPart)) {
                continue;
            }

            $customerIds[] = (int) $idPart;
        }

        sort($customerIds);

        $summary = new ImportSummaryDTO(total: count($customerIds));

        foreach ($customerIds as $customerId) {
            $result = $this->importOne($customerId);
            $summary->details[] = $result;

            if ($result->errors !== []) {
                ++$summary->failed;
                continue;
            }

            if ($result->warnings !== []) {
                ++$summary->partial;
                continue;
            }

            ++$summary->success;
        }

        return $summary;
    }

    private function upsertCustomer(int $customerId, array $normalizedData, ImportResultDTO $result): ?PimCustomer
    {
        $existing = PimCustomer::query()->where('identifier', (string) $customerId)->first();
        $attributes = $this->mapToCustomer($normalizedData, $customerId);

        if (!array_key_exists('custom_fields', $attributes) || $attributes['custom_fields'] === []) {
            if ($existing === null) {
                $attributes['custom_fields'] = [
                    PimCustomerCustomFields::BLOCKED->value => false,
                ];
            } else {
                unset($attributes['custom_fields']);
            }
        }

        if ($existing === null && $this->requiresCustomerFieldsMissing($attributes)) {
            $result->warnings[] = sprintf('Customer %d missing required fields. Skipping customer creation.', $customerId);

            return null;
        }

        if ($existing === null) {
            $customer = PimCustomer::query()->create($attributes);
            $result->createdOrUpdated = true;
            $result->messages[] = sprintf('Customer %d created.', $customerId);

            return $customer;
        }

        $hasChanges = false;
        $customFields = $attributes['custom_fields'] ?? null;
        unset($attributes['custom_fields']);

        $attributes = $this->filterNullValues($attributes);

        if ($attributes !== []) {
            $existing->fill($attributes);
            $hasChanges = true;
        }

        if ($customFields !== null) {
            $currentCustomFields = $existing->custom_fields ?? [];
            $merged = array_merge($currentCustomFields, $this->filterNullValues($customFields));
            $existing->custom_fields = $merged;
            $hasChanges = true;
        }

        if ($hasChanges) {
            $existing->save();
            $result->createdOrUpdated = true;
            $result->messages[] = sprintf('Customer %d updated.', $customerId);
        }

        return $existing;
    }

    private function upsertAddress(PimCustomer $customer, array $payload, string $type, ImportResultDTO $result): void
    {
        $attributes = $this->mapToAddress($payload, $type);
        $attributes['customer_id'] = $customer->id;

        $filtered = $this->filterNullValues($attributes);
        $requiredKeys = ['street', 'city', 'zipcode'];
        $missing = [];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $filtered)) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            $message = sprintf('%s address missing required fields: %s.', ucfirst($type), implode(', ', $missing));
            $result->warnings[] = $message;
            $this->setSection($result, $type, 'warning', 'missing fields');

            return;
        }

        $search = [
            'customer_id' => $customer->id,
        ];
        if (array_key_exists('custom_fields', $filtered)) {
            $search['custom_fields'] = $filtered['custom_fields'];
        }

        $address = PimCustomerAddress::query()->updateOrCreate($search, $filtered);

        $columns = $this->getCustomerColumns();
        if ($type === 'billing' && in_array('default_billing_address_id', $columns, true)) {
            $customer->default_billing_address_id = $address->id;
        }

        if ($type === 'shipping' && in_array('default_shipping_address_id', $columns, true)) {
            $customer->default_shipping_address_id = $address->id;
        }

        if (in_array('default_address_id', $columns, true) && $customer->default_address_id === null) {
            $customer->default_address_id = $address->id;
        }

        $customer->save();

        $result->createdOrUpdated = true;
        $result->messages[] = sprintf('%s address synced.', ucfirst($type));
        $this->setSection($result, $type, 'success', 'synced');
    }

    private function upsertPaymentMethod(PimCustomer $customer, array $payload, ImportResultDTO $result): void
    {
        $attributes = $this->mapToPaymentMethod($payload);
        if ($attributes === []) {
            $result->warnings[] = 'Payment method missing required data.';
            $this->setSection($result, 'payment', 'warning', 'missing fields');

            return;
        }

        $unique = [];
        if (array_key_exists('technical_name', $attributes)) {
            $unique['technical_name'] = $attributes['technical_name'];
        } elseif (array_key_exists('name', $attributes)) {
            $unique['name'] = $attributes['name'];
        }

        if ($unique === []) {
            $result->warnings[] = 'Payment method missing identifier.';
            $this->setSection($result, 'payment', 'warning', 'missing identifier');

            return;
        }

        $paymentMethod = PimPaymentMethod::query()->updateOrCreate($unique, $attributes);

        $columns = $this->getCustomerColumns();
        if (in_array('default_payment_method', $columns, true)) {
            $customer->default_payment_method = $paymentMethod->id;
            $customer->save();
        }

        $result->createdOrUpdated = true;
        $result->messages[] = 'Payment method synced.';
        $this->setSection($result, 'payment', 'success', 'synced');
    }

    private function loadJsonForSection(ImportResultDTO $result, int $customerId, string $path, string $section): ?array
    {
        $disk = Storage::disk('local');
        if (!$disk->exists($path)) {
            $message = sprintf('%s file missing for customer %d.', basename($path), $customerId);
            Log::warning('Ombis customer import file missing.', [
                'customer_id' => $customerId,
                'path' => $path,
            ]);
            $result->warnings[] = $message;
            $this->setSection($result, $section, 'warning', 'missing file');

            return null;
        }

        $payload = $this->readJsonOrNull($path, $customerId);
        if ($payload === null) {
            $result->errors[] = sprintf('Unable to decode %s for customer %d.', basename($path), $customerId);
            $this->setSection($result, $section, 'error', 'invalid JSON');

            return null;
        }

        return $payload;
    }

    private function setSection(ImportResultDTO $result, string $section, string $status, string $message): void
    {
        $result->sections[$section] = [
            'status' => $status,
            'message' => $message,
        ];
    }

    private function mapToCustomer(array $data, int $customerId): array
    {
        $columns = $this->getCustomerColumns();
        $attributes = [];

        if (in_array('identifier', $columns, true)) {
            $attributes['identifier'] = (string) $customerId;
        }

        if (in_array('first_name', $columns, true)) {
            $firstName = $this->firstString($data, ['first_name', 'firstname']);
            if ($firstName !== null) {
                $attributes['first_name'] = $firstName;
            }
        }

        if (in_array('last_name', $columns, true)) {
            $lastName = $this->firstString($data, ['last_name', 'lastname']);
            if ($lastName !== null) {
                $attributes['last_name'] = $lastName;
            }
        }

        if (in_array('email', $columns, true)) {
            $email = $this->firstString($data, ['email', 'e_mail']);
            if ($email !== null) {
                $attributes['email'] = $email;
            }
        }

        if (in_array('birthday', $columns, true)) {
            $birthday = $this->firstString($data, ['birthday', 'birthdate', 'date_of_birth']);
            if ($birthday !== null) {
                $attributes['birthday'] = $birthday;
            }
        }

        if (in_array('custom_fields', $columns, true)) {
            $customFields = [];
            $customFields[PimCustomerCustomFields::TYPE->value] = $this->firstString($data, ['type', 'customer_type']);
            $customFields[PimCustomerCustomFields::COMPANY_NAME->value] = $this->firstString($data, ['company_name', 'company']);
            $customFields[PimCustomerCustomFields::FISCAL_CODE->value] = $this->firstString($data, ['fiscal_code', 'tax_id']);
            $customFields[PimCustomerCustomFields::VAT_ID->value] = $this->firstString($data, ['vat_id', 'vat']);
            $customFields[PimCustomerCustomFields::AGENT_ID->value] = $this->firstString($data, ['agent_id']);
            $customFields[PimCustomerCustomFields::NET_FOLDER_DOCUMENTS->value] = $this->firstString($data, ['net_folder_documents']);
            $blockedValue = $this->firstValue($data, ['blocked', 'is_blocked']);
            $customFields[PimCustomerCustomFields::BLOCKED->value] = $this->normalizeBoolean($blockedValue);

            $attributes['custom_fields'] = $this->filterNullValues($customFields);
        }

        return $attributes;
    }

    private function mapToAddress(array $payload, string $type): array
    {
        $columns = $this->getAddressColumns();
        $data = $this->flatten($payload);
        $attributes = [];

        if (in_array('zipcode', $columns, true)) {
            $zip = $this->firstString($data, ['zipcode', 'zip', 'postal_code']);
            if ($zip !== null) {
                $attributes['zipcode'] = $zip;
            }
        }

        if (in_array('city', $columns, true)) {
            $city = $this->firstString($data, ['city', 'town']);
            if ($city !== null) {
                $attributes['city'] = $city;
            }
        }

        if (in_array('street', $columns, true)) {
            $street = $this->firstString($data, ['street', 'street_1', 'address', 'address_line1']);
            if ($street !== null) {
                $attributes['street'] = $street;
            }
        }

        if (in_array('additional_address_line_1', $columns, true)) {
            $additional = $this->firstString($data, ['street2', 'street_2', 'address_line2', 'additional_address']);
            if ($additional !== null) {
                $attributes['additional_address_line_1'] = $additional;
            }
        }

        if (in_array('phone_number', $columns, true)) {
            $phone = $this->firstString($data, ['phone_number', 'phone']);
            if ($phone !== null) {
                $attributes['phone_number'] = $phone;
            }
        }

        if (in_array('region', $columns, true)) {
            $region = $this->firstString($data, ['region', 'state']);
            if ($region !== null) {
                $attributes['region'] = $region;
            }
        }

        if (in_array('vat_id', $columns, true)) {
            $vat = $this->firstString($data, ['vat_id', 'vat']);
            if ($vat !== null) {
                $attributes['vat_id'] = $vat;
            }
        }

        if (in_array('first_name', $columns, true)) {
            $firstName = $this->firstString($data, ['first_name', 'firstname']);
            if ($firstName !== null) {
                $attributes['first_name'] = $firstName;
            }
        }

        if (in_array('last_name', $columns, true)) {
            $lastName = $this->firstString($data, ['last_name', 'lastname']);
            if ($lastName !== null) {
                $attributes['last_name'] = $lastName;
            }
        }

        if (in_array('country_id', $columns, true)) {
            $countryValue = $this->firstString($data, ['country_iso', 'country_code', 'country']);
            if ($countryValue !== null) {
                $countryId = $this->resolveCountryId($countryValue);
                if ($countryId !== null) {
                    $attributes['country_id'] = $countryId;
                }
            }
        }

        if (in_array('custom_fields', $columns, true)) {
            try {
                $attributes['custom_fields'] = json_encode([
                    'type' => $type,
                    'source' => 'ombis',
                ], JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $attributes['custom_fields'] = json_encode([
                    'type' => $type,
                    'source' => 'ombis',
                ]);
            }
        }

        return $this->filterNullValues($attributes);
    }

    private function mapToPaymentMethod(array $payload): array
    {
        $columns = $this->getPaymentColumns();
        $data = $this->flatten($payload);
        $attributes = [];

        if (in_array('name', $columns, true)) {
            $name = $this->firstString($data, ['name', 'display_name', 'title']);
            if ($name !== null) {
                $attributes['name'] = $name;
            }
        }

        if (in_array('technical_name', $columns, true)) {
            $technical = $this->firstString($data, ['technical_name', 'code', 'identifier', 'key']);
            if ($technical !== null) {
                $attributes['technical_name'] = $technical;
            }
        }

        return $this->filterNullValues($attributes);
    }

    private function readJsonOrNull(string $path, int $customerId): ?array
    {
        try {
            $contents = Storage::disk('local')->get($path);
        } catch (Throwable $exception) {
            Log::warning('Unable to read Ombis customer file.', [
                'customer_id' => $customerId,
                'path' => $path,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            Log::error('Unable to decode Ombis customer JSON.', [
                'customer_id' => $customerId,
                'path' => $path,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function flatten(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $normalizedKey = is_string($key) ? $this->normalizeKey($key) : (string) $key;
            if (is_array($value)) {
                if ($this->isAssoc($value)) {
                    $result = array_merge($result, $this->flatten($value));
                }

                continue;
            }

            $result[$normalizedKey] = $value;
        }

        return $result;
    }

    private function normalizeKey(string $key): string
    {
        $key = str_replace(['-', ' '], '_', $key);
        $key = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $key) ?? $key;

        return strtolower($key);
    }

    private function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function firstString(array $data, array $keys): ?string
    {
        $value = $this->firstValue($data, $keys);
        if ($value === null) {
            return null;
        }

        $string = is_scalar($value) ? (string) $value : null;
        if ($string === null || $string === '') {
            return null;
        }

        return trim($string);
    }

    private function firstValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return null;
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['1', 'true', 'yes'], true)) {
                return true;
            }
            if (in_array($lower, ['0', 'false', 'no'], true)) {
                return false;
            }
        }

        if (is_int($value)) {
            return $value === 1 ? true : ($value === 0 ? false : null);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function filterNullValues(array $values): array
    {
        return array_filter(
            $values,
            static fn ($value) => $value !== null
        );
    }

    private function resolveCountryId(string $value): ?string
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '') {
            return null;
        }

        if (array_key_exists($normalized, $this->countryCache)) {
            $cached = $this->countryCache[$normalized];

            return $cached === '' ? null : $cached;
        }

        $country = PimCountry::query()
            ->whereRaw('LOWER(iso) = ?', [strtolower($normalized)])
            ->orWhereRaw('LOWER(name) = ?', [strtolower($normalized)])
            ->first();

        $id = $country?->id;
        $this->countryCache[$normalized] = $id ?? '';

        return $id;
    }

    private function requiresCustomerFieldsMissing(array $attributes): bool
    {
        $required = ['identifier', 'email', 'custom_fields'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $attributes) || $attributes[$key] === null || $attributes[$key] === []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function getCustomerColumns(): array
    {
        if ($this->customerColumns === null) {
            $this->customerColumns = Schema::getColumnListing('pim_customers');
        }

        return $this->customerColumns;
    }

    /**
     * @return array<int, string>
     */
    private function getAddressColumns(): array
    {
        if ($this->addressColumns === null) {
            $this->addressColumns = Schema::getColumnListing('pim_customer_address');
        }

        return $this->addressColumns;
    }

    /**
     * @return array<int, string>
     */
    private function getPaymentColumns(): array
    {
        if ($this->paymentColumns === null) {
            $this->paymentColumns = Schema::getColumnListing('pim_payment_method');
        }

        return $this->paymentColumns;
    }
}
