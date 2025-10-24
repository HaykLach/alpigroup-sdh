<?php

namespace App\Repositories\OrderDelivery;

use App\Contracts\OrderDelivery\OrderDeliveryRepositoryInterface;
use App\Models\Pim\Order\PimOrderDelivery;
use App\Repositories\BaseRepository;
use Exception;

class OrderDeliveryRepository extends BaseRepository implements OrderDeliveryRepositoryInterface
{
    public function findOrderShippingAddress(string $orderId, ?string $shippingAddressId): ?PimOrderDelivery
    {
        return PimOrderDelivery::where('order_id', $orderId)->where('shipping_address_id', $shippingAddressId ?? null)->first();
    }

    public function create(array $data): PimOrderDelivery
    {
        return PimOrderDelivery::create($data);
    }

    /**
     * @throws Exception
     */
    public function update(string $id, array $data): PimOrderDelivery
    {
        $pimOrderDelivery = $this->find($id);
        if (! $pimOrderDelivery) {
            throw new Exception('Order delivery with id '.$id.' not found');
        }

        $pimOrderDelivery->update($data);

        return $pimOrderDelivery;
    }

    public function find(string $id): ?PimOrderDelivery
    {
        return $this->findModelByField(PimOrderDelivery::class, $id, 'id');
    }
}
