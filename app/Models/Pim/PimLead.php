<?php

namespace App\Models\Pim;

use App\Enums\Pim\PimLeadSource;
use App\Enums\Pim\PimLeadStatus;
use App\Models\Pim\Customer\PimAgent;
use App\Models\Pim\Customer\PimCustomer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PimLead extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'status' => PimLeadStatus::class,
        'source' => PimLeadSource::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lead) {
            if (! $lead->number) {
                $lead->number = self::generateNumber();
            }
        });
    }

    public static function generateNumber(): int
    {
        return self::withTrashed()->max('number') + 1;
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(PimAgent::class, 'pim_agent_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(PimCustomer::class, 'pim_customer_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(PimQuotation::class, 'pim_lead_id');
    }

    /**
     * Scope to filter leads by an agent ID.
     */
    public function scopeByAgentId(Builder $query, string $agentId): Builder
    {
        return $query->where('pim_agent_id', $agentId);
    }
}
