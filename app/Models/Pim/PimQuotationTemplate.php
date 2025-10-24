<?php

namespace App\Models\Pim;

use App\Casts\Pim\PimQuotationContentCast;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PimQuotationTemplate extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'pim_quotations';

    protected $guarded = [];

    protected $casts = [
        'content' => PimQuotationContentCast::class,
    ];

    public const string QUOTATION_NUMBER_PREFIX = 'T-';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->quotation_template_number = PimQuotationTemplate::generateQuotationNumber();
        });
    }

    protected static function booted()
    {
        static::addGlobalScope('quotation_template_number', function (Builder $builder) {
            $builder->whereNotNull('quotation_template_number');
        });
    }

    protected function formattedQuotationNumber(): Attribute
    {
        return Attribute::make(
            get: function () {
                $number = $this->quotation_template_number;

                return PimQuotationTemplate::QUOTATION_NUMBER_PREFIX.$number.'-'.Carbon::parse($this->created_at)->format('y');
            }
        );
    }

    protected function summarizedLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                $number = $this->formatted_quotation_number;

                return $number.' '.$this->internal_comment.' ('.$this->products()->count().') '.__('Posten');
            }
        );
    }

    public static function generateQuotationNumber(): int
    {
        return self::withTrashed()->max('quotation_template_number') + 1;
    }

    public function products(): HasMany
    {
        return $this->hasMany(QuotationProduct::class, 'pim_quotation_id', 'id')->with(['product'])->orderBy('position');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(PimTax::class, 'pim_tax_id');
    }
}
