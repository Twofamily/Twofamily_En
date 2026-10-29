<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * หน้าตั้งค่าระบบ แบ่งเป็นแท็บตาม group ใน config/settings.php
 *
 * รับและบันทึกเฉพาะ key ที่ประกาศไว้ในทะเบียนเท่านั้น
 * field อื่นที่ถูกส่งมาจะถูกทิ้งทั้งหมด
 */
class SettingController extends Controller
{
    /** ข้อความแจ้งเตือนภาษาไทย ใช้ร่วมกับชื่อช่องจากทะเบียน (:attribute) */
    private const MESSAGES = [
        'required' => 'กรุณากรอก:attribute',
        'integer'  => ':attribute ต้องเป็นจำนวนเต็ม',
        'numeric'  => ':attribute ต้องเป็นตัวเลข',
        'between'  => ':attribute ต้องอยู่ระหว่าง :min ถึง :max',
        'digits'   => ':attribute ต้องเป็นตัวเลข :digits หลัก',
        'regex'    => 'รูปแบบ:attribute ไม่ถูกต้อง',
        'email'    => ':attribute ต้องเป็นอีเมลที่ถูกต้อง',
        'max'      => ':attribute ยาวหรือใหญ่เกินกำหนด',
        'in'       => 'ค่าของ:attribute ไม่ถูกต้อง',
        'boolean'  => 'ค่าของ:attribute ไม่ถูกต้อง',
        'image'    => ':attribute ต้องเป็นไฟล์รูปภาพ',
        'mimes'    => ':attribute ต้องเป็นไฟล์ :values',
    ];

    public function edit(DocumentNumberService $numbers, string $group = 'company')
    {
        return view('settings.index', [
            'group'         => $group,
            'groups'        => Setting::groups(),
            'definitions'   => Setting::definitions(),
            'values'        => Setting::merged(),
            'documentTypes' => Setting::documentTypes(),
            'nextNumbers'   => $group === 'document' ? $this->nextNumbers($numbers) : [],
        ]);
    }

    public function update(Request $request, string $group)
    {
        $this->normalizeInput($request, $group);

        $definitions = Setting::definitionsFor($group);

        $validated = $request->validate(
            Setting::rulesFor($group),
            self::MESSAGES,
            array_map(fn (array $def) => $def['label'], $definitions)
        );

        if ($group === 'document' && $error = $this->duplicatePrefixError($validated)) {
            return back()->withInput()->withErrors($error);
        }

        $values      = [];
        $filesToDrop = [];

        foreach ($definitions as $key => $def) {

            // ไฟล์รูป: อัปโหลดใหม่ / ลบ / ไม่แตะ
            if ($def['type'] === 'image') {
                $current = Setting::get($key);

                if ($request->hasFile($key)) {
                    $values[$key]  = $request->file($key)->store('settings', 'public');
                    $filesToDrop[] = $current;
                } elseif (! empty($request->input('remove_image', [])[$key])) {
                    $values[$key]  = '';
                    $filesToDrop[] = $current;
                }

                continue;
            }

            $value = data_get($validated, $key);

            // ช่องข้อความที่ถูกลบจนว่าง เก็บเป็น '' ไม่ใช่ null
            // ถ้าเก็บ null ระบบจะคิดว่า "ยังไม่ได้ตั้ง" แล้วกลับไปใช้ค่า default แทน
            if ($value === null && in_array($def['type'], ['string', 'text'], true)) {
                $value = '';
            }

            $values[$key] = $value;
        }

        Setting::setMany($values);

        // ลบไฟล์เก่าหลังบันทึกสำเร็จแล้วเท่านั้น
        foreach (array_filter($filesToDrop) as $path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()
            ->route('settings.index', $group)
            ->with('ok', 'บันทึกการตั้งค่า' . Setting::groups()[$group]['label'] . 'เรียบร้อย');
    }

    /* ==================== Private helpers ==================== */

    /** จัดรูปแบบค่าก่อนตรวจ ผู้ใช้จะได้ไม่ต้องพิมพ์ให้ตรงเป๊ะ */
    private function normalizeInput(Request $request, string $group): void
    {
        // เลขผู้เสียภาษี: รับ 0-1234-56789-01-2 ได้ เก็บเป็นตัวเลขล้วน
        if ($group === 'company') {
            $company = $request->input('company', []);

            if (! empty($company['tax_id'])) {
                $company['tax_id'] = preg_replace('/\D/', '', $company['tax_id']);
                $request->merge(['company' => $company]);
            }
        }

        // ตัวย่อเอกสาร: พิมพ์ตัวเล็กมาก็แปลงเป็นตัวใหญ่ให้
        if ($group === 'document') {
            $doc = $request->input('doc', []);

            foreach ($doc['prefix'] ?? [] as $type => $prefix) {
                $doc['prefix'][$type] = strtoupper(trim((string) $prefix));
            }

            $request->merge(['doc' => $doc]);
        }
    }

    /**
     * ตัวย่อเอกสารห้ามซ้ำกัน
     * ถ้าใบแจ้งหนี้กับใบเสร็จใช้ตัวย่อเดียวกัน เลขที่เอกสารสองประเภทจะปนกัน
     * และระบบตรวจเลขสูงสุดจะนับเอกสารของอีกประเภทเข้ามาด้วย
     */
    private function duplicatePrefixError(array $validated): ?array
    {
        $seen = [];

        foreach (data_get($validated, 'doc.prefix', []) as $type => $prefix) {
            if (isset($seen[$prefix])) {
                $other = Setting::documentTypes()[$seen[$prefix]]['label'];

                return ["doc.prefix.{$type}" => "ตัวย่อ {$prefix} ซ้ำกับ{$other}"];
            }

            $seen[$prefix] = $type;
        }

        return null;
    }

    /** เลขที่เอกสารถัดไปของทุกประเภท (ตามค่าที่บันทึกแล้ว) */
    private function nextNumbers(DocumentNumberService $numbers): array
    {
        $result = [];

        foreach (array_keys(Setting::documentTypes()) as $type) {
            try {
                $result[$type] = $numbers->peek($type);
            } catch (Throwable) {
                $result[$type] = '-';
            }
        }

        return $result;
    }
}