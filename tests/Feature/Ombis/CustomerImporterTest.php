<?php

declare(strict_types=1);

namespace Tests\Feature\Ombis;

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
        PimCountry::query()->create([
            'name' => 'Germany',
            'iso' => 'DE',
        ]);

        $this->seedCustomerFiles(100, [
            'billing' => $this->addressPayload('John', 'Doe', 'Main Street 1', '10115', 'Berlin', 'DE'),
            'shipping' => $this->addressPayload('John', 'Doe', 'Warehouse 5', '20095', 'Hamburg', 'DE'),
            'payment' => [
                'code' => 'prepaid',
                'name' => 'Prepaid',
            ],
        ]);

        $importer = $this->app->make(CustomerImporter::class);
        $result = $importer->importOne(100);

        $this->assertInstanceOf(ImportResultDTO::class, $result);
        $this->assertSame([], $result->errors);
        $this->assertSame([], $result->warnings);
        $this->assertTrue($result->createdOrUpdated);

        $customer = PimCustomer::query()->where('identifier', '100')->first();
        $this->assertNotNull($customer);
        $this->assertSame('john.doe@example.com', $customer->email);
        $this->assertSame('John', $customer->first_name);
        $this->assertSame('Doe', $customer->last_name);
        $this->assertSame('prepaid', $customer->paymentMethod?->technical_name);
        $this->assertNotNull($customer->default_billing_address_id);
        $this->assertNotNull($customer->default_shipping_address_id);

        $addresses = PimCustomerAddress::query()->where('customer_id', $customer->id)->get();
        $this->assertCount(2, $addresses);
        $this->assertTrue($addresses->contains('street', 'Main Street 1'));
        $this->assertTrue($addresses->contains('street', 'Warehouse 5'));
    }

    public function test_import_all_processes_multiple_customers(): void
    {
        PimCountry::query()->create([
            'name' => 'Germany',
            'iso' => 'DE',
        ]);

        $this->seedCustomerFiles(101, [
            'billing' => $this->addressPayload('Alice', 'Meyer', 'Allee 1', '50667', 'Köln', 'DE'),
            'shipping' => $this->addressPayload('Alice', 'Meyer', 'Depot 2', '80331', 'München', 'DE'),
            'payment' => [
                'code' => 'invoice',
                'name' => 'Invoice',
            ],
        ]);

        $this->seedCustomerFiles(102, [
            'billing' => $this->addressPayload('Bob', 'Schmidt', 'Ring 5', '01067', 'Dresden', 'DE'),
            'shipping' => $this->addressPayload('Bob', 'Schmidt', 'Hub 9', '04109', 'Leipzig', 'DE'),
            'payment' => [
                'code' => 'cod',
                'name' => 'Cash on Delivery',
            ],
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
        PimCountry::query()->create([
            'name' => 'Germany',
            'iso' => 'DE',
        ]);

        $directory = 'ombis_customers/upload/customer_103';
        Storage::disk('local')->makeDirectory($directory);
        Storage::disk('local')->put($directory . '/shipping_address.json', $this->encodeJson($this->addressPayload('Eva', 'Kurz', 'Logistics 7', '90402', 'Nürnberg', 'DE')));

        $result = $this->app->make(CustomerImporter::class)->importOne(103);

        $this->assertNotSame([], $result->warnings);
        $this->assertSame([], $result->errors);
        $this->assertArrayHasKey('billing', $result->sections);
        $this->assertSame('warning', $result->sections['billing']['status']);
    }

    public function test_import_handles_malformed_json(): void
    {
        PimCountry::query()->create([
            'name' => 'Germany',
            'iso' => 'DE',
        ]);

        $directory = 'ombis_customers/upload/customer_104';
        Storage::disk('local')->makeDirectory($directory);
        Storage::disk('local')->put($directory . '/billing_address.json', $this->encodeJson($this->addressPayload('Karl', 'Lang', 'Center 3', '01109', 'Dresden', 'DE')));
        Storage::disk('local')->put($directory . '/shipping_address.json', $this->encodeJson($this->addressPayload('Karl', 'Lang', 'Center 3', '01109', 'Dresden', 'DE')));
        Storage::disk('local')->put($directory . '/payment_method.json', '{invalid');

        $result = $this->app->make(CustomerImporter::class)->importOne(104);

        $this->assertNotSame([], $result->errors);
        $this->assertArrayHasKey('payment', $result->sections);
        $this->assertSame('error', $result->sections['payment']['status']);
    }

    public function test_import_is_idempotent(): void
    {
        PimCountry::query()->create([
            'name' => 'Germany',
            'iso' => 'DE',
        ]);

        $this->seedCustomerFiles(105, [
            'billing' => $this->addressPayload('Nina', 'Vogel', 'Route 10', '70173', 'Stuttgart', 'DE'),
            'shipping' => $this->addressPayload('Nina', 'Vogel', 'Route 10', '70173', 'Stuttgart', 'DE'),
            'payment' => [
                'code' => 'credit',
                'name' => 'Credit Card',
            ],
        ]);

        $importer = $this->app->make(CustomerImporter::class);
        $first = $importer->importOne(105);
        $second = $importer->importOne(105);

        $this->assertSame([], $first->errors);
        $this->assertSame([], $second->errors);
        $this->assertSame(1, PimPaymentMethod::query()->count());
        $customer = PimCustomer::query()->where('identifier', '105')->first();
        $this->assertNotNull($customer);
        $this->assertSame(2, PimCustomerAddress::query()->where('customer_id', $customer->id)->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function addressPayload(string $firstName, string $lastName, string $street, string $zip, string $city, string $countryIso): array
    {
        return [
            'customer' => [
                'id' => 100,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower($firstName) . '.' . strtolower($lastName) . '@example.com',
                'company_name' => $firstName . ' ' . $lastName . ' GmbH',
                'vat_id' => 'DE' . random_int(1000000, 9999999),
            ],
            'address' => [
                'street' => $street,
                'zip' => $zip,
                'city' => $city,
                'country_iso' => $countryIso,
            ],
        ];
    }

    private function seedCustomerFiles(int $customerId, array $payloads): void
    {
        $directory = 'ombis_customers/upload/customer_' . $customerId;
        Storage::disk('local')->makeDirectory($directory);

        if (isset($payloads['billing'])) {
            Storage::disk('local')->put($directory . '/billing_address.json', $this->encodeJson($payloads['billing']));
        }

        if (isset($payloads['shipping'])) {
            Storage::disk('local')->put($directory . '/shipping_address.json', $this->encodeJson($payloads['shipping']));
        }

        if (isset($payloads['payment'])) {
            Storage::disk('local')->put($directory . '/payment_method.json', $this->encodeJson($payloads['payment']));
        }
    }

    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
