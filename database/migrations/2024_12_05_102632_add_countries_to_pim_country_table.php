<?php

use App\Services\Pim\PimGenerateIdService;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use PeterColes\Countries\CountriesFacade;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $countries = CountriesFacade::lookup();

        $countryData = [];
        $date = $this->getOldDate();
        foreach ($countries as $iso => $name) {
            $countryData[] = [
                'id' => PimGenerateIdService::getCountryId($iso),
                'iso' => $iso,
                'name' => $name,
                'created_at' => $date,
            ];
        }

        foreach ($countryData as $country) {
            DB::table('pim_country')->updateOrInsert(
                ['iso' => $country['iso']],
                $country
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $countryCodes = array_keys(CountriesFacade::lookup());
        $date = $this->getOldDate();

        // remove all countries that where created in date $date
        DB::table('pim_country')->whereIn('iso', $countryCodes)->where('created_at', $date)->delete();
    }

    protected function getOldDate(): Carbon
    {
        // just a sample date
        return Carbon::create(1979, 6, 9);
    }
};
