<?php

namespace App\Contracts\PaymentMethod;

use App\Models\Pim\PaymentMethod\PimPaymentMethod;

interface PaymentMethodRepositoryInterface
{
    public function upsert(array $data): ?PimPaymentMethod;

    public function update(string $id, array $data): PimPaymentMethod;

    /**
     * get from Db payment method by name
     */
    public function findByName(string $paymentName): ?PimPaymentMethod;

    /**
     * returns existing payment method if not exists creates then returns
     */
    public function findByData(array $paymentData): PimPaymentMethod;
}
