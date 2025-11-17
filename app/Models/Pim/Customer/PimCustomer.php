<?php

namespace App\Models\Pim\Customer;

use App\Casts\Pim\PimCustomerCustomFieldsCast;
use App\Enums\Pim\PimCustomerCustomFields;
use App\Enums\Pim\PimCustomerType;
use App\Models\Pim\Order\PimOrderCustomer;
use App\Models\Pim\PaymentMethod\PimPaymentMethod;
use App\Models\Pim\PimQuotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use SmartDato\Shopware6\App\Models\Shopware6Customers\Shopware6CustomersExtension;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PimCustomer extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    protected $table = 'pim_customers';

    protected $guarded = [];

    protected $casts = [
        'custom_fields' => PimCustomerCustomFieldsCast::class,
    ];

    public function getSummarizedTitleAttribute(): string
    {
        $companyName = $this->custom_fields['company_name'] ? $this->custom_fields['company_name'].' - ' : '';

        return $companyName.$this->full_name;
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getMainAddressAttribute(): ?PimCustomerAddress
    {
        return $this->addresses->first();
    }

    // Accessor for type
    public function getTypeAttribute()
    {
        return $this->custom_fields[PimCustomerCustomFields::TYPE->value] ?? null;
    }

    // Mutator for type
    public function setTypeAttribute($value)
    {
        $customFields = $this->custom_fields ?? [];
        $customFields[PimCustomerCustomFields::TYPE->value] = $value;
        $this->custom_fields = $customFields;
    }

    // Scope for customers
    public function scopeCustomers(Builder $query)
    {
        return $this->getTypeScope($query, PimCustomerType::CUSTOMER);
    }

    public function scopeCrmCustomers(Builder $query)
    {
        return $this->getTypeScope($query, PimCustomerType::CRM_CUSTOMER);
    }

    // Scope for agents
    public function scopeAgents(Builder $query)
    {
        return $this->getTypeScope($query, PimCustomerType::AGENT);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('custom_fields->'.PimCustomerCustomFields::BLOCKED->value, '=', false);
    }

    public function scopeBlocked(Builder $query)
    {
        return $query->where('custom_fields->'.PimCustomerCustomFields::BLOCKED->value, '=', true);
    }

    public function scopeWithQuotationsCount(Builder $query)
    {
        return $query->addSelect([
            'quotations_count' => DB::table('quotation_customer')
                ->selectRaw('count(*)')
                ->leftJoin('pim_quotations', 'quotation_customer.pim_quotation_id', '=', 'pim_quotations.id')
                ->whereNull('pim_quotations.deleted_at')
                ->whereColumn('pim_customer_id', 'pim_customers.id'),
        ]);
    }

    public function scopeByAgentId(Builder $query, string $agentId)
    {
        return $query->where('custom_fields->'.PimCustomerCustomFields::AGENT_ID->value, $agentId);
    }

    protected static function getTypeScope($query, PimCustomerType $type): Builder
    {
        return $query->where('custom_fields->'.PimCustomerCustomFields::TYPE->value, '=', $type);
    }

    public function scopeAllCustomerTypes(Builder $query)
    {
        return $query->whereIn('custom_fields->'.PimCustomerCustomFields::TYPE->value,
            [PimCustomerType::CUSTOMER->value, PimCustomerType::CRM_CUSTOMER->value]);
    }

    public static function getMediaCollectionPreviewName(): string
    {
        return 'profile_picture';
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $collection = $this->getMediaCollectionPreviewName();

        $this
            ->addMediaConversion('thumbnail')
            ->performOnCollections($collection)
            ->nonQueued()
            ->format('png')
            ->width(80)
            ->height(80);

        $this
            ->addMediaConversion('preview')
            ->performOnCollections($collection)
            ->nonQueued()
            ->keepOriginalImageFormat()
            ->quality(80)
            ->width(640);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PimCustomerAddress::class, 'customer_id', 'id')
            ->with('country');
    }

    public function salutation(): BelongsTo
    {
        return $this->belongsTo(PimCustomerSalutation::class, 'salutation_id', 'id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PimPaymentMethod::class, 'default_payment_method', 'id');
    }

    public function order(): HasMany
    {
        return $this->hasMany(PimOrderCustomer::class, 'customer_id', 'id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(PimCustomerBranch::class);
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(PimCustomerTaxGroup::class, 'tax_group_id');
    }

    public function quotations(): BelongsToMany
    {
        return $this->belongsToMany(PimQuotation::class, 'quotation_customer')
            ->withTimestamps();
    }

    public function getAgentIdAttribute()
    {
        return $this->custom_fields['agent_id'] ?? null;
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(PimAgent::class, 'agent_id', 'id');
    }

    public function defaultBillingAddress(): BelongsTo
    {
        return $this->belongsTo(PimCustomerAddress::class,'default_billing_address_id', 'id');
    }

    public function defaultShippingAddress(): BelongsTo
    {
        return $this->belongsTo(PimCustomerAddress::class,'default_shipping_address_id', 'id');
    }
}
