<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pim_customer_address', function (Blueprint $table) {
            $table->foreignUuid('region_id')
                ->nullable()
                ->after('phone_number')
                ->constrained('pim_region')
                ->nullOnDelete();
        });

        $addresses = DB::table('pim_customer_address')
            ->select('id', 'region')
            ->whereNotNull('region')
            ->where('region', '!=', '')
            ->get();

        foreach ($addresses as $address) {
            $displayName = $address->region;

            $region = DB::table('pim_region')->where('display_name', $displayName)->first();

            if ($region === null) {
                $regionId = (string) Str::uuid();
                $externalId = 'legacy_' . md5($displayName);

                DB::table('pim_region')->insert([
                    'id' => $regionId,
                    'external_id' => $externalId,
                    'code' => null,
                    'display_name' => $displayName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $region = (object) ['id' => $regionId];
            }

            DB::table('pim_customer_address')
                ->where('id', $address->id)
                ->update(['region_id' => $region->id]);
        }

        Schema::table('pim_customer_address', function (Blueprint $table) {
            $table->dropColumn('region');
        });
    }

    public function down(): void
    {
        Schema::table('pim_customer_address', function (Blueprint $table) {
            $table->string('region')->nullable()->after('phone_number');
        });

        $addresses = DB::table('pim_customer_address')
            ->select('id', 'region_id')
            ->whereNotNull('region_id')
            ->get();

        foreach ($addresses as $address) {
            $region = DB::table('pim_region')->where('id', $address->region_id)->first();

            DB::table('pim_customer_address')
                ->where('id', $address->id)
                ->update(['region' => $region->display_name ?? null]);
        }

        Schema::table('pim_customer_address', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
