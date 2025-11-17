<?php

namespace App\Services\Export;

use App\Contracts\Customer\CustomerRepositoryInterface;
use App\Models\Pim\Customer\PimCustomer;
use App\Models\Pim\Customer\PimCustomerAddress;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use SmartDato\Shopware6\App\Models\Shopware6Address\Shopware6CustomerAddressExtension;
use SmartDato\Shopware6\App\Models\Shopware6Customers\Shopware6CustomersExtension;
use SmartDato\Shopware6\Components\BaseManager;
use SmartDato\Shopware6\Contracts\Address\AddressManagerInterface;
use SmartDato\Shopware6\Contracts\Customer\CustomerExportManagerInterface;
use SmartDato\Shopware6\Contracts\Customer\CustomerExportProcessorInterface;
use SmartDato\Shopware6\Contracts\Customer\CustomerServiceInterface;
use SmartDato\Shopware6\Contracts\CustomerGroup\CustomerGroupManagerInterface;
use SmartDato\Shopware6\Contracts\PaymentMethod\PaymentMethodManagerInterface;
use SmartDato\Shopware6\Contracts\SalesChannel\SalesChannelManagerInterface;
use SmartDato\Shopware6\Contracts\Salutation\SalutationManagerInterface;

class PimCustomerExportService extends BaseManager implements CustomerExportManagerInterface
{
    protected const SW_CUSTOMER_CACHE_NAME = 'swCustomer';

    /** @var CustomerRepositoryInterface */
    protected CustomerRepositoryInterface $customerRepository;

    /** @var PaymentMethodManagerInterface */
    protected PaymentMethodManagerInterface $paymentMethodManager;

    /** @var CustomerGroupManagerInterface */
    protected CustomerGroupManagerInterface $customerGroupManager;

    /** @var AddressManagerInterface */
    protected AddressManagerInterface $addressManager;

    /** @var CustomerServiceInterface */
    protected CustomerServiceInterface $customerService;

    /** @var SalutationManagerInterface */
    protected SalutationManagerInterface $salutationManager;

