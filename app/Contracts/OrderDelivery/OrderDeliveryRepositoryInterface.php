<?php

namespace App\Contracts\OrderDelivery;

use App\Models\Pim\Order\PimOrderDelivery;

interface OrderDeliveryRepositoryInterface
{
    public function findOrderShippingAddress(string $orderId, string $shippingAddressId): ?PimOrderDelivery;

    public function create(array $data): PimOrderDelivery;

    public function update(string $id, array $data): PimOrderDelivery;

    public function find(string $id): ?PimOrderDelivery;
}
