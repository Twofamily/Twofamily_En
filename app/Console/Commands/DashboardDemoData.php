<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * สร้างข้อมูลตัวอย่างย้อนหลัง 12 เดือน เพื่อให้กราฟรายได้/ต้นทุนบนแดชบอร์ดเห็นภาพ
 *
 * ทุกแถวที่สร้างจะมีเครื่องหมาย DEMO กำกับไว้ จึงลบออกได้หมดด้วย --clear
 * โดยไม่กระทบข้อมูลจริงที่มีอยู่
 *
 *   php artisan dashboard:demo           สร้าง (ลบของเก่าทิ้งก่อน)
 *   php artisan dashboard:demo --clear   ลบอย่างเดียว
 */
class DashboardDemoData extends Command
{
    protected $signature = 'dashboard:demo {--clear : ลบข้อมูลตัวอย่างออกอย่างเดียว ไม่สร้างใหม่}';

    protected $description = 'สร้าง/ลบข้อมูลตัวอย่างรายได้และต้นทุนย้อนหลัง 12 เดือน สำหรับแดชบอร์ด';

    /** เครื่องหมายที่ใช้ระบุว่าแถวไหนเป็นข้อมูลตัวอย่าง */
    private const MARK = 'DEMO';

    /** ยอดขายต่อเดือน (บาท) — ขยับขึ้นลงให้เหมือนงานจริง มีช่วงหน้าฝนที่งานน้อย */
    private const MONTHLY_SALES = [
        182_000, 215_000, 168_000, 243_000, 291_000, 205_000,
        134_000, 156_000, 248_000, 312_000, 268_000, 224_000,
    ];

    /** สัดส่วนค่าน้ำมันต่อยอดขายในแต่ละเดือน — ระยะทางไม่เท่ากัน ต้นทุนจึงไม่คงที่ */
    private const FUEL_RATIO = [
        .34, .31, .38, .29, .27, .35,
        .42, .39, .30, .26, .28, .33,
    ];

    /** สัดส่วนที่เป็นงานบริการแคมป์ ที่เหลือเป็นการขายสินค้าส่งตรงให้ลูกค้า */
    private const CAMP_SHARE = [
        .72, .65, .58, .70, .76, .61,
        .54, .63, .71, .68, .59, .66,
    ];

    /** ค่าซ่อมรถ — เกิดเป็นก้อนบางเดือน ไม่ได้มีทุกเดือน */
    private const MAINTENANCE = [
        0, 18_500, 0, 0, 42_000, 0,
        9_800, 0, 27_400, 0, 15_200, 0,
    ];

    public function handle(): int
    {
        $this->clear();

        if ($this->option('clear')) {
            $this->info('ลบข้อมูลตัวอย่างออกแล้ว');

            return self::SUCCESS;
        }

        $customer = DB::table('customers')->value('id_customer');
        $camps = DB::table('camps')->whereNull('deleted_at')->pluck('id_camp')->all();
        $trucks = DB::table('trucks')->whereNull('deleted_at')->pluck('id_truck')->all();
        $products = DB::table('products')->whereNull('deleted_at')
            ->select('id_product', 'unit_price')->get()->all();

        if (! $customer || ! $camps || ! $trucks || ! $products) {
            $this->error('ต้องมีลูกค้า แคมป์ รถ และสินค้าอย่างน้อยอย่างละ 1 รายการก่อน');

            return self::FAILURE;
        }

        // เริ่มที่เดือนแรกของช่วง 12 เดือนล่าสุด ให้ตรงกับค่าเริ่มต้นของแดชบอร์ด
        $month = now()->copy()->startOfMonth()->subMonths(11);
        $now = now();
        $created = ['dn' => 0, 'fuel' => 0, 'maint' => 0];

        DB::transaction(function () use ($month, $now, $customer, $camps, $trucks, $products, &$created) {
            foreach (self::MONTHLY_SALES as $i => $sales) {
                $m = $month->copy()->addMonths($i);

                $campSales = (int) round($sales * self::CAMP_SHARE[$i]);

                // งานแคมป์ (ผูก id_camp) กับขายสินค้า (ไม่ผูกแคมป์) — คนละสายรายได้บนแดชบอร์ด
                $created['dn'] += $this->makeDeliveries($m, $campSales, $customer, $camps, $products, $now, 'C');
                $created['dn'] += $this->makeDeliveries($m, $sales - $campSales, $customer, null, $products, $now, 'P');
                $created['fuel'] += $this->makeFuel($m, (int) round($sales * self::FUEL_RATIO[$i]), $trucks, $now);

                if (self::MAINTENANCE[$i] > 0) {
                    $this->makeMaintenance($m, self::MAINTENANCE[$i], $trucks, $now);
                    $created['maint']++;
                }
            }
        });

        $this->info(sprintf(
            'สร้างข้อมูลตัวอย่างแล้ว: ใบส่งของ %d · บันทึกน้ำมัน %d · ใบซ่อม %d',
            $created['dn'], $created['fuel'], $created['maint']
        ));
        $this->line('ลบออกได้ด้วย: php artisan dashboard:demo --clear');

        return self::SUCCESS;
    }

