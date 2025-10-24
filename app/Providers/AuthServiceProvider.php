<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Pim\Cache\PimCacheTranslation;
use App\Models\Pim\Customer\PimAgent;
use App\Models\Pim\Customer\PimCustomer;
use App\Models\Pim\Job\PimJob;
use App\Models\Pim\PimCategory;
use App\Models\Pim\PimLanguage;
use App\Models\Pim\PimLead;
use App\Models\Pim\PimLocal;
use App\Models\Pim\PimMedia;
use App\Models\Pim\PimMediaPool;
use App\Models\Pim\PimQuotation;
use App\Models\Pim\PimQuotationTemplate;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\User;
use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use App\Models\VendorCatalog\VendorCatalogEntry;
use App\Models\VendorCatalog\VendorCatalogImport;
use App\Models\VendorCatalog\VendorCatalogImportRule;
use App\Models\VendorCatalog\VendorCatalogVendor;
use App\Policies\GeneralSettingsPolicy;
use App\Policies\Pim\Job\PimJobPolicy;
use App\Policies\Pim\PimAgentPolicy;
use App\Policies\Pim\PimCacheTranslationPolicy;
use App\Policies\Pim\PimCategoryPolicy;
use App\Policies\Pim\PimCustomerPolicy;
use App\Policies\Pim\PimLanguagePolicy;
use App\Policies\Pim\PimLeadPolicy;
use App\Policies\Pim\PimLocalPolicy;
use App\Policies\Pim\PimMediaPolicy;
use App\Policies\Pim\PimMediaPoolPolicy;
use App\Policies\Pim\PimQuotationPolicy;
use App\Policies\Pim\PimQuotationTemplatePolicy;
use App\Policies\Pim\Product\PimProductManufacturerPolicy;
use App\Policies\Pim\Product\PimProductPolicy;
use App\Policies\Pim\Property\PimPropertyGroupPolicy;
use App\Policies\QueueMonitorPolicy;
use App\Policies\UserPolicy;
use App\Policies\VendorCatalog\ImportDefinition\VendorCatalogImportDefinitionPolicy;
use App\Policies\VendorCatalog\VendorCatalogEntryPolicy;
use App\Policies\VendorCatalog\VendorCatalogImportPolicy;
use App\Policies\VendorCatalog\VendorCatalogImportRulePolicy;
use App\Policies\VendorCatalog\VendorCatalogVendorPolicy;
use App\Settings\GeneralSettings;
use Croustibat\FilamentJobsMonitor\Models\QueueMonitor;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        User::class => UserPolicy::class,
        PimJob::class => PimJobPolicy::class,
        PimLocal::class => PimLocalPolicy::class,
        QueueMonitor::class => QueueMonitorPolicy::class,
        VendorCatalogImport::class => VendorCatalogImportPolicy::class,
        VendorCatalogEntry::class => VendorCatalogEntryPolicy::class,
        VendorCatalogImportRule::class => VendorCatalogImportRulePolicy::class,
        VendorCatalogVendor::class => VendorCatalogVendorPolicy::class,
        PimCategory::class => PimCategoryPolicy::class,
        PimLanguage::class => PimLanguagePolicy::class,
        PimMedia::class => PimMediaPolicy::class,
        PimMediaPool::class => PimMediaPoolPolicy::class,
        VendorCatalogImportDefinition::class => VendorCatalogImportDefinitionPolicy::class,
        PimProduct::class => PimProductPolicy::class,
        PimProductManufacturer::class => PimProductManufacturerPolicy::class,
        PimPropertyGroup::class => PimPropertyGroupPolicy::class,
        PimCustomer::class => PimCustomerPolicy::class,
        PimAgent::class => PimAgentPolicy::class,
        GeneralSettings::class => GeneralSettingsPolicy::class,
        PimCacheTranslation::class => PimCacheTranslationPolicy::class,
        PimQuotation::class => PimQuotationPolicy::class,
        PimQuotationTemplate::class => PimQuotationTemplatePolicy::class,
        PimLead::class => PimLeadPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
