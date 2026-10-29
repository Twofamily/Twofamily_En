<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * เปลี่ยนชื่อลูกค้าของระบบ "ลูกค้าทั่วไป (เงินสด)" → "ลูกค้าทั่วไป (ไม่ระบุชื่อ)"
 * ระบบรู้ว่าเป็นลูกค้าทั่วไปจากชื่อ (Customer::isWalkIn) จึงต้องเปลี่ยนชื่อแถวเดิม
 * ไม่งั้น Customer::walkIn() จะสร้างแถวใหม่ซ้ำ และใบเสร็จเก่าจะชี้ไปลูกค้าคนละแถว
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->where('name_customer', Customer::WALK_IN_OLD_NAME)
            ->update(['name_customer' => Customer::WALK_IN_NAME]);
    }

    public function down(): void
    {
        DB::table('customers')
            ->where('name_customer', Customer::WALK_IN_NAME)
            ->update(['name_customer' => Customer::WALK_IN_OLD_NAME]);
    }
};
