<?php

namespace App\Models\Pim;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QuotationProduct extends Model
{
    protected $guarded = [];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $maxPosition = self::where('pim_quotation_id', $model->pim_quotation_id)->max('position');
            $model->position = $maxPosition + 1;
        });
    }

    public function product(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public static function createForQuotation(string $quotationId, string $productId, string $productType, array $data = []): QuotationProduct
    {
        $insertData = array_merge($data, [
            'pim_quotation_id' => $quotationId,
            'product_id' => $productId,
            'product_type' => $productType,
        ]);

        $quotationProduct = new self($insertData);
        $quotationProduct->save();

        if (isset($data['position'])) {
            $quotationProduct->update([
                'position' => (int) $data['position'],
            ]);
        }

        return $quotationProduct;
    }
}
