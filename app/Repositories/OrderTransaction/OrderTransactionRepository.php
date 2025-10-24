<?php

namespace App\Repositories\OrderTransaction;

use App\Contracts\OrderTransaction\OrderTransactionRepositoryInterface;
use App\Models\Pim\Order\PimOrderTransaction;
use App\Repositories\BaseRepository;
use Exception;

class OrderTransactionRepository extends BaseRepository implements OrderTransactionRepositoryInterface
{
    public function findOrderTransaction(string $orderId, string $paymentMethodId): ?PimOrderTransaction
    {
        return PimOrderTransaction::where('order_id', $orderId)->where('payment_method_id', $paymentMethodId)->first();
    }

    public function create(array $data): PimOrderTransaction
    {
        return PimOrderTransaction::create($data);
    }

    /**
     * @throws Exception
     */
    public function update(string $id, array $data): PimOrderTransaction
    {
        $pimOrderTransaction = $this->find($id);
        if (! $pimOrderTransaction) {
            throw new Exception('Transaction with id '.$id.' not found');
        }

        $pimOrderTransaction->update($data);

        return $pimOrderTransaction;
    }

    public function find(string $id): ?PimOrderTransaction
    {
        return $this->findModelByField(PimOrderTransaction::class, $id, 'id');
    }
}
