<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['iso2' => 'KE', 'name' => 'Kenya'],
            ['iso2' => 'US', 'name' => 'United States'],
            ['iso2' => 'GB', 'name' => 'United Kingdom'],
            ['iso2' => 'CA', 'name' => 'Canada'],
            ['iso2' => 'AU', 'name' => 'Australia'],
            ['iso2' => 'DE', 'name' => 'Germany'],
            ['iso2' => 'FR', 'name' => 'France'],
            ['iso2' => 'IT', 'name' => 'Italy'],
            ['iso2' => 'ES', 'name' => 'Spain'],
            ['iso2' => 'ZA', 'name' => 'South Africa'],
            ['iso2' => 'NG', 'name' => 'Nigeria'],
            ['iso2' => 'TZ', 'name' => 'Tanzania'],
            ['iso2' => 'UG', 'name' => 'Uganda'],
            ['iso2' => 'RW', 'name' => 'Rwanda'],
            ['iso2' => 'BI', 'name' => 'Burundi'],
            ['iso2' => 'ET', 'name' => 'Ethiopia'],
            ['iso2' => 'AE', 'name' => 'United Arab Emirates'],
            ['iso2' => 'IN', 'name' => 'India'],
            ['iso2' => 'CN', 'name' => 'China'],
            ['iso2' => 'JP', 'name' => 'Japan'],
            ['iso2' => 'BR', 'name' => 'Brazil'],
            ['iso2' => 'MX', 'name' => 'Mexico'],
        ];

        foreach ($countries as $c) {
            Country::updateOrCreate(['iso2' => $c['iso2']], ['name' => $c['name']]);
        }
    }
}
