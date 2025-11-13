<?php

declare(strict_types=1);

namespace Tests\Feature\Ombis;

use App\Enums\Pim\PimCustomerCustomFields;
use App\Models\Pim\Country\PimCountry;
use App\Models\Pim\Customer\PimCustomer;
use App\Models\Pim\Customer\PimCustomerAddress;
use App\Models\Pim\PaymentMethod\PimPaymentMethod;
use App\Services\Ombis\CustomerImporter;
use App\Services\Ombis\DTO\ImportResultDTO;
use App\Services\Ombis\DTO\ImportSummaryDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CustomerImporterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Log::spy();
    }

    public function test_imports_single_customer_successfully(): void
    {
        $country = PimCountry::query()->create([
            'name' => 'Italy',
            'iso' => 'IT',
        ]);

        $this->seedCustomerFiles(400, [
            'billing' => $this->billingFields(
                uuid: '5850f33f97ee4850bb57d4117ed5747c',
                name1: 'Karl Pedross AG',
                name2: 'Karin',
                street: 'Industriezone 1/c',
                zip: '39021',
                city: 'Latsch',
                countryIso: 'IT',
                email: 'karin.patscheider@pedross.com',
                vat: 'IT00223300211'
            ),
            'billing_references' => $this->billingReferencePayload('IT'),
            'shipping' => $this->shippingFields(code: 'ABHOL', name: 'Abholung Kunde'),
            'payment' => $this->paymentFields(code: 'UE', name: 'Überweisung'),
            'currency' => $this->currencyFields(iso: 'EUR', name: 'Euro'),
        ]);

        $importer = $this->app->make(CustomerImporter::class);
        $result = $importer->importOne(400);

        $this->assertInstanceOf(ImportResultDTO::class, $result);
        $this->assertSame([], $result->errors);
        $this->assertSame([], $result->warnings);
        $this->assertTrue($result->createdOrUpdated);
        $this->assertArrayHasKey('currency', $result->sections);
        $this->assertSame('success', $result->sections['currency']['status']);
        $this->assertArrayHasKey('shipping', $result->sections);
        $this->assertSame('preference synced', $result->sections['shipping']['message']);

        $customer = PimCustomer::query()->where('identifier', '400')->first();
        $this->assertNotNull($customer);
        $this->assertSame('Karin', $customer->first_name);
        $this->assertSame('Karl Pedross AG', $customer->last_name);
        $this->assertSame('karin.patscheider@pedross.com', $customer->email);
        $this->assertSame('Karl Pedross AG', $customer->custom_fields[PimCustomerCustomFields::COMPANY_NAME->value]);
        $this->assertSame('ABHOL', $customer->custom_fields[PimCustomerCustomFields::SHIPPING_METHOD_CODE->value]);
        $this->assertSame('EUR', $customer->custom_fields[PimCustomerCustomFields::CURRENCY_CODE->value]);
        $this->assertSame('UE', $customer->paymentMethod?->technical_name);
        $this->assertNotNull($customer->default_billing_address_id);
        $this->assertNull($customer->default_shipping_address_id);

        $address = PimCustomerAddress::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($address);
        $this->assertSame('5850f33f-97ee-4850-bb57-d4117ed5747c', $address->id);
        $this->assertSame('Industriezone 1/c', $address->street);
        $this->assertSame('39021', $address->zipcode);
        $this->assertSame('Latsch', $address->city);
        $this->assertSame($country->id, $address->country_id);
    }

    public function test_import_all_processes_multiple_customers(): void
    {
        PimCountry::query()->create(['name' => 'Italy', 'iso' => 'IT']);
        PimCountry::query()->create(['name' => 'Germany', 'iso' => 'DE']);

        $this->seedCustomerFiles(401, [
            'billing' => $this->billingFields(
                uuid: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                name1: 'Example Spa',
                name2: 'Elisa',
                street: 'Via Roma 1',
                zip: '00100',
                city: 'Roma',
                countryIso: 'IT',
                email: 'elisa@example.com',
                vat: 'IT12345678901'
            ),
            'billing_references' => $this->billingReferencePayload('IT'),
            'shipping' => $this->shippingFields(code: 'COURIER', name: 'Courier'),
            'payment' => $this->paymentFields(code: 'INV', name: 'Invoice'),
            'currency' => $this->currencyFields(iso: 'EUR', name: 'Euro'),
        ]);

        $this->seedCustomerFiles(402, [
            'billing' => $this->billingFields(
                uuid: 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
                name1: 'Beispiel GmbH',
                name2: 'Bernd',
                street: 'Musterstr. 5',
                zip: '10115',
                city: 'Berlin',
                countryIso: 'DE',
                email: 'bernd@example.de',
                vat: 'DE123456789'
            ),
            'billing_references' => $this->billingReferencePayload('DE'),
            'shipping' => $this->shippingFields(code: 'PICKUP', name: 'Abholung'),
            'payment' => $this->paymentFields(code: 'COD', name: 'Cash on Delivery'),
            'currency' => $this->currencyFields(iso: 'EUR', name: 'Euro'),
        ]);

        $summary = $this->app->make(CustomerImporter::class)->importAll();

        $this->assertInstanceOf(ImportSummaryDTO::class, $summary);
        $this->assertSame(2, $summary->total);
        $this->assertSame(2, $summary->success);
        $this->assertSame(0, $summary->partial);
        $this->assertSame(0, $summary->failed);
        $this->assertCount(2, $summary->details);
    }

    public function test_import_handles_missing_files(): void
    {
        $directory = 'ombis_customers/upload/customer_403';
        Storage::disk('local')->makeDirectory($directory);
        Storage::disk('local')->makeDirectory($directory . '/refs');
        Storage::disk('local')->put($directory . '/refs/shipping_address.json', $this->encodeJson($this->wrapPayload($this->shippingFields('ABHOL', 'Pickup'))));

        $result = $this->app->make(CustomerImporter::class)->importOne(403);

        $this->assertNotSame([], $result->warnings);
        $this->assertSame([], $result->errors);
        $this->assertArrayHasKey('billing', $result->sections);
        $this->assertSame('warning', $result->sections['billing']['status']);
    }

    public function test_import_handles_malformed_json(): void
    {
        PimCountry::query()->create(['name' => 'Italy', 'iso' => 'IT']);

        $directory = 'ombis_customers/upload/customer_404';
        Storage::disk('local')->makeDirectory($directory);
        Storage::disk('local')->makeDirectory($directory . '/refs');
        Storage::disk('local')->put($directory . '/refs/billing_address.json', $this->encodeJson($this->wrapPayload($this->billingFields(
            uuid: 'cccccccccccccccccccccccccccccccc',
            name1: 'Malformed Spa',
            name2: 'Mario',
            street: 'Via Milano 3',
            zip: '20100',
            city: 'Milano',
            countryIso: 'IT',
            email: 'mario@example.com',
            vat: 'IT98765432100'
        ))));
        Storage::disk('local')->put($directory . '/refs/billing_address_references.json', $this->encodeJson($this->billingReferencePayload('IT')));
        Storage::disk('local')->put($directory . '/refs/shipping_address.json', $this->encodeJson($this->wrapPayload($this->shippingFields('COURIER', 'Courier'))));
        Storage::disk('local')->put($directory . '/refs/currency.json', $this->encodeJson($this->wrapPayload($this->currencyFields('EUR', 'Euro'))));
        Storage::disk('local')->put($directory . '/refs/payment_method.json', '{invalid');

        $result = $this->app->make(CustomerImporter::class)->importOne(404);

        $this->assertNotSame([], $result->errors);
        $this->assertArrayHasKey('payment', $result->sections);
        $this->assertSame('error', $result->sections['payment']['status']);
    }

    public function test_import_is_idempotent(): void
    {
        $this->seedCustomerFiles(405, [
            'billing' => $this->billingFields(
                uuid: 'dddddddddddddddddddddddddddddddd',
                name1: 'Idempotent SRL',
                name2: 'Ida',
                street: 'Via Torino 7',
                zip: '10100',
                city: 'Torino',
                countryIso: 'IT',
                email: 'ida@example.com',
                vat: 'IT11122233344'
            ),
            'billing_references' => $this->billingReferencePayload('IT'),
            'shipping' => $this->shippingFields(code: 'COURIER', name: 'Courier'),
            'payment' => $this->paymentFields(code: 'BANK', name: 'Bank Transfer'),
            'currency' => $this->currencyFields(iso: 'EUR', name: 'Euro'),
        ]);

        $importer = $this->app->make(CustomerImporter::class);
        $first = $importer->importOne(405);
        $second = $importer->importOne(405);

        $this->assertSame([], $first->errors);
        $this->assertSame([], $second->errors);
        $this->assertSame(1, PimPaymentMethod::query()->count());

        $customer = PimCustomer::query()->where('identifier', '405')->first();
        $this->assertNotNull($customer);
        $this->assertSame('COURIER', $customer->custom_fields[PimCustomerCustomFields::SHIPPING_METHOD_CODE->value]);
        $this->assertSame(1, PimCustomerAddress::query()->where('customer_id', $customer->id)->count());
    }

    public function test_import_enriches_billing_fields_with_reference_payload(): void
    {
        PimCountry::query()->create(['name' => 'Italy', 'iso' => 'IT']);

        $billing = $this->billingFields(
            uuid: 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee',
            name1: 'Referenced Spa',
            name2: 'Rita',
            street: 'Via Firenze 10',
            zip: '',
            city: '',
            countryIso: '',
            email: 'rita@example.com',
            vat: 'IT55566677788'
        );
        unset($billing['Land.ISOCode']);
        unset($billing['Ort']);
        unset($billing['PLZ']);

        $this->seedCustomerFiles(406, [
            'billing' => $billing,
            'billing_references' => $this->billingReferencePayload('IT', region: 'Trentino', province: 'Bozen', city: 'Latsch', zip: '39021'),
            'shipping' => $this->shippingFields(code: 'COURIER', name: 'Courier'),
            'payment' => $this->paymentFields(code: 'CARD', name: 'Credit Card'),
            'currency' => $this->currencyFields(iso: 'EUR', name: 'Euro'),
        ]);

        $result = $this->app->make(CustomerImporter::class)->importOne(406);

        $this->assertSame([], $result->errors);
        $this->assertSame([], $result->warnings);

        $customer = PimCustomer::query()->where('identifier', '406')->first();
        $this->assertNotNull($customer);

        $address = PimCustomerAddress::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($address);
        $this->assertSame('Latsch', $address->city);
        $this->assertSame('39021', $address->zipcode);
        $this->assertNotNull($address->country_id);
    }

    public function test_import_enriches_shipping_fields_with_reference_payload(): void
    {
        PimCountry::query()->create(['name' => 'Italy', 'iso' => 'IT']);

        $billing = $this->billingFields(
            uuid: 'ffffffffffffffffffffffffffffffff',
            name1: 'Shipping Spa',
            name2: 'Sibylle',
            street: 'Via Verona 1',
            zip: '37100',
            city: 'Verona',
            countryIso: 'IT',
            email: 'sibylle@example.com',
            vat: 'IT00011122233'
        );

        $shipping = $this->billingFields(
            uuid: '11111111111111111111111111111111',
            name1: 'Shipping Spa',
            name2: 'Sibylle',
            street: 'Via Bolzano 5',
            zip: '',
            city: '',
            countryIso: '',
            email: 'sibylle@example.com',
            vat: 'IT00011122233'
        );
        unset($shipping['Land.ISOCode']);
        unset($shipping['PLZ']);
        unset($shipping['Ort']);

        $this->seedCustomerFiles(407, [
            'billing' => $billing,
            'billing_references' => $this->billingReferencePayload('IT'),
            'shipping' => $shipping,
            'shipping_references' => $this->billingReferencePayload('IT', region: 'Veneto', province: 'Verona', city: 'Bolzano', zip: '39100'),
            'payment' => $this->paymentFields(code: 'CARD', name: 'Credit Card'),
            'currency' => $this->currencyFields(iso: 'EUR', name: 'Euro'),
        ]);

        $result = $this->app->make(CustomerImporter::class)->importOne(407);

        $this->assertSame([], $result->errors);

        $customer = PimCustomer::query()->where('identifier', '407')->first();
        $this->assertNotNull($customer);
        $this->assertNotNull($customer->default_shipping_address_id);

        $shippingAddress = PimCustomerAddress::query()->find($customer->default_shipping_address_id);
        $this->assertNotNull($shippingAddress);
        $this->assertSame('Bolzano', $shippingAddress->city);
        $this->assertSame('39100', $shippingAddress->zipcode);
        $this->assertSame('Veneto', $shippingAddress->region);
        $this->assertNotNull($shippingAddress->country_id);
        $this->assertSame(2, PimCustomerAddress::query()->where('customer_id', $customer->id)->count());
    }

    /**
     * @param array<string, mixed> $payloads
     */
    private function seedCustomerFiles(int $customerId, array $payloads): void
    {
        $directory = 'ombis_customers/upload/customer_' . $customerId;
        $refsDirectory = $directory . '/refs';
        Storage::disk('local')->makeDirectory($directory);
        Storage::disk('local')->makeDirectory($refsDirectory);

        if (isset($payloads['billing'])) {
            Storage::disk('local')->put($refsDirectory . '/billing_address.json', $this->encodeJson($this->wrapPayload($payloads['billing'])));
        }

        if (isset($payloads['shipping'])) {
            Storage::disk('local')->put($refsDirectory . '/shipping_address.json', $this->encodeJson($this->wrapPayload($payloads['shipping'])));
        }

        if (isset($payloads['payment'])) {
            Storage::disk('local')->put($refsDirectory . '/payment_method.json', $this->encodeJson($this->wrapPayload($payloads['payment'])));
        }

        if (isset($payloads['currency'])) {
            Storage::disk('local')->put($refsDirectory . '/currency.json', $this->encodeJson($this->wrapPayload($payloads['currency'])));
        }

        if (isset($payloads['billing_references'])) {
            Storage::disk('local')->put($refsDirectory . '/billing_address_references.json', $this->encodeJson($payloads['billing_references']));
        }

        if (isset($payloads['shipping_references'])) {
            Storage::disk('local')->put($refsDirectory . '/shipping_address_references.json', $this->encodeJson($payloads['shipping_references']));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function billingFields(
        string $uuid,
        string $name1,
        string $name2,
        string $street,
        string $zip,
        string $city,
        string $countryIso,
        string $email,
        string $vat
    ): array {
        return [
            'UUID' => $uuid,
            'Name1' => $name1,
            'Name2' => $name2,
            'Strasse1' => $street,
            'PLZ' => $zip,
            'Ort' => $city,
            'Land.ISOCode' => $countryIso,
            'Telefon' => '+3900000000',
            'EMail' => $email,
            'Steuernummer' => $vat,
            'MwStNummer' => $vat,
            'UStIDNummer' => $vat,
            'Gesperrt' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function shippingFields(string $code, string $name, string $provider = 'Empfaenger'): array
    {
        return [
            'Code' => $code,
            'Name' => $name,
            'DisplayName' => $name,
            'TransportDurch' => $provider,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentFields(string $code, string $name): array
    {
        return [
            'Code' => $code,
            'Name' => $name,
            'DisplayName' => $name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function currencyFields(string $iso, string $name): array
    {
        return [
            'ISOCode' => $iso,
            'Name' => $name,
            'Symbol' => $iso,
        ];
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    private function wrapPayload(array $fields): array
    {
        return [
            'URI' => '/fake/resource',
            'Fields' => $fields,
        ];
    }

    private function billingReferencePayload(
        string $countryIso,
        string $region = 'Trentino-Südtirol',
        string $province = 'Bozen',
        ?string $city = null,
        ?string $zip = null
    ): array {
        $land = [
            'Fields' => [
                'ISOCode' => $countryIso,
                'DisplayName' => $countryIso,
            ],
        ];

        $regionPayload = [
            'Fields' => [
                'DisplayName' => $region,
            ],
        ];

        $provincePayload = [
            'Fields' => [
                'DisplayName' => $province,
            ],
        ];

        $cityFields = array_filter([
            'DisplayName' => $city,
            'PLZ' => $zip,
        ], static fn ($value) => $value !== null);

        $municipality = $cityFields === [] ? null : ['Fields' => $cityFields];

        return array_filter([
            'Land' => $land,
            'Region' => $regionPayload,
            'Provinz' => $provincePayload,
            'Gemeinde' => $municipality,
        ]);
    }

    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