    /** @var SalesChannelManagerInterface */
    protected SalesChannelManagerInterface $salesChannelManager;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        PaymentMethodManagerInterface $paymentMethodManager,
        CustomerGroupManagerInterface $customerGroupManager,
        AddressManagerInterface $addressManager,
        CustomerServiceInterface $customerService,
        SalutationManagerInterface $salutationManager,
        SalesChannelManagerInterface $salesChannelManager
    ) {
        parent::__construct();

        $this->customerRepository = $customerRepository;
        $this->paymentMethodManager = $paymentMethodManager;
        $this->customerGroupManager = $customerGroupManager;
        $this->addressManager = $addressManager;
        $this->customerService = $customerService;
        $this->salutationManager = $salutationManager;
        $this->salesChannelManager = $salesChannelManager;
    }

    /**
     * @param array $formattedData
     *
     * @return int[]
     */
    public function exportCustomers(array $formattedData): array
    {
        $result = [
            CustomerExportProcessorInterface::EXPORT_STATUS_FAILED => 0,
            CustomerExportProcessorInterface::EXPORT_STATUS_SUCCESS => 0,
        ];

        $exportRequestBody = $this->getExportRequestBody($formattedData);
        try {
            $this->logger->info('Request of exporting customers to SW: ' . json_encode($exportRequestBody));

            $this->customerService->bulkUpsertCustomers($exportRequestBody);

            $this->saveSwIds($formattedData);
            $result[CustomerExportProcessorInterface::EXPORT_STATUS_SUCCESS] = count($formattedData);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to export customers to SW: ' . $exception->getMessage());

            $result[CustomerExportProcessorInterface::EXPORT_STATUS_FAILED] = count($formattedData);
        }

        return $result;
    }

    /**
     * @param int|null $offset
     * @param int|null $limit
     * @param string|null $lastExecutionTime
     *
     * @return Collection
     */
    public function searchCustomers(?int $offset, ?int $limit, ?string $lastExecutionTime = null): Collection
    {
        $query = PimCustomer::with('defaultBillingAddress.country', 'defaultShippingAddress.country');
        if ($offset) {
            $query->offset($offset);
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * @param Collection $pimCustomers
     *
     * @return array
     * @throws Exception
     */
    public function formatCustomerData(Collection $pimCustomers): array
    {
        $exportData = [];

        /** @var PimCustomer $pimCustomer */
        foreach ($pimCustomers as $pimCustomer) {
            $swCustomerId = $this->findSwCustomerByPimId($pimCustomer->id);

            if (!$swCustomerId) {
                $customerData = $this->formatCustomerCreateData($pimCustomer);
            } else {
                $billingAddressId = $this->formatDefaultBillingAddressId($pimCustomer);
                if (!$billingAddressId) {
                    $this->logger->info('This customer does not have billing address: ' . $pimCustomer->id);

                    continue;
                }

                $shippingAddressId = $this->formatDefaultShippingAddressId($pimCustomer);
                if (!$shippingAddressId) {
                    $this->logger->info('This customer does not have shipping address: ' . $pimCustomer->id);

                    continue;
                }

                $defaultPaymentMethodId = $this->formatDefaultPaymentMethod($pimCustomer);
                if (!$defaultPaymentMethodId) {
                    $this->logger->info('This customer does not have payment method: ' . $pimCustomer->id);

                    continue;
                }

                $customerGroupId = $this->formatCustomerGroupId($pimCustomer);
                if (!$customerGroupId) {
                    $this->logger->info('This customer does not have group id: ' . $pimCustomer->id);

                    continue;
                }

                $customerData = [
                    'groupId' => $this->formatCustomerGroupId($pimCustomer),
                    'defaultPaymentMethodId' => $defaultPaymentMethodId,
                    'defaultBillingAddressId' => $billingAddressId,
                    'defaultShippingAddressId' => $shippingAddressId,
                    'customerNumber' => $this->formatCustomerNumber($pimCustomer),
                    'salutationId' => $this->formatSalutationId($pimCustomer),
                    'firstName' => $this->formatFirstName($pimCustomer),
                    'lastName' => $this->formatLastName($pimCustomer),
                    'birthday' => $this->formatBirthday($pimCustomer),
                    'customFields' => $this->formatCustomFields($pimCustomer),
                    'email' => $this->formatCustomerEmail($pimCustomer),
                    'salesChannelId' => $this->getDefaultSalesChannelId(),
                    'id' => $swCustomerId,
                    'extensions' => [
                        'pim_customer_id' => $pimCustomer->id,
                        'pim_billing_address_id' => $pimCustomer->default_billing_address_id,
                        'pim_shipping_address_id' => $pimCustomer->default_shipping_address_id,
                        'sw_billing_address_id' => $billingAddressId,
                        'sw_shipping_address_id' => $shippingAddressId
                    ]
                ];
            }

            $exportData[] = $customerData;
        }

        return $exportData;
    }

    /**
     * @param array $customerData
     *
     * @return array[]
     */
    public function getExportRequestBody(array $customerData): array
    {
        return [
            'customer-export' => [
                'entity' => 'customer',
                'action' => 'upsert',
                'payload' => $customerData,
            ],
        ];
    }

    /**
     * @return string
     */
    public function getDefaultSalesChannelName(): string
    {
        return config('shopware6-sdk.defaults.salesChannels')[0]['name'];
    }

    /**
     * @param string $pimCustomerId
     *
     * @return string|null
     */
    public function findSwCustomerByPimId(string $pimCustomerId): ?string
    {
        return Shopware6CustomersExtension::where('pim_customer_id', $pimCustomerId)->first()?->sw_customer_id;
    }

    /**
     * @param PimCustomer $pimCustomer
     *
     * @return string|null
     */
    public function formatDefaultPaymentMethod(PimCustomer $pimCustomer): ?string
    {
        return 'd5003b3c951046f7a7a79446195639ef';
        $pimPaymentMethodId = $pimCustomer?->default_payment_method;
        if (!$pimPaymentMethodId) {
            return null;
        }

        return $this->paymentMethodManager->findByPimId($pimPaymentMethodId)?->sw_payment_method_id;
    }

    /**
     * @param PimCustomer $pimCustomer
     *
     * @return string|null
     */
    public function formatDefaultBillingAddressId(PimCustomer $pimCustomer): ?string
    {
        $pimBillingAddressId = $pimCustomer->default_billing_address_id;
        if (!$pimBillingAddressId) {
            return null;
        }

        return $this->addressManager->findSwAddressExtension($pimBillingAddressId)?->sw_address_id;
    }

    /**
     * @param PimCustomer $pimCustomer
     *
     * @return string|null
     */
    public function formatDefaultShippingAddressId(PimCustomer $pimCustomer): ?string
    {
        $pimShippingAddressId = $pimCustomer->default_shipping_address_id;
        if (!$pimShippingAddressId) {
            return null;
        }

        return $this->addressManager->findSwAddressExtension($pimShippingAddressId)?->sw_address_id;
    }

    /**
     * @param PimCustomer $pimCustomer
     *
     * @return string
     */
    public function formatCustomerNumber(PimCustomer $pimCustomer): string
    {
        return $pimCustomer->identifier;
    }

    /**
     * @param PimCustomer $pimCustomer
     *
     * @return string|null
     */
    public function formatSalutationId(PimCustomer $pimCustomer): ?string
    {
        $pimSalutationId = $pimCustomer->salutation_id;
        if (!$pimSalutationId) {
            return null;
        }

        return $this->salutationManager->getSwSalutationByPimSalutation($pimSalutationId)?->sw_salutation_id;
    }

    /**
     * @param PimCustomer $pimCustomer
     *
     * @return string
     */
    public function formatFirstName(PimCustomer $pimCustomer): string
    {
        return $pimCustomer->first_name;
    }

    /**
     * @param PimCustomer $pimCustomer
     *
     * @return string
     */
    public function formatLastName(PimCustomer $pimCustomer): string
    {
        return $pimCustomer->last_name;
    }

    /**
     * @param PimCustomer $pimCustomer
     *
     * @return string|null
     */
    public function formatBirthday(PimCustomer $pimCustomer): ?string
    {
        return $pimCustomer->birthday;
    }

    /**
     * @param PimCustomer $pimCustomer
     *
     * @return array
     */
    public function formatCustomFields(PimCustomer $pimCustomer): array
    {
        return $pimCustomer->custom_fields;
    }

    /**
     * @param PimCustomer $pimCustomer
     *
     * @return string
     */
    public function formatCustomerEmail(PimCustomer $pimCustomer): string
    {
        return $pimCustomer->email;
    }

    /**
     * @param PimCustomer $pimCustomer
     *
     * @return string|null
     */
    public function formatCustomerGroupId(PimCustomer $pimCustomer): ?string
    {
        return 'cfbd5018d38d41d8adca10d94fc8bdd6';
        $pimGroupId = $pimCustomer?->group_id;
        if (!$pimGroupId) {
            return null;
        }

        return $this->customerGroupManager->findByPimId($pimGroupId)?->sw_customer_group_id;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getDefaultSalesChannelId(): string
    {
        $defaultSalesChannel = $this->salesChannelManager->getSalesChannel([$this->getDefaultSalesChannelName()]) ?? [];
        $defaultSalesChannel = reset($defaultSalesChannel);
        if (!$defaultSalesChannel) {
            throw new Exception('Sales channel not selected!');
        }

        return $defaultSalesChannel['sw_sales_channel_id'];
    }

    /**
     * @param PimCustomer $customer
     *
     * @return array
     * @throws Exception
     */
    public function formatCustomerCreateData(PimCustomer $customer): array
    {
        $customerNumber = $this->formatCustomerNumber($customer);

        $customerId = md5($customerNumber);
        $customerData = [
            'id' => $customerId,
            'customerNumber' => $customerNumber,
            'salutationId' => $this->formatSalutationId($customer),
            'firstName' => $this->formatFirstName($customer),
            'lastName' => $this->formatLastName($customer),
            'birthday' => $this->formatBirthday($customer),
            'customFields' => $this->formatCustomFields($customer),
            'email' => $this->formatCustomerEmail($customer),
            'salesChannelId' => $this->getDefaultSalesChannelId(),
            'extensions' => [
                'pim_customer_id' => $customer->id,
                'pim_billing_address_id' => $customer->default_billing_address_id,
                'pim_shipping_address_id' => $customer->default_shipping_address_id
            ]
        ];


        $billingAddressId = $this->formatDefaultBillingAddressId($customer);
        if (!$billingAddressId) {
            $defaultBillingAddress = $customer->defaultBillingAddress;
            $customerData['defaultBillingAddress'] = $this->formatAddressData($defaultBillingAddress, $customer->id);
        } else {
            $customerData['defaultBillingAddressId'] = $billingAddressId;
        }

        $shippingAddressId = $this->formatDefaultShippingAddressId($customer);
        if (!$shippingAddressId) {
            $defaultShippingAddress = $customer->defaultShippingAddress;

            $customerData['defaultShippingAddress'] = $this->formatAddressData($defaultShippingAddress, $customer->id);
        } else {
            $customerData['defaultShippingAddressId'] = $shippingAddressId;
        }


        $defaultPaymentMethodId = $this->formatDefaultPaymentMethod($customer);
        if ($defaultPaymentMethodId) {
            $customerData['defaultPaymentMethodId'] = $defaultPaymentMethodId;
        }

        $groupId = $this->formatCustomerGroupId($customer);
        if ($groupId) {
            $customerData['groupId'] = $groupId;
        }

        $customerData['extensions']['sw_billing_address_id'] = $billingAddressId ?? $customerData['defaultBillingAddress']['id'];
        $customerData['extensions']['sw_shipping_address_id'] = $shippingAddressId ?? $customerData['defaultShippingAddress']['id'];

        return $customerData;
    }

    /**
     * @param array $formattedCustomersData
     *
     * @return void
     */
    private function saveSwIds(array $formattedCustomersData): void
    {
        $resolvedData = [];
        foreach ($formattedCustomersData as $formattedCustomerData) {
            $pimCustomerId = $formattedCustomerData['extensions']['pim_customer_id'];
            $swCustomerId = $formattedCustomerData['id'];

            $pimCustomerBillingAddressId = $formattedCustomerData['extensions']['pim_billing_address_id'];
            $pimCustomerShippingAddressId = $formattedCustomerData['extensions']['pim_shipping_address_id'];
            $swCustomerShippingAddressId = $formattedCustomerData['extensions']['sw_shipping_address_id'];
            $swCustomerBillingAddressId = $formattedCustomerData['extensions']['sw_billing_address_id'];

            $resolvedData[$pimCustomerId] = [
                'pimCustomerId' => $pimCustomerId,
                'pimBillingAddressId' => $pimCustomerBillingAddressId,
                'pimShippingAddressId' => $pimCustomerShippingAddressId,
                'swCustomerId' => $swCustomerId,
                'swBillingAddressId' => $swCustomerBillingAddressId,
                'swShippingAddressId' => $swCustomerShippingAddressId
            ];
        }

        foreach ($resolvedData as $pimCustomerId => $data) {
            Shopware6CustomersExtension::updateOrCreate(
                ['pim_customer_id' => $pimCustomerId],
                [
                    'sw_customer_id' => $data['swCustomerId']
                ]
            );

            Shopware6CustomerAddressExtension::updateOrCreate(
                ['pim_address_id' => $data['pimBillingAddressId'], 'pim_customer_id' => $pimCustomerId],
                [
                    'sw_address_id' => $data['swBillingAddressId']
                ]
            );

            Shopware6CustomerAddressExtension::updateOrCreate(
                ['pim_address_id' => $data['pimShippingAddressId'], 'pim_customer_id' => $pimCustomerId],
                [
                    'sw_address_id' => $data['swShippingAddressId']
                ]
            );
        }
    }

    /**
     * @param PimCustomerAddress $address
     * @param string $customerId
     *
     * @return array
     */
    private function formatAddressData(PimCustomerAddress $address, string $customerId): array
    {
        return [
            'firstName' => $address->first_name,
            'lastName' => $address->last_name,
            'street' => $address->street,
            'zipcode' => $address->zipcode,
            'city' => $address->city,
            'phoneNumber' => $address->phone_number,
            'countryId' => $this->formatCountryData($address),
            'id' => md5($customerId . '_' . $address->first_name . '_' . $address->last_name . '_' . $address->street . '_' . $address->city),
            'extensions' => [
                'pim_address_id' => $address->id
            ]
        ];
    }

    /**
     * @param PimCustomerAddress $address
     *
     * @return string
     */
    private function formatCountryData(PimCustomerAddress $address): string
    {
        return 'b550f4502a504a57bfbd627fa429f87a';
    }
}
