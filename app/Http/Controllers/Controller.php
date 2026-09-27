<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * ตัวเลือกจำนวนรายการต่อหน้าที่ระบบยอมรับ (ใช้ร่วมกันทุกหน้ารายการ)
     *
     * ประกาศไว้ที่ class แม่ เพื่อให้ทุกหน้ารายการใช้ชุดเดียวกัน
     * และกันไม่ให้ผู้ใช้ยัด ?per_page=999999 ผ่าน URL จนดึงข้อมูลทั้งตาราง
     */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /** จำนวนรายการต่อหน้าเริ่มต้น */
    public const DEFAULT_PER_PAGE = 10;

    /**
     * อ่านจำนวนรายการต่อหน้าจาก query string แล้วตรวจกับ whitelist
     * ค่าไม่ถูกต้อง → ใช้ค่าเริ่มต้น
     */
    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', static::DEFAULT_PER_PAGE);

        return in_array($perPage, static::PER_PAGE_OPTIONS, true)
            ? $perPage
            : static::DEFAULT_PER_PAGE;
    }

    /** อ่านคำค้นหา (ตัดช่องว่างหัวท้าย) */
    protected function searchTerm(Request $request): string
    {
        return trim((string) $request->input('search', ''));
    }

    /**
     * อ่านตัวกรองสถานะ แล้วตรวจกับรายการที่อนุญาต
     * ไม่อยู่ในรายการ → null (แสดงทุกสถานะ)
     */
    protected function statusFilter(Request $request, array $allowed): ?string
    {
        $status = $request->input('status');

        return in_array($status, $allowed, true) ? $status : null;
    }
}