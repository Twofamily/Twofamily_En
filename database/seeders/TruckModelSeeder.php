<?php

namespace Database\Seeders;

use App\Models\TruckBrand;
use App\Models\TruckModel;
use Illuminate\Database\Seeder;

class TruckModelSeeder extends Seeder
{
    public function run(): void
    {
        $isuzu = TruckBrand::firstOrCreate(['name_brand' => 'ISUZU']);
        $hino  = TruckBrand::firstOrCreate(['name_brand' => 'HINO']);

        $models = [
            ['b' => $isuzu->id, 'name' => 'FVZ',        'year' => 2020, 'wheels' => 10, 'cubic' => 10, 'load' => 16000, 'curb' => 8500,  'cc' => 7790,  'hp' => 240, 'rate' => 3.50, 'tank' => 200],
            ['b' => $isuzu->id, 'name' => 'FXZ',        'year' => 2020, 'wheels' => 10, 'cubic' => 12, 'load' => 17000, 'curb' => 9200,  'cc' => 9839,  'hp' => 300, 'rate' => 3.20, 'tank' => 300],
            ['b' => $isuzu->id, 'name' => 'FYH',        'year' => 2021, 'wheels' => 12, 'cubic' => 14, 'load' => 20000, 'curb' => 10500, 'cc' => 9839,  'hp' => 340, 'rate' => 3.00, 'tank' => 400],
            ['b' => $isuzu->id, 'name' => 'GXZ',        'year' => 2021, 'wheels' => 10, 'cubic' => 12, 'load' => 18000, 'curb' => 9800,  'cc' => 9839,  'hp' => 360, 'rate' => 3.10, 'tank' => 300],
            ['b' => $hino->id,  'name' => 'Victor 300', 'year' => 2019, 'wheels' => 10, 'cubic' => 10, 'load' => 16000, 'curb' => 8800,  'cc' => 7684,  'hp' => 300, 'rate' => 3.40, 'tank' => 200],
            ['b' => $hino->id,  'name' => 'Victor 500', 'year' => 2022, 'wheels' => 10, 'cubic' => 12, 'load' => 18000, 'curb' => 9500,  'cc' => 10520, 'hp' => 380, 'rate' => 3.00, 'tank' => 300],
        ];

        foreach ($models as $m) {
            TruckModel::updateOrCreate(
                ['truck_brand_id' => $m['b'], 'name_model' => $m['name']],
                [
                    'model_year'     => $m['year'],
                    'truck_type'     => 'ดัมพ์',
                    'wheels'         => $m['wheels'],
                    'cubic_capacity' => $m['cubic'],
                    'load_capacity'  => $m['load'],
                    'curb_weight'    => $m['curb'],
                    'engine_cc'      => $m['cc'],
                    'horsepower'     => $m['hp'],
                    'fuel_type'      => 'ดีเซล',
                    'fuel_rate'      => $m['rate'],
                    'tank_capacity'  => $m['tank'],
                    'is_active'      => true,
                ]
            );
        }
    }
}