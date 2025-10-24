<?php

namespace App\Contracts\OrderTransaction;

use App\Models\Pim\Order\PimOrderTransaction;

interface OrderTransactionRepositoryInterface
{
    public function findOrderTransaction(string $orderId, string $paymentMethodId): ?PimOrderTransaction;

    public function create(array $data): PimOrderTransaction;

    public function update(string $id, array $data): PimOrderTransaction;

    public function find(string $id): ?PimOrderTransaction;
}