    /** ลบเฉพาะแถวที่มีเครื่องหมาย DEMO — ข้อมูลจริงไม่ถูกแตะ */
    private function clear(): void
    {
        DB::transaction(function () {
            $ids = DB::table('delivery_notes')->where('code_dn', 'like', self::MARK.'-%')
                ->pluck('id_delivery_note');

            DB::table('delivery_note_details')->whereIn('id_delivery_note', $ids)->delete();
            DB::table('delivery_notes')->whereIn('id_delivery_note', $ids)->delete();

            DB::table('fuel_records')->where('start_detail', self::MARK)->delete();
            DB::table('truck_maintenances')->where('title', 'like', '['.self::MARK.']%')->delete();
        });
    }

    /**
     * กระจายยอดขายของเดือนออกเป็นใบส่งของ 3–5 ใบ ลงวันที่คนละวัน
     *
     * $camps = null คือใบส่งของแบบขายสินค้า ไม่ผูกแคมป์ (id_camp เป็น NULL)
     * $kind ใช้แยกรหัสใบไม่ให้ชนกันระหว่างสองสาย
     */
    private function makeDeliveries($month, int $sales, $customer, ?array $camps, array $products, $now, string $kind): int
    {
        $notes = 3 + ($month->month % 3);           // 3–5 ใบ ต่างกันไปตามเดือน
        $perNote = (int) floor($sales / $notes);
        $count = 0;

        for ($n = 0; $n < $notes; $n++) {
            $offset = $kind === 'P' ? 3 : 0;
            $day = min(2 + $n * 6 + ($month->month % 5) + $offset, $month->daysInMonth);
            $date = $month->copy()->setDay($day);

            $id = DB::table('delivery_notes')->insertGetId([
                'code_dn' => sprintf('%s-%s%s-%02d', self::MARK, $month->format('ym'), $kind, $n + 1),
                'id_camp' => $camps === null ? null : $camps[($month->month + $n) % count($camps)],
                'id_customer' => $customer,
                'delivery_date' => $date->toDateString(),
                'status' => 'delivered',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // แบ่งยอดของใบนี้ลงสินค้า 2 รายการ ให้จำนวนลงตัวกับราคาต่อหน่วยจริง
            $remaining = $perNote;

            foreach ([0, 1] as $k) {
                $p = $products[($month->month + $n + $k) % count($products)];
                $price = (float) $p->unit_price;
                $target = $k === 0 ? $remaining * 0.6 : $remaining;
                $qty = max(1, (int) round($target / max($price, 1)));
                $total = $qty * $price;

                DB::table('delivery_note_details')->insert([
                    'id_delivery_note' => $id,
                    'id_product' => $p->id_product,
                    'quantity' => $qty,
                    'price_per_unit' => $price,
                    'total_price' => $total,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $remaining -= $total;
                if ($remaining <= 0) {
                    break;
                }
            }

            $count++;
        }

        return $count;
    }

    /** ค่าน้ำมันของเดือน แตกเป็นหลายเที่ยว กระจายรถคนละคัน */
    private function makeFuel($month, int $monthlyCost, array $trucks, $now): int
    {
        $trips = 4;
        $perTrip = (int) round($monthlyCost / $trips);
        $count = 0;

        for ($t = 0; $t < $trips; $t++) {
            $day = min(3 + $t * 7, $month->daysInMonth);

            DB::table('fuel_records')->insert([
                'date_record' => $month->copy()->setDay($day)->toDateString(),
                'start_point' => 'ลานกองวัสดุ',
                'start_detail' => self::MARK,
                'destination' => 'หน้างานแคมป์',
                'destination_detail' => self::MARK,
                'distance' => 60 + $t * 25,
                'cost_fuel_total' => $perTrip,
                'total_fuel_liters' => round($perTrip / 32, 2),
                'trucks_id_truck' => $trucks[($month->month + $t) % count($trucks)],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $count++;
        }

        return $count;
    }

    private function makeMaintenance($month, int $cost, array $trucks, $now): void
    {
        $day = min(12, $month->daysInMonth);

        DB::table('truck_maintenances')->insert([
            'id_truck' => $trucks[$month->month % count($trucks)],
            'title' => '['.self::MARK.'] ซ่อมบำรุงตามรอบ',
            'detail' => 'ข้อมูลตัวอย่างสำหรับแดชบอร์ด',
            'garage' => 'อู่ประจำ',
            'cost' => $cost,
            'start_date' => $month->copy()->setDay($day)->toDateString(),
            'finished_date' => $month->copy()->setDay(min($day + 2, $month->daysInMonth))->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
