<?php

namespace App\Http\Controllers;

use App\Services\DashboardFinance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * แดชบอร์ดการเงิน: รายได้ ต้นทุน กำไร ลูกหนี้ งานในมือ
 *
 * ช่วงเวลามาจากตัวกรองด้านบน (RevenueReport จัดการ preset / วันที่)
 * แล้วส่งช่วงเดียวกันให้ DashboardFinance คำนวณส่วนที่เหลือ ตัวเลขทั้งหน้าจึงตรงกันเสมอ
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $report = new RevenueReport($request);
        $finance = new DashboardFinance($report->from(), $report->to());

        return view('dashboard', [
            'revenue'    => $report->toArray(),       // ตัวกรอง + กราฟรายเดือน
            'kpi'        => $finance->kpis(),
            'campProfit' => $finance->campProfit(),
            'aging'      => $finance->receivableAging(),
            'truckCosts' => $finance->truckCosts(),
            'backlog'    => $finance->backlog(),
            'ops'        => $this->operations(),
        ]);
    }

    /** ตัวเลขการปฏิบัติงาน (ณ วันนี้) แสดงแถวเล็กท้ายหน้า */
    private function operations(): array
    {
        $trucks = DB::table('trucks')
            ->whereNull('deleted_at')
            ->selectRaw('status_truck, COUNT(*) as total')
            ->groupBy('status_truck')
            ->pluck('total', 'status_truck');

        return [
            'trucks'      => (int) $trucks->sum(),
            'active'      => (int) ($trucks['active'] ?? 0),
            'maintenance' => (int) ($trucks['maintenance'] ?? 0),
            'drivers'     => DB::table('drivers')->whereNull('deleted_at')->count(),
            'camps'       => DB::table('camps')->whereNull('deleted_at')->where('status_camp', '!=', 'closed')->count(),
        ];
    }
}
