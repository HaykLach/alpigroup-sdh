<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! App::isProduction()) {
            Artisan::call('shield:install', [
                'panel' => 'admin',
            ]);

            $user = \App\Models\User::factory()->create([
                'name' => 'SmartDato',
                'email' => 'info@smart-dato.com',
                'password' => Hash::make('password'),
            ]);

            $user->assignRole('super_admin');
        }

        $this->call([
            LocaleSeeder::class,
            LanguageSeeder::class,
            TaxSeeder::class,
            CurrencySeeder::class,
            RoleSeeder::class,
        ]);
    }
}
