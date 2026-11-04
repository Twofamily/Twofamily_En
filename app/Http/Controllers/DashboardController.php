<?php

namespace App\Http\Controllers;

use App\Services\DashboardFinance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $report = new RevenueReport($request);
        $finance = new DashboardFinance($report->from(), $report->to());

        return view('dashboard', [
            'revenue'    => $report->toArray(),
            'kpi'        => $finance->kpis(),
            'campProfit' => $finance->campProfit(),
            'aging'      => $finance->receivableAging(),
            'truckCosts' => $finance->truckCosts(),
            'backlog'    => $finance->backlog(),
            'ops'        => $this->operations(),
        ]);
    }

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
