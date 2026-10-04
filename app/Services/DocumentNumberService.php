<?php

namespace App\Services;

use App\Models\DocumentSequence;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * สร้างเลขที่เอกสารตามค่าในหน้าตั้งค่า
 *
 *   รายปี   : SO-2569-0004
 *   รายเดือน: SO-2569-10-0004
 *   ไม่รีเซ็ต: SO-000004
 *
 * วิธีใช้:
 *   app(DocumentNumberService::class)->next('sales_order');   // ออกเลขจริง (นับเพิ่ม)
 *   app(DocumentNumberService::class)->peek('sales_order');   // ดูเลขถัดไป (ไม่นับเพิ่ม)
 */
class DocumentNumberService
{
    /**
     * ออกเลขถัดไป และบันทึกตัวนับ
     *
     * ควรเรียกภายใน DB::transaction เดียวกับการสร้างเอกสาร
     * ถ้าบันทึกเอกสารไม่สำเร็จ ตัวนับจะ rollback ไปด้วย เลขจึงไม่ข้าม
     */
    public function next(string $type, ?CarbonInterface $date = null): string
    {
        $this->assertType($type);

        $date   ??= now();
        $period   = $this->periodKey($date);

        return DB::transaction(function () use ($type, $date, $period) {

            // สร้างแถวตัวนับถ้ายังไม่มี
            // ใช้ insertOrIgnore + unique index แทนการเช็กก่อนแล้วค่อยสร้าง
            // เพราะถ้าสองคนเช็กพร้อมกัน จะเห็นว่า "ยังไม่มี" ทั้งคู่แล้วสร้างซ้อน
            DB::table('document_sequences')->insertOrIgnore([
                'doc_type'    => $type,
                'period'      => $period,
                'last_number' => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // ล็อกแถวนี้ไว้ คนที่สองต้องรอจนคนแรก commit ถึงจะอ่านได้
            // จึงไม่มีทางได้เลขเดียวกัน
            $sequence = DocumentSequence::where('doc_type', $type)
                ->where('period', $period)
                ->lockForUpdate()
                ->firstOrFail();

            // เทียบกับเลขสูงสุดที่มีอยู่จริงในตารางเอกสารด้วยทุกครั้ง
            // กันเลขชนกับข้อมูลเดิม หรือเอกสารที่สร้างด้วยโค้ดเก่าก่อนเปลี่ยนมาใช้ service นี้
            $sequence->last_number = max(
                $sequence->last_number,
                $this->highestExisting($type, $date)
            ) + 1;

            $sequence->save();

            return $this->format($type, $date, $sequence->last_number);
        });
    }

    /** ดูเลขถัดไปโดยไม่นับเพิ่ม (ใช้แสดงบนหน้าจอ) */
    public function peek(string $type, ?CarbonInterface $date = null): string
    {
        $this->assertType($type);

        $date ??= now();

        $last = (int) DocumentSequence::where('doc_type', $type)
            ->where('period', $this->periodKey($date))
            ->value('last_number');

        $last = max($last, $this->highestExisting($type, $date));

        return $this->format($type, $date, $last + 1);
    }

    /** ประกอบเลขที่เอกสารจากเลขรัน */
    public function format(string $type, CarbonInterface $date, int $number): string
    {
        $digits = Setting::get('doc.running_digits');

        return $this->stem($type, $date)
            . str_pad((string) $number, $digits, '0', STR_PAD_LEFT);
    }

    /* ==================== ส่วนประกอบของเลข ==================== */

    /** ส่วนหน้าของเลข ก่อนเลขรัน เช่น "SO-2569-" */
    private function stem(string $type, CarbonInterface $date): string
    {
        $period = $this->periodLabel($date);

        return $this->prefix($type) . '-' . ($period !== '' ? $period . '-' : '');
    }

    private function prefix(string $type): string
    {
        return Setting::get("doc.prefix.{$type}");
    }

    /** ปีตามรูปแบบที่ตั้งไว้ */
    private function year(CarbonInterface $date): int
    {
        return Setting::get('doc.year_format') === 'be'
            ? $date->year + 543
            : $date->year;
    }

    /** ส่วนของช่วงเวลาที่แสดงในเลข: "2569" / "2569-10" / "" */
    private function periodLabel(CarbonInterface $date): string
    {
        return match (Setting::get('doc.reset_period')) {
            'monthly' => $this->year($date) . '-' . $date->format('m'),
            'never'   => '',
            default   => (string) $this->year($date),
        };
    }

    /** key ของช่วงเวลาในตารางตัวนับ */
    private function periodKey(CarbonInterface $date): string
    {
        return $this->periodLabel($date) ?: 'all';
    }

    /* ==================== เลขที่มีอยู่จริง ==================== */

    /**
     * เลขรันสูงสุดที่มีอยู่แล้วในตารางเอกสาร (รวมใบที่ถูก soft delete)
     *
     * ใบที่ถูกลบต้องนับด้วย เพราะเลขที่ใบกำกับภาษีห้ามนำกลับมาใช้ซ้ำ
     */
    private function highestExisting(string $type, CarbonInterface $date): int
    {
        $info   = Setting::documentTypes()[$type];
        $class  = $info['model'] ?? null;
        $column = $info['column'] ?? null;

        if (! $class || ! $column || ! class_exists($class)) {
            return 0;
        }

        $query = in_array(SoftDeletes::class, class_uses_recursive($class), true)
            ? $class::withTrashed()
            : $class::query();

        $stem    = $this->stem($type, $date);
        $pattern = '/^' . preg_quote($stem, '/') . '(\d+)$/';

        // แยกเลขรันด้วย regex แทน orderByDesc
        // เพราะการเรียงแบบข้อความจะผิดเมื่อจำนวนหลักเปลี่ยน (เช่น 9999 กับ 10000)
        return (int) $query
            ->where($column, 'like', $stem . '%')
            ->pluck($column)
            ->map(fn ($code) => preg_match($pattern, (string) $code, $m) ? (int) $m[1] : 0)
            ->max();
    }

    private function assertType(string $type): void
    {
        if (! array_key_exists($type, Setting::documentTypes())) {
            throw new InvalidArgumentException("ไม่รู้จักประเภทเอกสาร: {$type}");
        }
    }
}