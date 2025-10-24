<?php

namespace App\Models\Pim;

use App\Casts\Pim\PimQuotationContentCast;
use App\Enums\Pim\PimQuotationStatus;
use App\Models\Pim\Customer\PimCustomer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PimQuotation extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'validity_period' => 'date',
        'sent_to_customer' => 'datetime',
        'content' => PimQuotationContentCast::class,
        'status' => PimQuotationStatus::class,
        'version' => 'integer',
    ];

    public const string QUOTATION_NUMBER_PREFIX = 'CRM-';

    protected static function booted()
    {
        static::addGlobalScope('quotation_number', function (Builder $builder) {
            $builder->whereNotNull('quotation_number');
        });
    }

    protected function formattedQuotationNumber(): Attribute
    {
        return Attribute::make(
            get: function () {
                $number = $this->quotation_number + 10000;
                $prefix = PimQuotation::QUOTATION_NUMBER_PREFIX.$number;
                $version = ! empty($this->version) && $this->version > 1 ? '.'.$this->version - 1 : '';
                $suffix = '-'.Carbon::parse($this->created_at)->format('y');

                return $prefix.$version.$suffix;
            }
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(QuotationProduct::class)->with(['product'])->orderBy('position');
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(PimCustomer::class, 'quotation_customer')
            ->withTimestamps();
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(PimCustomer::class, 'quotation_agent')
            ->withTimestamps();
    }

    public static function generateQuotationNumber(): int
    {
        return self::withTrashed()->max('quotation_number') + 1;
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(PimTax::class, 'pim_tax_id');
    }

    /**
     * Get the lead associated with the quotation.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(PimLead::class, 'pim_lead_id');
    }

    /**
     * Scope to filter quotations by an agent ID.
     */
    public function scopeByAgentId(Builder $query, string $agentId): Builder
    {
        return $query->whereHas('agents', function ($subQuery) use ($agentId) {
            $subQuery->where('pim_customer_id', $agentId);
        });
    }

    /**
     * Scope to filter quotations by date range.
     */
    public function scopeByDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('updated_at', [$startDate, $endDate]);
    }

    /**
     * Get the parent quotation (previous version).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PimQuotation::class, 'parent_id');
    }

    /**
     * Get the child quotations (newer versions).
     */
    public function children(): HasMany
    {
        return $this->hasMany(PimQuotation::class, 'parent_id');
    }
}
