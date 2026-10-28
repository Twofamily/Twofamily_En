<?php

namespace App\Http\Controllers;

use App\Http\Controllers\RevenueReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /** ค่าสถานะที่ถือว่าแคมป์จบงานแล้ว — แก้ตรงนี้ที่เดียวถ้าค่าใน DB ไม่ตรง */
    private const CAMP_CLOSED = [
        'closed', 'done', 'finished', 'cancelled', 'inactive',
        'ปิดงาน', 'ยกเลิก', 'เสร็จสิ้น', 'จบงาน',
    ];

    public function index(Request $request)
    {
        $data = [];

        // ---------- รายได้ (กรองตามช่วงเวลาที่เลือก) ----------
        $data['revenue'] = (new RevenueReport($request))->toArray();

        // ---------- กองรถ ----------
        $data['truckCounts'] = [
            'total'       => $this->count('trucks'),
            'active'      => $this->count('trucks', fn($q) => $q->where('status_truck', 'active')),
            'maintenance' => $this->count('trucks', fn($q) => $q->where('status_truck', 'maintenance')),
            'retired'     => $this->count('trucks', fn($q) => $q->where('status_truck', 'retired')),
        ];

        // ---------- คนขับ ----------
        $data['driversTotal'] = $this->count('drivers');

        // ---------- แคมป์ ----------
        $data['hasCamps']   = $this->has('camps');
        $data['campsTotal'] = $this->count('camps');

        $data['campsActive'] = $this->count('camps', function ($q) {
            if (Schema::hasColumn('camps', 'status_camp')) {
                $q->where(fn($w) => $w->whereNull('status_camp')
                                      ->orWhereNotIn('status_camp', self::CAMP_CLOSED));
            }
        });

        $data['activeCamps'] = $this->activeCamps();

        // ---------- ลูกหนี้ค้างชำระ ----------
        $data['receivables'] = $this->receivables();

        return view('dashboard', $data);
    }

    /**
     * ยอดค้างรับจากใบแจ้งหนี้ที่ยังชำระไม่ครบ
     * = ยอดใบแจ้งหนี้ − ยอดที่ลูกค้าโอนเข้ามาแล้ว (ตาราง payments)
     * ตัวเลขนี้คือเงินที่บริษัทยังต้องตามเก็บ
     */
    private function receivables(): array
    {
        $empty = ['amount' => 0.0, 'count' => 0];

        if (!$this->has('invoices')) {
            return $empty;
        }

        $q = DB::table('invoices as i')->where('i.status', '!=', 'paid');

        if (Schema::hasColumn('invoices', 'deleted_at')) {
            $q->whereNull('i.deleted_at');
        }

        // ยอดที่ชำระแล้วของแต่ละใบ (ไม่มีตาราง payments = ถือว่ายังไม่ได้ชำระ)
        $paid = $this->has('payments')
            ? '(SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.id_invoice = i.id_invoice)'
            : '0';

        $row = $q->selectRaw("COUNT(*) as cnt, COALESCE(SUM(GREATEST(i.total - $paid, 0)), 0) as amount")
                 ->first();

        return [
            'amount' => (float) ($row->amount ?? 0),
            'count'  => (int) ($row->cnt ?? 0),
        ];
    }

    /** รายการแคมป์ที่ยังดำเนินการอยู่ พร้อมชื่อลูกค้า */
    private function activeCamps()
    {
        if (!$this->has('camps')) {
            return collect();
        }

        $q = DB::table('camps')
            ->whereNull('camps.deleted_at')
            ->select(
                'camps.id_camp',
                'camps.code_camp',
                'camps.name_camp',
                'camps.province',
                'camps.district',
                'camps.status_camp'
            );

        // join ลูกค้า เฉพาะเมื่อมีตารางจริง
        if ($this->has('customers')) {
            $nameCol = $this->firstColumn('customers', [
                'name_customer', 'customer_name', 'company_name', 'name',
            ]);

            $q->leftJoin('customers', 'camps.id_customer', '=', 'customers.id_customer');

            if ($nameCol) {
                $q->addSelect('customers.' . $nameCol . ' as customer_label');
            }
        }

        if (Schema::hasColumn('camps', 'status_camp')) {
            $q->where(fn($w) => $w->whereNull('camps.status_camp')
                                  ->orWhereNotIn('camps.status_camp', self::CAMP_CLOSED));
        }

        return $q->orderByDesc('camps.created_at')->limit(6)->get();
    }

    // ================= helper =================

    /** ตารางมีอยู่จริงไหม */
    private function has(string $table): bool
    {
        return Schema::hasTable($table);
    }

    /** หาคอลัมน์แรกที่มีอยู่จริงจากรายการที่เป็นไปได้ */
    private function firstColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $col) {
            if (Schema::hasColumn($table, $col)) {
                return $col;
            }
        }

        return null;
    }

    /** สร้าง query พร้อมกรองข้อมูลที่ถูกลบแล้ว (soft delete) */
    private function table(string $table)
    {
        $q = DB::table($table);

        if (Schema::hasColumn($table, 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        return $q;
    }

    private function count(string $table, ?callable $filter = null): int
    {
        if (!$this->has($table)) return 0;

        $q = $this->table($table);
        if ($filter) $filter($q);

        return (int) $q->count();
    }
}