<?php

namespace App\Providers;

use App\Contracts\Category\CategoryRepositoryInterface;
use App\Contracts\Country\CountryRepositoryInterface;
use App\Contracts\Currency\CurrencyRepositoryInterface;
use App\Contracts\Customer\CustomerRepositoryInterface;
use App\Contracts\CustomerAddress\CustomerAddressRepositoryInterface;
use App\Contracts\CustomerGroup\CustomerGroupRepositoryInterface;
use App\Contracts\Job\JobLogRepositoryInterface;
use App\Contracts\Job\JobRepositoryInterface;
use App\Contracts\Language\LanguageRepositoryInterface;
use App\Contracts\Manufacturer\ManufacturerRepositoryInterface;
use App\Contracts\Order\OrderRepositoryInterface;
use App\Contracts\OrderAddress\OrderAddressRepositoryInterface;
use App\Contracts\OrderDelivery\OrderDeliveryRepositoryInterface;
use App\Contracts\OrderTransaction\OrderTransactionRepositoryInterface;
use App\Contracts\PaymentMethod\PaymentMethodRepositoryInterface;
use App\Contracts\Product\ProductRepositoryInterface;
use App\Contracts\Property\PropertyGroupOptionRepositoryInterface;
use App\Contracts\Property\PropertyGroupRepositoryInterface;
use App\Contracts\SalesChannel\SalesChannelRepositoryInterface;
use App\Contracts\Salutation\SalutationRepositoryInterface;
use App\Contracts\Tax\TaxRepositoryInterface;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Country\CountryRepository;
use App\Repositories\Currency\CurrencyRepository;
use App\Repositories\Customer\CustomerRepository;
use App\Repositories\CustomerAddress\CustomerAddressRepository;
use App\Repositories\CustomerGroup\CustomerGroupRepository;
use App\Repositories\Job\JobLogRepository;
use App\Repositories\Job\JobRepository;
use App\Repositories\Language\LanguageRepository;
use App\Repositories\Manufacturer\ManufacturerRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\OrderAddress\OrderAddressRepository;
use App\Repositories\OrderDelivery\OrderDeliveryRepository;
use App\Repositories\OrderTransaction\OrderTransactionRepository;
use App\Repositories\PaymentMethod\PaymentMethodRepository;
use App\Repositories\Product\ProductRepository;
use App\Repositories\Property\PropertyGroupOptionRepository;
use App\Repositories\Property\PropertyGroupRepository;
use App\Repositories\SalesChannel\SalesChannelRepository;
use App\Repositories\Salutation\SalutationRepository;
use App\Repositories\Tax\TaxRepository;
use App\Services\Export\PimCustomerExportService;
use App\Services\Import\CustomerImportManager;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\View\Components\Modal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use SmartDato\Shopware6\Contracts\Customer\CustomerExportManagerInterface;
use SmartDato\Shopware6\Contracts\Customer\CustomerManagerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        $cssFile = $this->findCurrentCssFile();
        if ($cssFile) {
            FilamentAsset::register([
                CSS::make('app', asset("/build/assets/{$cssFile}")),
            ]);
        }
    }

    private function findCurrentCssFile(): ?string
    {
        $files = glob(public_path('build/assets/app-*.css'));

        return $files ? basename($files[0]) : null;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Everything strict, all the time.
        Model::shouldBeStrict();

        Modal::closedByClickingAway(false);

        // In production, merely log lazy loading violations.
        if ($this->app->isProduction()) {
            Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
                $class = get_class($model);

                Log::info("Attempted to lazy load [{$relation}] on model [{$class}].");
            });
        }

        // $this->bootSuperAdmin(); // uncomment to grant access to @smart-dato.com users
        $this->bootRepositories();
        $this->bootServices();
    }

    private function bootRepositories(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
        $this->app->bind(CurrencyRepositoryInterface::class, CurrencyRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(JobRepositoryInterface::class, JobRepository::class);
        $this->app->bind(LanguageRepositoryInterface::class, LanguageRepository::class);
        $this->app->bind(PaymentMethodRepositoryInterface::class, PaymentMethodRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(PropertyGroupRepositoryInterface::class, PropertyGroupRepository::class);
        $this->app->bind(PropertyGroupOptionRepositoryInterface::class, PropertyGroupOptionRepository::class);
        $this->app->bind(SalesChannelRepositoryInterface::class, SalesChannelRepository::class);
        $this->app->bind(TaxRepositoryInterface::class, TaxRepository::class);
        $this->app->bind(ManufacturerRepositoryInterface::class, ManufacturerRepository::class);
        $this->app->bind(SalutationRepositoryInterface::class, SalutationRepository::class);
        $this->app->bind(CustomerAddressRepositoryInterface::class, CustomerAddressRepository::class);
        $this->app->bind(OrderAddressRepositoryInterface::class, OrderAddressRepository::class);
        $this->app->bind(OrderDeliveryRepositoryInterface::class, OrderDeliveryRepository::class);
        $this->app->bind(OrderTransactionRepositoryInterface::class, OrderTransactionRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(JobLogRepositoryInterface::class, JobLogRepository::class);
        $this->app->bind(CustomerGroupRepositoryInterface::class, CustomerGroupRepository::class);
    }

    private function bootServices(): void
    {
        $this->app->bind(CustomerExportManagerInterface::class, PimCustomerExportService::class);
    }

    /**
     * workaround initial setup spatie shield roles management
     *
     * problem: after installing spatie shield, the info@smart-dato.com user may have no roles assigned
     * solution: grant all permissions to info@smart-dato.com user and apply super_admin role
     */
    private function bootSuperAdmin(): void
    {
        Gate::before(static function ($user, $ability) {
            return str_ends_with($user->email, '@smart-dato.com');
        });
    }
}
