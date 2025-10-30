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
        Storage::disk('local')->put($directory . '/shipping_address.json', $this->encodeJson($this->wrapPayload($this->shippingFields('ABHOL', 'Pickup'))));

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
        Storage::disk('local')->put($directory . '/billing_address.json', $this->encodeJson($this->wrapPayload($this->billingFields(
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
        Storage::disk('local')->put($directory . '/shipping_address.json', $this->encodeJson($this->wrapPayload($this->shippingFields('COURIER', 'Courier'))));
        Storage::disk('local')->put($directory . '/currency.json', $this->encodeJson($this->wrapPayload($this->currencyFields('EUR', 'Euro'))));
        Storage::disk('local')->put($directory . '/payment_method.json', '{invalid');

        $result = $this->app->make(CustomerImporter::class)->importOne(404);

        $this->assertNotSame([], $result->errors);
        $this->assertArrayHasKey('payment', $result->sections);
        $this->assertSame('error', $result->sections['payment']['status']);
    }

    public function test_import_is_idempotent(): void
    {
        PimCountry::query()->create(['name' => 'Italy', 'iso' => 'IT']);

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

    /**
     * @param array<string, mixed> $payloads
     */
    private function seedCustomerFiles(int $customerId, array $payloads): void
    {
        $directory = 'ombis_customers/upload/customer_' . $customerId;
        Storage::disk('local')->makeDirectory($directory);

        if (isset($payloads['billing'])) {
            Storage::disk('local')->put($directory . '/billing_address.json', $this->encodeJson($this->wrapPayload($payloads['billing'])));
        }

        if (isset($payloads['shipping'])) {
            Storage::disk('local')->put($directory . '/shipping_address.json', $this->encodeJson($this->wrapPayload($payloads['shipping'])));
        }

        if (isset($payloads['payment'])) {
            Storage::disk('local')->put($directory . '/payment_method.json', $this->encodeJson($this->wrapPayload($payloads['payment'])));
        }

        if (isset($payloads['currency'])) {
            Storage::disk('local')->put($directory . '/currency.json', $this->encodeJson($this->wrapPayload($payloads['currency'])));
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

    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
