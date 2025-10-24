<?php

declare(strict_types=1);

namespace App\Repositories\PaymentMethod;

use App\Contracts\PaymentMethod\PaymentMethodRepositoryInterface;
use App\Models\Pim\PaymentMethod\PimPaymentMethod;
use App\Repositories\BaseRepository;
use Exception;

class PaymentMethodRepository extends BaseRepository implements PaymentMethodRepositoryInterface
{
    /** @var string */
    public const PIM_PAYMENT_METHOD_CACHE_NAME = 'pimPaymentMethod';

    public function upsert(array $data): ?PimPaymentMethod
    {
        return $this->findByData($data);
    }

    /**
     * @throws Exception
     */
    public function update(string $id, array $data): PimPaymentMethod
    {
        $paymentMethod = $this->find($id);
        if (! $paymentMethod) {
            throw new Exception('Payment method with id '.$id.' not found');
        }

        $paymentMethod->update($data);

        return $paymentMethod;
    }

    public function find(string $id): ?PimPaymentMethod
    {
        return $this->findModelByField(PimPaymentMethod::class, $id, 'id', [], self::PIM_PAYMENT_METHOD_CACHE_NAME);
    }

    /**
     * get from Db payment method by name
     */
    public function findByName(string $paymentName): ?PimPaymentMethod
    {
        return $this->findModelByField(PimPaymentMethod::class, $paymentName, 'name', [], self::PIM_PAYMENT_METHOD_CACHE_NAME);
    }

    /**
     * returns existing payment method if not exists creates then returns
     */
    public function findByData(array $paymentData): PimPaymentMethod
    {
        $existingPaymentMethod = $this->findByName($paymentData['name']);
        if ($existingPaymentMethod) {
            return $existingPaymentMethod;
        }

        return PimPaymentMethod::create($paymentData);
    }
}
