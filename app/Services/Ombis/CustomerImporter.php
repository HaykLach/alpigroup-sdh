<?php

declare(strict_types=1);

namespace App\Services\Ombis;

use App\Enums\Pim\PimCustomerCustomFields;
use App\Enums\Pim\PimCustomerType;
use App\Models\Pim\Country\PimCountry;
use App\Models\Pim\Customer\PimCustomer;
use App\Models\Pim\Customer\PimCustomerAddress;
use App\Models\Pim\PaymentMethod\PimPaymentMethod;
use App\Models\Pim\PimLanguage;
use App\Models\Pim\Region\PimRegion;
use App\Models\Pim\Region\PimRegionTranslation;
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
    private const LOG_CHANNEL = 'ombis-sdk';
    private const BASE_PATH = 'ombis_customers/upload';
    private const DETAILS_FILE = 'customer-details.json';
    private const BILLING_FILE = 'refs/billing_address.json';
    private const BILLING_REFERENCES_FILE = 'refs/billing_address_references.json';
    private const SHIPPING_FILE = 'refs/shipping_address.json';
    private const SHIPPING_REFERENCES_FILE = 'refs/shipping_address_references.json';
    private const PAYMENT_FILE = 'refs/payment_method.json';
    private const CURRENCY_FILE = 'refs/currency.json';
    private const LEGAL_INFORMATION_FILE = 'refs/legal_information.json';

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

    /**
     * @var array<string, string>
     */
    private array $regionCache = [];

    /**
     * @var array<string, PimLanguage>|null
     */
    private ?array $languagesByLocale = null;

    public function __construct(private readonly DatabaseManager $databaseManager)
    {
    }

    public function importOne(int $customerId): ImportResultDTO
    {
        $result = new ImportResultDTO(customerId: $customerId);

        $disk = Storage::disk('local');
        $directory = self::BASE_PATH . '/customer_' . $customerId;

        if (! $disk->directoryExists($directory)) {
            $message = sprintf('Customer directory not found at %s.', $directory);
            $this->logWarning('Ombis customer import directory missing.', [
                'customer_id' => $customerId,
                'path' => $directory,
            ]);
            $result->warnings[] = $message;
            $this->setSection($result, 'billing', 'warning', 'missing directory');
            $this->setSection($result, 'shipping', 'warning', 'missing directory');
            $this->setSection($result, 'payment', 'warning', 'missing directory');
            $this->setSection($result, 'currency', 'warning', 'missing directory');

            return $result;
        }

        $detailsFields = $this->loadCustomerDetails($result, $customerId, $directory . '/' . self::DETAILS_FILE);
        $customerNumber = $this->getValue($detailsFields['Nummer'] ?? null);

        if ($customerNumber === null) {
            $warning = sprintf('Customer %d missing Nummer. Skipping import.', $customerId);
            $result->warnings[] = $warning;
            $this->logWarning('Ombis customer missing Nummer. Skipping import.', [
                'customer_id' => $customerId,
                'path' => $directory . '/' . self::DETAILS_FILE,
            ]);

            $this->setSection($result, 'billing', 'warning', 'missing customer number');
            $this->setSection($result, 'shipping', 'warning', 'missing customer number');
            $this->setSection($result, 'payment', 'warning', 'missing customer number');
            $this->setSection($result, 'currency', 'warning', 'missing customer number');

            return $result;

        }

        $billingPayload = $this->loadJsonForSection($result, $customerId, $directory . '/' . self::BILLING_FILE, 'billing');
        $billingReferencesPayload = $this->loadAddressReferences($result, $customerId, $directory . '/' . self::BILLING_REFERENCES_FILE);
        $shippingPayload = $this->loadJsonForSection($result, $customerId, $directory . '/' . self::SHIPPING_FILE, 'shipping');
        $shippingReferencesPayload = $this->loadAddressReferences($result, $customerId, $directory . '/' . self::SHIPPING_REFERENCES_FILE);
        $paymentPayload = $this->loadJsonForSection($result, $customerId, $directory . '/' . self::PAYMENT_FILE, 'payment');
        $currencyPayload = $this->loadJsonForSection($result, $customerId, $directory . '/' . self::CURRENCY_FILE, 'currency');
        $legalInformationPayload = $this->loadJsonForSection($result, $customerId, $directory . '/' . self::LEGAL_INFORMATION_FILE, 'legal');

        $billingFields = $this->extractFields($billingPayload);
        $billingReferenceFields = $this->extractReferenceFields($billingReferencesPayload);
        if ($billingReferenceFields !== []) {
            $billingFields = $this->mergeAddressFieldsWithReferences($billingFields, $billingReferenceFields);
        }
        $legalInformationFields = $this->extractFields($legalInformationPayload);
        $shippingFields = $this->extractFields($shippingPayload);
        $shippingReferenceFields = $this->extractReferenceFields($shippingReferencesPayload);
        if ($shippingReferenceFields !== [] && $shippingFields !== null) {
            $shippingFields = $this->mergeAddressFieldsWithReferences($shippingFields, $shippingReferenceFields);
        }
        $paymentFields = $this->extractFields($paymentPayload);
        $currencyFields = $this->extractFields($currencyPayload);

        $legalEmail = $this->getValue($legalInformationFields['KommunikationsinfoEC'] ?? null);

        if ($legalEmail === null) {
            $warning = sprintf('Customer %d missing email. Skipping import.', $customerId);
            $result->warnings[] = $warning;
            $this->logWarning('Ombis customer missing email. Skipping import.', [
                'customer_id' => $customerId,
                'path' => $directory . '/' . self::LEGAL_INFORMATION_FILE,
            ]);

            $this->setSection($result, 'billing', 'warning', 'missing email');
            $this->setSection($result, 'shipping', 'warning', 'missing email');
            $this->setSection($result, 'payment', 'warning', 'missing email');
            $this->setSection($result, 'currency', 'warning', 'missing email');
            $this->setSection($result, 'legal', 'warning', 'missing email');

            return $result;
        }

        try {
            $this->databaseManager->connection()->transaction(function () use ($customerId, $billingFields, $shippingFields, $paymentFields, $currencyFields, $result, $customerNumber, $legalEmail): void {
                $customer = $this->upsertCustomer($customerId, $billingFields, $result, $customerNumber, $legalEmail);

                if ($customer === null) {
                    if ($billingFields !== null) {
                        $this->setSection($result, 'billing', 'warning', 'customer missing');
                    }
                    if ($shippingFields !== null) {
                        $this->setSection($result, 'shipping', 'warning', 'customer missing');
                    }
                    if ($paymentFields !== null) {
                        $this->setSection($result, 'payment', 'warning', 'customer missing');
                    }
                    if ($currencyFields !== null) {
                        $this->setSection($result, 'currency', 'warning', 'customer missing');
                    }

                    return;
                }

                if ($billingFields !== null) {
                    $this->syncAddress($customer, $billingFields, 'billing', $result);
                }

                if ($shippingFields !== null) {
                    if ($this->isValidAddress($shippingFields)) {
                        $this->syncAddress($customer, $shippingFields, 'shipping', $result);
                    } else {
                        $this->syncShippingPreference($customer, $shippingFields, $result);
                    }
                }

                if ($paymentFields !== null) {
                    $this->upsertPaymentMethod($customer, $paymentFields, $result);
                }

                if ($currencyFields !== null) {
                    $this->applyCurrency($customer, $currencyFields, $result);
                }
            });
        } catch (Throwable $exception) {
            $this->logError('Failed to import Ombis customer.', [
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
        if (! $disk->directoryExists(self::BASE_PATH)) {
            return new ImportSummaryDTO(total: 0);
        }

        $directories = $disk->directories(self::BASE_PATH);
        $customerIds = [];
        foreach ($directories as $directory) {
            $name = basename($directory);
            if (! Str::startsWith($name, 'customer_')) {
                continue;
            }

            $idPart = Str::after($name, 'customer_');
            if ($idPart === '' || ! ctype_digit($idPart)) {
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

    private function upsertCustomer(int $customerId, ?array $billingFields, ImportResultDTO $result, string $customerNumber, string $email): ?PimCustomer
    {
        /** @var PimCustomer $existing */
        $existing = PimCustomer::query()->where('identifier', $customerNumber)->first();
        $attributes = $this->mapToCustomer($customerId, $billingFields, $customerNumber, $email);

        $missingFields = $this->missingCustomerFields($attributes);
        if ($existing === null && $missingFields !== []) {
            $result->warnings[] = sprintf('Customer %d missing required fields. Skipping customer creation.', $customerId);
            $this->logWarning('Customer missing required fields.', [
                'customer_id' => $customerId,
                'missing_fields' => $missingFields,
            ]);

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
            $hasChanges = $this->mergeCustomerCustomFields($existing, $customFields) || $hasChanges;
        }

        if ($hasChanges) {
            $existing->save();
            $result->createdOrUpdated = true;
            $result->messages[] = sprintf('Customer %d updated.', $customerId);
        }

        return $existing;
    }

    private function mapToCustomer(int $customerId, ?array $billingFields, string $customerNumber, string $email): array
    {
        $columns = $this->getCustomerColumns();
        $attributes = [];

        if (in_array('identifier', $columns, true)) {
            $attributes['identifier'] = $customerNumber;
        }

        if ($billingFields !== null) {
            if (in_array('first_name', $columns, true)) {
                $firstName = $this->getValue($billingFields['Name1'] ?? $billingFields['Name2'] ?? null);
                if ($firstName !== null) {
                    $attributes['first_name'] = $firstName;
                }
            }

            if (in_array('last_name', $columns, true)) {
                $lastName = $this->getValue($billingFields['Name2'] ?? null);
                if (!$lastName) {
                    $lastName = '&nbsp;';
                }

                $attributes['last_name'] = $lastName;
            }
        }

        if (in_array('email', $columns, true)) {
            $attributes['email'] = $email;
        }

        if (in_array('custom_fields', $columns, true)) {
            $customFields = [
                PimCustomerCustomFields::TYPE->value => PimCustomerType::CUSTOMER->value,
            ];

            if ($billingFields !== null) {
                $customFields[PimCustomerCustomFields::COMPANY_NAME->value] = $this->getValue($billingFields['Name1'] ?? null);
                $customFields[PimCustomerCustomFields::FISCAL_CODE->value] = $this->getValue($billingFields['Steuernummer'] ?? null);
                $vat = $this->getValue($billingFields['UStIDNummer'] ?? $billingFields['MwStNummer'] ?? null);
                $customFields[PimCustomerCustomFields::VAT_ID->value] = $vat;
                $blocked = $this->normalizeBoolean($billingFields['Gesperrt'] ?? null);
                $customFields[PimCustomerCustomFields::BLOCKED->value] = $blocked;
            }

            $filtered = $this->filterNullValues($customFields);
            if ($filtered !== []) {
                $attributes['custom_fields'] = $filtered;
            }
        }

        return $attributes;
    }

    private function syncAddress(PimCustomer $customer, array $fields, string $type, ImportResultDTO $result): void
    {
        $attributes = $this->mapToAddress($fields, $type);
        $attributes['customer_id'] = $customer->id;

        $requiredKeys = ['street', 'city', 'zipcode'];
        $missing = [];
        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $attributes)) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            $message = sprintf('%s address missing required fields: %s.', ucfirst($type), implode(', ', $missing));
            $result->warnings[] = $message;
            $this->logWarning('Customer address missing required fields.', [
                'customer_id' => $customer->id,
                'address_type' => $type,
                'missing_fields' => $missing,
            ]);
            $this->setSection($result, $type, 'warning', 'missing fields');

            return;
        }

        $data = $attributes;
        $search = [];

        if (array_key_exists('id', $attributes)) {
            $search['id'] = $attributes['id'];
        } else {
            $search['customer_id'] = $customer->id;

            if (isset($attributes['street'], $attributes['zipcode'])) {
                $search['street'] = $attributes['street'];
                $search['zipcode'] = $attributes['zipcode'];
            }
        }

        if (! array_key_exists('id', $attributes)) {
            unset($data['id']);
        }

        $address = PimCustomerAddress::query()->updateOrCreate($search, $data);

        $columns = $this->getCustomerColumns();
        if ($type === 'billing') {
            if (in_array('default_billing_address_id', $columns, true)) {
                $customer->default_billing_address_id = $address->id;
            }

            if (
                in_array('default_shipping_address_id', $columns, true)
                && $customer->default_shipping_address_id === null
            ) {
                $customer->default_shipping_address_id = $address->id;
            }
        }

        if ($type === 'shipping') {
            if (in_array('default_shipping_address_id', $columns, true)) {
                $customer->default_shipping_address_id = $address->id;
            }

            if (
                in_array('default_billing_address_id', $columns, true)
                && $customer->default_billing_address_id === null
            ) {
                $customer->default_billing_address_id = $address->id;
            }
        }

        if (in_array('default_address_id', $columns, true) && $customer->default_address_id === null) {
            $customer->default_address_id = $address->id;
        }

        $customer->save();

        $result->createdOrUpdated = true;
        $result->messages[] = sprintf('%s address synced.', ucfirst($type));
        $this->setSection($result, $type, 'success', 'synced');
    }

    private function syncShippingPreference(PimCustomer $customer, array $fields, ImportResultDTO $result): void
    {
        $updates = $this->filterNullValues([
            PimCustomerCustomFields::SHIPPING_METHOD_CODE->value => $this->getValue($fields['Code'] ?? null),
            PimCustomerCustomFields::SHIPPING_METHOD_NAME->value => $this->getValue($fields['Name'] ?? $fields['DisplayName'] ?? null),
            PimCustomerCustomFields::SHIPPING_METHOD_PROVIDER->value => $this->getValue($fields['TransportDurch'] ?? null),
        ]);

        if ($updates === []) {
            $result->warnings[] = 'Shipping reference missing recognizable fields.';
            $this->logWarning('Shipping reference missing recognizable fields.', [
                'customer_id' => $customer->id,
            ]);
            $this->setSection($result, 'shipping', 'warning', 'no data');

            return;
        }

        if ($this->mergeCustomerCustomFields($customer, $updates)) {
            $result->createdOrUpdated = true;
            $result->messages[] = 'Shipping preference synced.';
        }

        $this->setSection($result, 'shipping', 'success', 'preference synced');
    }

    private function applyCurrency(PimCustomer $customer, array $fields, ImportResultDTO $result): void
    {
        $updates = $this->filterNullValues([
            PimCustomerCustomFields::CURRENCY_CODE->value => $this->getValue($fields['ISOCode'] ?? null),
            PimCustomerCustomFields::CURRENCY_NAME->value => $this->getValue($fields['Name'] ?? $fields['DisplayName'] ?? null),
        ]);

        if ($updates === []) {
            $result->warnings[] = 'Currency reference missing recognizable fields.';
            $this->logWarning('Currency reference missing recognizable fields.', [
                'customer_id' => $customer->id,
            ]);
            $this->setSection($result, 'currency', 'warning', 'no data');

            return;
        }

        if ($this->mergeCustomerCustomFields($customer, $updates)) {
            $result->createdOrUpdated = true;
            $result->messages[] = 'Currency preference synced.';
        }

        $this->setSection($result, 'currency', 'success', 'synced');
    }

    private function upsertPaymentMethod(PimCustomer $customer, array $fields, ImportResultDTO $result): void
    {
        $attributes = $this->mapToPaymentMethod($fields);
        if ($attributes === []) {
            $result->warnings[] = 'Payment method missing required data.';
            $this->logWarning('Payment method missing required data.', [
                'customer_id' => $customer->id,
            ]);
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
            $this->logWarning('Payment method missing identifier.', [
                'customer_id' => $customer->id,
                'payment_fields' => $fields,
            ]);
            $this->setSection($result, 'payment', 'warning', 'missing identifier');

            return;
        }

        $paymentMethod = PimPaymentMethod::query()->updateOrCreate($unique, $attributes);

        $columns = $this->getCustomerColumns();
        if (in_array('default_payment_method', $columns, true)) {
            $customer->default_payment_method = $paymentMethod->id;
            $customer->save();
        }

        $metadata = $this->filterNullValues([
            PimCustomerCustomFields::PAYMENT_METHOD_CODE->value => $this->getValue($fields['Code'] ?? null),
            PimCustomerCustomFields::PAYMENT_METHOD_NAME->value => $this->getValue($fields['Name'] ?? $fields['DisplayName'] ?? null),
        ]);

        if ($metadata !== []) {
            if ($this->mergeCustomerCustomFields($customer, $metadata)) {
                $result->createdOrUpdated = true;
            }
        }

        $result->createdOrUpdated = true;
        $result->messages[] = 'Payment method synced.';
        $this->setSection($result, 'payment', 'success', 'synced');
    }

    private function mapToPaymentMethod(array $fields): array
    {
        $columns = $this->getPaymentColumns();
        $attributes = [];

        if (in_array('name', $columns, true)) {
            $name = $this->getValue($fields['Name'] ?? $fields['DisplayName'] ?? null);
            if ($name !== null) {
                $attributes['name'] = $name;
            }
        }

        if (in_array('technical_name', $columns, true)) {
            $technical = $this->getValue($fields['Code'] ?? null);
            if ($technical !== null) {
                $attributes['technical_name'] = $technical;
            }
        }

        return $this->filterNullValues($attributes);
    }

    private function loadCustomerDetails(ImportResultDTO $result, int $customerId, string $path): ?array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            $message = sprintf('%s missing for customer %d.', basename($path), $customerId);
            $result->warnings[] = $message;
            $this->logWarning('Ombis customer details file missing.', [
                'customer_id' => $customerId,
                'path' => $path,
            ]);

            return null;
        }

        $payload = $this->readJsonOrNull($path, $customerId);
        if ($payload === null) {
            $result->warnings[] = sprintf('Unable to read %s for customer %d.', basename($path), $customerId);

            return null;
        }

        $fields = $this->extractFields($payload);
        if ($fields === null) {
            $result->warnings[] = sprintf('Invalid customer details structure for %d.', $customerId);

            return null;
        }

        return $fields;
    }

    private function loadJsonForSection(ImportResultDTO $result, int $customerId, string $path, string $section): ?array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            $message = sprintf('%s file missing for customer %d.', basename($path), $customerId);
            $this->logWarning('Ombis customer import file missing.', [
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

    private function loadAddressReferences(ImportResultDTO $result, int $customerId, string $path): ?array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            return null;
        }

        $payload = $this->readJsonOrNull($path, $customerId);
        if ($payload === null) {
            $result->warnings[] = sprintf('Unable to load %s for customer %d.', basename($path), $customerId);
        }

        return $payload;
    }

    private function extractReferenceFields(?array $payload): array
    {
        if ($payload === null) {
            return [];
        }

        $fields = [];

        $land = $this->extractFields($payload['Land'] ?? null);
        if ($land !== null) {
            $iso = $this->getValue($land['ISOCode'] ?? null);
            if ($iso !== null) {
                $fields['Land.ISOCode'] = $iso;
            }

            $name = $this->getValue($land['DisplayName'] ?? $land['Name'] ?? null);
            if ($name !== null) {
                $fields['Land.Name'] = $name;
            }
        }

        $region = $this->extractFields($payload['Region'] ?? null);
        if ($region !== null) {
            $fields['RegionFields'] = $region;
            $regionName = $this->getValue($region['DisplayName'] ?? $region['Name'] ?? null);
            if ($regionName !== null) {
                $fields['Region'] = $regionName;
            }
        }

        $province = $this->extractFields($payload['Provinz'] ?? null);
        if ($province !== null) {
            $provinceName = $this->getValue($province['DisplayName'] ?? $province['Name'] ?? null);
            if ($provinceName !== null) {
                $fields['Provinz'] = $provinceName;
            }
        }

        $municipality = $this->extractFields($payload['Gemeinde'] ?? null);
        if ($municipality !== null) {
            $city = $this->getValue($municipality['DisplayName'] ?? $municipality['Name'] ?? null);
            if ($city !== null) {
                $fields['Ort'] = $city;
            }

            $zip = $this->getValue($municipality['PLZ'] ?? null);
            if ($zip !== null) {
                $fields['PLZ'] = $zip;
            }
        }

        return $fields;
    }

    private function mergeAddressFieldsWithReferences(?array $billingFields, array $referenceFields): ?array
    {
        if ($billingFields === null) {
            return $referenceFields === [] ? null : $referenceFields;
        }

        foreach ($referenceFields as $key => $value) {
            $current = $billingFields[$key] ?? null;
            if ($this->getValue($current) === null) {
                $billingFields[$key] = $value;
            }
        }

        return $billingFields;
    }

    private function resolveRegionIdFromFields(?array $regionFields): ?string
    {
        if ($regionFields === null) {
            return null;
        }

        $name = $this->getValue($regionFields['DisplayName'] ?? $regionFields['Name'] ?? null);
        $externalId = $this->getValue($regionFields['ID'] ?? null);
        $code = $this->getValue($regionFields['Code'] ?? null);

        $cacheKey = $externalId ?? $code ?? $name;

        if ($cacheKey !== null && array_key_exists($cacheKey, $this->regionCache)) {
            return $this->regionCache[$cacheKey];
        }

        if ($externalId === null) {
            if ($code !== null) {
                $externalId = 'code_' . $code;
            } elseif ($name !== null) {
                $externalId = 'name_' . md5($name);
            } else {
                return null;
            }
        }

        $attributes = $this->filterNullValues([
            'external_id' => $externalId,
            'code' => $code,
            'display_name' => $name,
        ]);

        $region = PimRegion::query()->updateOrCreate(['external_id' => $externalId], $attributes);

        $this->syncRegionTranslations($region, $regionFields);

        if ($cacheKey !== null) {
            $this->regionCache[$cacheKey] = $region->id;
        } else {
            $this->regionCache[$externalId] = $region->id;
        }

        return $region->id;
    }

    private function syncRegionTranslations(PimRegion $region, array $regionFields): void
    {
        foreach ($regionFields as $key => $value) {
            if (! is_string($key) || ! Str::startsWith($key, 'Name_')) {
                continue;
            }

            $languageCode = substr($key, 5);
            $languageId = $this->languageIdForSuffix($languageCode);
            $name = $this->getValue(is_scalar($value) ? (string) $value : null);

            if ($languageId === null || $name === null) {
                continue;
            }

            PimRegionTranslation::query()->updateOrCreate(
                [
                    'pim_region_id' => $region->id,
                    'language_id' => $languageId,
                ],
                ['name' => $name]
            );
        }
    }

    private function languageIdForSuffix(?string $suffix): ?string
    {
        if ($suffix === null || $suffix === '') {
            return null;
        }

        $suffix = strtolower($suffix);

        foreach ($this->getLanguagesByLocale() as $code => $language) {
            $normalized = strtolower($code);
            if ($normalized === $suffix || Str::startsWith($normalized, $suffix . '-') || Str::startsWith($normalized, $suffix)) {
                return $language->id;
            }
        }

        return null;
    }

    /**
     * @return array<string, PimLanguage>
     */
    private function getLanguagesByLocale(): array
    {
        if ($this->languagesByLocale === null) {
            $this->languagesByLocale = PimLanguage::getAllWithLocalKeyedByCode()
                ->mapWithKeys(static fn (PimLanguage $language, string $code) => [strtolower($code) => $language])
                ->all();
        }

        return $this->languagesByLocale;
    }

    private function isValidAddress(array $fields): bool
    {
        $street = $this->getValue($fields['Strasse1'] ?? null);
        $zip = $this->getValue($fields['PLZ'] ?? null);
        $city = $this->getValue($fields['Ort'] ?? null);

        return $street !== null && $zip !== null && $city !== null;
    }

    private function mapToAddress(array $fields, string $type): array
    {
        $columns = $this->getAddressColumns();
        $attributes = [];

        if (in_array('id', $columns, true)) {
            $id = $this->formatUuid($fields['UUID'] ?? null);
            if ($id !== null) {
                $attributes['id'] = $id;
            }
        }

        if (in_array('first_name', $columns, true)) {
            $firstName = $this->getValue($fields['Name2'] ?? $fields['Name1'] ?? null);
            if ($firstName !== null) {
                $attributes['first_name'] = $firstName;
            }
        }

        if (in_array('last_name', $columns, true)) {
            $lastName = $this->getValue($fields['Name1'] ?? $fields['Name2'] ?? null);
            if ($lastName !== null) {
                $attributes['last_name'] = $lastName;
            }
        }

        if (in_array('zipcode', $columns, true)) {
            $zip = $this->getValue($fields['PLZ'] ?? null);
            if ($zip !== null) {
                $attributes['zipcode'] = $zip;
            }
        }

        if (in_array('city', $columns, true)) {
            $city = $this->getValue($fields['Ort'] ?? null);
            if ($city !== null) {
                $attributes['city'] = $city;
            }
        }

        if (in_array('street', $columns, true)) {
            $street = $this->getValue($fields['Strasse1'] ?? null);
            if ($street !== null) {
                $attributes['street'] = $street;
            }
        }

        if (in_array('additional_address_line_1', $columns, true)) {
            $additional = $this->getValue($fields['Strasse2'] ?? null);
            if ($additional !== null) {
                $attributes['additional_address_line_1'] = $additional;
            }
        }

        if (in_array('phone_number', $columns, true)) {
            $phone = $this->getValue($fields['Telefon'] ?? $fields['Festnetztelefon'] ?? $fields['Mobiltelefon'] ?? null);
            if ($phone !== null) {
                $attributes['phone_number'] = $phone;
            }
        }

        if (in_array('region_id', $columns, true)) {
            $region = $this->resolveRegionIdFromFields($fields['RegionFields'] ?? null);
            if ($region !== null) {
                $attributes['region_id'] = $region;
            }
        }

        if (in_array('vat_id', $columns, true)) {
            $vat = $this->getValue($fields['UStIDNummer'] ?? $fields['MwStNummer'] ?? null);
            if ($vat !== null) {
                $attributes['vat_id'] = $vat;
            }
        }

        if (in_array('country_id', $columns, true)) {
            $iso = $this->getValue($fields['Land.ISOCode'] ?? $fields['CountryISO'] ?? null);
            if ($iso !== null) {
                $countryId = $this->resolveCountryId($iso);
                if ($countryId !== null) {
                    $attributes['country_id'] = $countryId;
                }
            }
        }

        if (in_array('custom_fields', $columns, true)) {
            $customFields = $this->encodeCustomFields([
                'status' => $this->getValue($fields['Status'] ?? null),
                'notes' => $this->getValue($fields['Bemerkungen'] ?? null),
                'raw_id' => $this->getValue(isset($fields['ID']) ? (string) $fields['ID'] : null),
                'type' => $type,
            ]);

            if ($customFields !== null) {
                $attributes['custom_fields'] = $customFields;
            }
        }

        return $this->filterNullValues($attributes);
    }

    private function extractFields(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $fields = $payload['Fields'] ?? $payload;
        return is_array($fields) ? $fields : null;
    }

    private function readJsonOrNull(string $path, int $customerId): ?array
    {
        try {
            $contents = Storage::disk('local')->get($path);
        } catch (Throwable $exception) {
            $this->logWarning('Unable to read Ombis customer file.', [
                'customer_id' => $customerId,
                'path' => $path,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->logError('Unable to decode Ombis customer JSON.', [
                'customer_id' => $customerId,
                'path' => $path,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<int, string>
     */
    private function missingCustomerFields(array $attributes): array
    {
        $required = ['identifier', 'email', 'custom_fields'];
        $missing = [];
        foreach ($required as $key) {
            if (! array_key_exists($key, $attributes) || $attributes[$key] === null || $attributes[$key] === []) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    private function logWarning(string $message, array $context = []): void
    {
        Log::channel(self::LOG_CHANNEL)->warning($message, $context);
    }

    private function logError(string $message, array $context = []): void
    {
        Log::channel(self::LOG_CHANNEL)->error($message, $context);
    }

    private function getValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }

        if (is_scalar($value)) {
            $string = (string) $value;

            return $string === '' ? null : $string;
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
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
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

    private function encodeCustomFields(array $fields): ?string
    {
        $filtered = $this->filterNullValues($fields);
        if ($filtered === []) {
            return null;
        }

        try {
            return json_encode($filtered, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }

    private function formatUuid(mixed $value): ?string
    {
        $raw = $this->getValue($value);
        if ($raw === null) {
            return null;
        }

        $normalized = strtolower(str_replace('-', '', $raw));
        if (strlen($normalized) !== 32) {
            return Str::isUuid($raw) ? strtolower($raw) : null;
        }

        return substr($normalized, 0, 8) . '-' .
            substr($normalized, 8, 4) . '-' .
            substr($normalized, 12, 4) . '-' .
            substr($normalized, 16, 4) . '-' .
            substr($normalized, 20);
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

    private function mergeCustomerCustomFields(PimCustomer $customer, array $values): bool
    {
        $updates = $this->filterNullValues($values);
        if ($updates === []) {
            return false;
        }

        $current = $customer->custom_fields ?? [];
        $changed = false;

        foreach ($updates as $key => $value) {
            if (! array_key_exists($key, $current) || $current[$key] !== $value) {
                $current[$key] = $value;
                $changed = true;
            }
        }

        if ($changed) {
            $customer->custom_fields = $current;
            $customer->save();
        }

        return $changed;
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
