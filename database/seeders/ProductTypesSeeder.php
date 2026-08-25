<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['หิน', 'ดิน', 'ทราย'];

        foreach ($types as $type) {
            DB::table('product_types')->updateOrInsert(
                ['name_product_type' => $type],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}