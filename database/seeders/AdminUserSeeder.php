<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name'      => 'ผู้ดูแลระบบ',
                'password'  => Hash::make('admin1234'),
                'role'      => 'admin',
                'is_active' => 1,
            ]
        );
    }
}