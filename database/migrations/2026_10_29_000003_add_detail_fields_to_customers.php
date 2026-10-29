<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * แยกข้อมูลลูกค้าเป็นช่องย่อย
 *
 *   บุคคล  : คำนำหน้า / ชื่อ / นามสกุล  (name_customer = ชื่อเต็ม ระบบรวมให้เอง)
 *   บริษัท : ชื่อบริษัท (name_customer) / สำนักงานใหญ่-สาขา / ผู้ติดต่อ
 *   ทั้งคู่ : เลขประจำตัวผู้เสียภาษี 13 หลัก (ใช้ในใบแจ้งหนี้ / ใบเสร็จ)
 *   ที่อยู่ : บ้านเลขที่ / หมู่ / หมู่บ้าน-อาคาร (address_detail เดิม) / ซอย / ถนน
 *
 * name_customer ยังอยู่ — ทุกหน้าที่แสดงชื่อลูกค้าใช้ได้เหมือนเดิม
 */
return new class extends Migration
{
    private const PREFIXES = ['นางสาว', 'นาง', 'นาย', 'น.ส.'];

    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('name_prefix', 30)->nullable()->after('customer_type');
            $table->string('first_name', 100)->nullable()->after('name_prefix');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->char('tax_id', 13)->nullable()->after('last_name');
            $table->char('branch_code', 5)->nullable()->after('tax_id');      // 00000 = สำนักงานใหญ่
            $table->string('contact_name', 150)->nullable()->after('branch_code');

            $table->string('address_no', 30)->nullable()->after('email_customer');
            $table->string('moo', 10)->nullable()->after('address_no');
            $table->string('soi', 100)->nullable()->after('address_detail');
            $table->string('road', 100)->nullable()->after('soi');
        });

        // ลูกค้าบุคคลที่มีอยู่แล้ว: แยกชื่อเต็มเป็น คำนำหน้า / ชื่อ / นามสกุล
        DB::table('customers')->where('customer_type', 'person')->orderBy('id_customer')
            ->each(function ($row) {
                [$prefix, $first, $last] = self::split((string) $row->name_customer);

                DB::table('customers')->where('id_customer', $row->id_customer)->update([
                    'name_prefix' => $prefix,
                    'first_name'  => $first,
                    'last_name'   => $last,
                ]);
            });

        // บริษัทที่มีอยู่แล้ว ถือเป็นสำนักงานใหญ่
        DB::table('customers')->where('customer_type', 'company')->update(['branch_code' => '00000']);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'name_prefix', 'first_name', 'last_name', 'tax_id', 'branch_code', 'contact_name',
                'address_no', 'moo', 'soi', 'road',
            ]);
        });
    }

    private static function split(string $name): array
    {
        $name   = trim(preg_replace('/\s+/u', ' ', $name));
        $prefix = null;

        foreach (self::PREFIXES as $p) {
            if (str_starts_with($name, $p)) {
                $prefix = $p === 'น.ส.' ? 'นางสาว' : $p;
                $name   = ltrim(mb_substr($name, mb_strlen($p)));
                break;
            }
        }

        $parts = explode(' ', $name, 2);

        return [$prefix, $parts[0] ?: null, $parts[1] ?? null];
    }
};
