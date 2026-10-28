<?php

namespace Database\Seeders;

use App\Models\Camp;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteDetail;
use App\Models\Driver;
use App\Models\FuelRecord;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Receipt;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\Truck;
use App\Models\TruckMaintenance;
use App\Models\TruckModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * ข้อมูลตัวอย่างสำหรับเดโม — เอกสารครบทั้งสาย ทุกสถานะที่ระบบรองรับ
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * วันที่ทุกอย่างนับถอยหลังจาก "วันนี้" รันวันไหนก็ได้ภาพเดียวกัน
 * รันได้ครั้งเดียว (เจอลูกค้าตัวอย่างอยู่แล้วจะหยุด) — อยากเริ่มใหม่ให้ใช้ php artisan migrate:fresh --seed ก่อน
 *
 *   แคมป์ A  กำลังดำเนินงาน  ใบเสนอราคา → ใบสั่งขาย → ส่งของ 6 ใบ → ใบแจ้งหนี้ชำระครบ + ออกใบเสร็จ
 *                             ใบแจ้งหนี้ชำระบางส่วน · ใบส่งของรอออกบิล 2 ใบ · ใบส่งของ/ใบแจ้งหนี้ที่ยกเลิก
 *   แคมป์ B  กำลังดำเนินงาน  ใบสั่งขายอย่างเดียว (ไม่มีใบเสนอราคา) · รับเงินล่วงหน้า → หักในใบแจ้งหนี้
 *                             ใบแจ้งหนี้เลยกำหนดชำระ
 *   แคมป์ C  เตรียมงาน      เปิดแคมป์แล้ว ยังไม่ได้ส่งของ
 *   แคมป์ D  ปิดแคมป์แล้ว    ส่งครบ เก็บเงินครบ ถอนรถออกแล้ว
 *   ขายสด    ลูกค้าบุคคล ซื้อแล้วจ่ายเงินสดทันที · ใบเสนอราคาร่าง 1 ใบ
 */
class DemoDataSeeder extends Seeder
{
    private const MARK_CUSTOMER = 'บริษัท สยามพัฒนาก่อสร้าง จำกัด';

    /** ราคาน้ำมันดีเซลต่อลิตร ใช้คิดค่าน้ำมันของแต่ละเที่ยว */
    private const DIESEL = 31.94;

    private User $admin;
    private User $staff;
    private Carbon $today;

    public function run(): void
    {
        if (Customer::where('name_customer', self::MARK_CUSTOMER)->exists()) {
            $this->command?->warn('มีข้อมูลตัวอย่างอยู่แล้ว ข้ามการสร้าง (เริ่มใหม่: php artisan migrate:fresh --seed แล้วรันอีกครั้ง)');

            return;
        }

        // ต้องมีผู้ใช้ ประเภทสินค้า และรุ่นรถก่อน
        $this->call([AdminUserSeeder::class, CompanySettingSeeder::class, ProductTypesSeeder::class, TruckModelSeeder::class]);

        $this->today = Carbon::today();

        DB::transaction(function () {
            $this->users();
            $p = $this->products();
            $c = $this->customers();
            [$t, $d] = $this->trucksAndDrivers();

            $this->campA($c['siam'], $p, $t, $d);
            $this->campB($c['korat'], $p, $t, $d);
            $this->campC($c['pakchong'], $p);
            $this->campD($c['siam'], $p, $t, $d);
            $this->walkIn($c['somchai'], $p);

            $this->runEvents();

            TruckMaintenance::create([
                'id_truck'        => $t[1]->id_truck,
                'title'           => 'เปลี่ยนผ้าเบรกและน้ำมันเครื่อง',
                'detail'          => 'ตรวจเช็กระยะ 120,000 กม.',
                'garage'          => 'อู่ช่างเอก สูงเนิน',
                'cost'            => 8500,
                'start_date'      => $this->ago(33),
                'expected_return' => $this->ago(31),
                'finished_date'   => $this->ago(31),
            ]);
        });

        $this->command?->info('สร้างข้อมูลตัวอย่างเรียบร้อย — เข้าระบบ admin@admin.com / admin1234 หรือ staff@twofamily.test / staff1234');
    }

    /* ==================== ข้อมูลหลัก ==================== */

    private function users(): void
    {
        $this->admin = User::where('email', 'admin@admin.com')->firstOrFail();
        $this->admin->update(['position' => 'กรรมการผู้จัดการ']);

        $this->staff = User::updateOrCreate(
            ['email' => 'staff@twofamily.test'],
            [
                'name'      => 'สมหญิง ขยันงาน',
                'password'  => Hash::make('staff1234'),
                'role'      => 'staff',
                'is_active' => 1,
                'position'  => 'เจ้าหน้าที่ฝ่ายขาย',
            ]
        );
    }

    private function products(): array
    {
        $type = fn (string $name) => ProductType::firstOrCreate(['name_product_type' => $name])->id_product_type;

        $make = fn (string $name, float $price, string $typeName, string $detail) => Product::create([
            'name_product'    => $name,
            'detail_product'  => $detail,
            'unit_price'      => $price,
            'product_type_id' => $type($typeName),
        ]);

        return [
            'fill'   => $make('ดินถม', 300, 'ดิน', 'ดินถมทั่วไป ราคาต่อคิว ส่งถึงหน้างาน'),
            'laterite' => $make('ดินลูกรัง', 350, 'ดิน', 'ดินลูกรังบดอัดได้ สำหรับทำถนน'),
            'sand'   => $make('ทรายหยาบ', 450, 'ทราย', 'ทรายหยาบงานคอนกรีต'),
            'fsand'  => $make('ทรายถม', 380.50, 'ทราย', 'ทรายถมปรับระดับ'),
            'crush'  => $make('หินคลุก', 550, 'หิน', 'หินคลุกรองพื้นถนน'),
            'stone'  => $make('หิน 3/4', 650, 'หิน', 'หินเบอร์ 3/4 งานคอนกรีต'),
        ];
    }

    private function customers(): array
    {
        $make = fn (array $a) => Customer::create($a + ['province' => 'นครราชสีมา']);

        return [
            'siam' => $make([
                'name_customer'  => self::MARK_CUSTOMER,
                'customer_type'  => 'company',
                'phone_customer' => '0441234567',
                'email_customer' => 'purchase@siamphatthana.test',
                'address_detail' => '99/9 ถนนมิตรภาพ',
                'subdistrict'    => 'ในเมือง',
                'district'       => 'เมืองนครราชสีมา',
                'zipcode'        => '30000',
            ]),
            'korat' => $make([
                'name_customer'  => 'ห้างหุ้นส่วนจำกัด โคราชวิศวกรรม',
                'customer_type'  => 'company',
                'phone_customer' => '0449876543',
                'address_detail' => '45 หมู่ 3',
                'subdistrict'    => 'หนองระเวียง',
                'district'       => 'เมืองนครราชสีมา',
                'zipcode'        => '30000',
            ]),
            'pakchong' => $make([
                'name_customer'  => 'บริษัท ปากช่องแลนด์ จำกัด',
                'customer_type'  => 'company',
                'phone_customer' => '0443112233',
                'address_detail' => '12 ถนนธนะรัชต์',
                'subdistrict'    => 'หมูสี',
                'district'       => 'ปากช่อง',
                'zipcode'        => '30130',
            ]),
            'somchai' => $make([
                'name_customer'  => 'นายสมชาย ใจดี',
                'customer_type'  => 'person',
                'phone_customer' => '0812345678',
                'address_detail' => '7 หมู่ 14',
                'subdistrict'    => 'สูงเนิน',
                'district'       => 'สูงเนิน',
                'zipcode'        => '30170',
            ]),
        ];
    }

    private function trucksAndDrivers(): array
    {
        $models = TruckModel::with('brand')->get()->keyBy('name_model');

        $truck = function (string $plate, string $model, int $year) use ($models) {
            $m = $models[$model];

            return Truck::create([
                'id_truck'          => $plate,
                'truck_brand_id'    => $m->truck_brand_id,
                'truck_model_id'    => $m->getKey(),
                'year_truck'        => $year,
                'province_truck'    => 'นครราชสีมา',
                'fuel_rate'         => $m->fuel_rate,
                'weight_truck'      => $m->curb_weight,
                'fuelfactory_truck' => $m->tank_capacity,
                'cubic_capacity'    => $m->cubic_capacity,
                'status_truck'      => 'active',
            ]);
        };

        $trucks = [
            $truck('70-1001', 'FXZ', 2020),
            $truck('70-1002', 'FXZ', 2021),
            $truck('70-2201', 'Victor 500', 2022),
            $truck('70-3305', 'FVZ', 2019),
        ];

        $driver = fn (string $f, string $l, string $phone) => Driver::create([
            'fname_driver' => $f,
            'lname_driver' => $l,
            'phone_driver' => $phone,
            'subdistrict'  => 'สูงเนิน',
            'district'     => 'สูงเนิน',
            'province'     => 'นครราชสีมา',
            'zipcode'      => '30170',
        ]);

        $drivers = [
            $driver('สมศักดิ์', 'แข็งแรง', '0891112222'),
            $driver('ประยุทธ', 'ขับดี', '0892223333'),
            $driver('วิชัย', 'มั่นคง', '0893334444'),
            $driver('บุญมี', 'ทนทาน', '0894445555'),
        ];

        return [$trucks, $drivers];
    }

    /* ==================== แคมป์ ==================== */

    /** แคมป์ A — งานใหญ่ กำลังดำเนินงาน ครบทุกสถานะของใบแจ้งหนี้ */
    private function campA(Customer $cus, array $p, array $t, array $d): void
    {
        $items = [[$p['fill'], 2000, 300], [$p['laterite'], 500, 350]];
        $x = new \stdClass;

        $this->at(62, fn () => $x->quot = $this->quotation($cus, $items, 62, 'approved'));
        $this->at(58, fn () => $x->so = $this->salesOrder($cus, $items, 58, $x->quot, 'PO-SP-2569-014'));
        $this->at(58, fn () => $x->camp = $this->camp($cus, $x->so, 'ถมดินโครงการหมู่บ้านสยามวิลล์', 'active', [
            'address_detail' => 'ถนนเลี่ยงเมือง กม.12',
            'subdistrict'    => 'หัวทะเล',
            'district'       => 'เมืองนครราชสีมา',
            'zipcode'        => '30000',
            'latitude'       => 14.9442,
            'longitude'      => 102.1301,
            'contact_name'   => 'คุณวีระ (โฟร์แมน)',
            'contact_phone'  => '0861234567',
        ], $x->quot));

        $this->at(52, function () use ($x, $t, $d) {
            $this->assign($x->camp, $t[0], $d[0], 52);
            $this->assign($x->camp, $t[1], $d[1], 52);
        });

        $this->at(45, fn () => $x->dn1 = $this->deliveryNote($x->camp, 45, [[$p['fill'], 400, 300]], $t[0], 18));
        $this->at(38, fn () => $x->dn2 = $this->deliveryNote($x->camp, 38, [[$p['fill'], 450, 300], [$p['laterite'], 100, 350]], $t[1], 18));
        $this->at(30, fn () => $x->dn3 = $this->deliveryNote($x->camp, 30, [[$p['fill'], 500, 300]], $t[0], 18));
        $this->at(20, fn () => $x->dn4 = $this->deliveryNote($x->camp, 20, [[$p['fill'], 380, 300], [$p['laterite'], 150, 350]], $t[1], 18));
        $this->at(8, fn () => $x->dn5 = $this->deliveryNote($x->camp, 8, [[$p['fill'], 300, 300]], $t[0], 18));
        $this->at(3, fn () => $this->deliveryNote($x->camp, 3, [[$p['laterite'], 120, 350]], $t[1], 18));

        // ใบส่งของที่ยกเลิก — บันทึกจำนวนผิด
        $this->at(30, function () use ($x, $p) {
            $wrong = $this->deliveryNote($x->camp, 30, [[$p['fill'], 50, 300]], null, 0);
            $wrong->cancel('บันทึกจำนวนผิด (ส่งจริง 500 คิว ออกใบใหม่แทนแล้ว)', $this->staff->id);
        });

        // บิลรอบที่ 1 — ชำระครบ ออกใบเสร็จแล้ว
        $this->at(37, fn () => $x->inv1 = $this->invoice([$x->dn1, $x->dn2], 37, 30, billReceived: 35));
        $this->at(10, fn () => $this->pay($x->inv1, 10, $x->inv1->total, 'transfer', 'ธนาคารกรุงเทพ', 'TRF-880211'));
        $this->at(9, fn () => $this->receiptFromInvoice($x->inv1, 9));

        // บิลรอบที่ 2 — ชำระบางส่วน ยังไม่ถึงกำหนด
        $this->at(19, fn () => $x->inv2 = $this->invoice([$x->dn3, $x->dn4], 19, 30, billReceived: 17));
        $this->at(4, fn () => $this->pay($x->inv2, 4, 100000, 'cheque', 'ธนาคารกสิกรไทย', 'CHQ-1029384'));

        // ใบแจ้งหนี้ที่ออกผิดรอบ แล้วยกเลิก — ใบส่งของกลับไปรอออกบิล
        $this->at(6, function () use ($x) {
            $wrongInv = $this->invoice([$x->dn5], 6, 30);
            DeliveryNote::where('id_invoice', $wrongInv->id_invoice)->update(['id_invoice' => null]);
            $wrongInv->cancel('ออกผิดรอบวางบิล ลูกค้าขอรวมกับรอบถัดไป (ใบส่งของ: ' . $x->dn5->code_dn . ')', $this->admin->id);
        });
    }

    /** แคมป์ B — ไม่มีใบเสนอราคา · รับเงินล่วงหน้า · ใบแจ้งหนี้เลยกำหนด */
    private function campB(Customer $cus, array $p, array $t, array $d): void
    {
        $items = [[$p['fsand'], 600, 380.50], [$p['crush'], 400, 550]];
        $x = new \stdClass;

        $this->at(44, fn () => $x->so = $this->salesOrder($cus, $items, 44, null, null));
        $this->at(44, fn () => $x->camp = $this->camp($cus, $x->so, 'ปรับพื้นที่ก่อสร้างโรงงานโคราชวิศวกรรม', 'active', [
            'address_detail' => 'นิคมอุตสาหกรรมสุรนารี ซอย 5',
            'subdistrict'    => 'หนองระเวียง',
            'district'       => 'เมืองนครราชสีมา',
            'zipcode'        => '30000',
            'contact_name'   => 'คุณอนันต์',
            'contact_phone'  => '0819876543',
        ]));

        $this->at(42, fn () => $this->assign($x->camp, $t[2], $d[2], 42));
        $this->at(40, fn () => $x->advance = $this->advanceReceipt($x->camp, 40, 100000));

        $this->at(35, fn () => $x->dn1 = $this->deliveryNote($x->camp, 35, [[$p['fsand'], 200, 380.50]], $t[2], 24));
        $this->at(28, fn () => $x->dn2 = $this->deliveryNote($x->camp, 28, [[$p['crush'], 150, 550]], $t[2], 24));
        $this->at(15, fn () => $this->deliveryNote($x->camp, 15, [[$p['fsand'], 180, 380.50], [$p['crush'], 100, 550]], $t[2], 24));

        // เครดิต 15 วัน → เลยกำหนดแล้ว
        $this->at(27, fn () => $x->inv = $this->invoice([$x->dn1, $x->dn2], 27, 15, billReceived: 26));
        $this->at(25, fn () => $this->applyAdvance($x->inv, $x->advance, 25, 100000));
    }

    /** แคมป์ C — เพิ่งเปิด ยังไม่ส่งของ */
    private function campC(Customer $cus, array $p): void
    {
        $items = [[$p['fill'], 1500, 290], [$p['sand'], 200, 450]];
        $x = new \stdClass;

        $this->at(10, fn () => $x->quot = $this->quotation($cus, $items, 10, 'approved'));
        $this->at(7, fn () => $x->so = $this->salesOrder($cus, $items, 7, $x->quot, 'PO-PCL-0091'));
        $this->at(7, fn () => $this->camp($cus, $x->so, 'ถมที่ดินโครงการปากช่องแลนด์ เฟส 2', 'preparing', [
            'address_detail' => 'ถนนธนะรัชต์ กม.8',
            'subdistrict'    => 'หมูสี',
            'district'       => 'ปากช่อง',
            'zipcode'        => '30130',
            'contact_name'   => 'คุณกมล',
            'contact_phone'  => '0827654321',
        ], $x->quot));
    }

    /** แคมป์ D — งานเสร็จ เก็บเงินครบ ปิดแคมป์แล้ว */
    private function campD(Customer $cus, array $p, array $t, array $d): void
    {
        $items = [[$p['stone'], 300, 650]];
        $x = new \stdClass;

        $this->at(95, fn () => $x->quot = $this->quotation($cus, $items, 95, 'approved'));
        $this->at(90, fn () => $x->so = $this->salesOrder($cus, $items, 90, $x->quot, 'PO-SP-2569-006'));
        $this->at(90, fn () => $x->camp = $this->camp($cus, $x->so, 'ถนนทางเข้าโครงการสยามวิลล์ เฟส 1', 'active', [
            'address_detail' => 'ถนนเลี่ยงเมือง กม.11',
            'subdistrict'    => 'หัวทะเล',
            'district'       => 'เมืองนครราชสีมา',
            'zipcode'        => '30000',
        ], $x->quot));

        $this->at(86, fn () => $x->assignment = $this->assign($x->camp, $t[3], $d[3], 86));

        $this->at(80, fn () => $x->dn1 = $this->deliveryNote($x->camp, 80, [[$p['stone'], 160, 650]], $t[3], 20));
        $this->at(70, fn () => $x->dn2 = $this->deliveryNote($x->camp, 70, [[$p['stone'], 140, 650]], $t[3], 20));

        $this->at(69, fn () => $x->inv = $this->invoice([$x->dn1, $x->dn2], 69, 30, billReceived: 68));
        $this->at(68, fn () => DB::table('camp_truck')->where('id_assignment', $x->assignment)->update(['released_date' => $this->ago(68)]));
        $this->at(50, fn () => $this->pay($x->inv, 50, $x->inv->total, 'transfer', 'ธนาคารไทยพาณิชย์', 'TRF-551203'));
        $this->at(49, function () use ($x) {
            $this->receiptFromInvoice($x->inv, 49);
            $x->so->update(['status' => 'closed']);
            $x->camp->update(['status_camp' => 'closed']);
        });
    }

    /** ขายสด + ใบเสนอราคาร่าง ของลูกค้าบุคคล */
    private function walkIn(Customer $cus, array $p): void
    {
        $this->at(5, function () use ($cus, $p) {
            $subtotal = 2700;
            $vat      = round($subtotal * Invoice::VAT_RATE / 100, 2);

            $receipt = Receipt::create([
                'receipt_type'      => Receipt::TYPE_DIRECT,
                'id_customer'       => $cus->id_customer,
                'subtotal'          => $subtotal,
                'vat_amount'        => $vat,
                'total'             => $subtotal + $vat,
                'date_receipt'      => $this->ago(5),
                'issued_by_user_id' => $this->staff->id,
            ]);
            $receipt->items()->create(['id_product' => $p['sand']->id_product, 'quantity' => 6, 'price' => 450, 'total' => 2700]);
            $receipt->payments()->create([
                'paid_at'             => $this->ago(5),
                'amount'              => $subtotal + $vat,
                'method'              => 'cash',
                'note'                => 'รับเงินสด 3,000.00 บาท ทอน ' . number_format(3000 - $subtotal - $vat, 2) . ' บาท',
                'recorded_by_user_id' => $this->staff->id,
            ]);
        });

        $this->at(2, fn () => $this->quotation($cus, [[$p['crush'], 20, 550]], 2, 'draft'));
    }

    /* ==================== ลำดับเหตุการณ์ ==================== */

    /** งานที่รอทำ เรียงตามวันที่ — เลขที่เอกสารจะได้เรียงตามวันจริง เหมือนออกทีละใบในระบบ */
    private array $events = [];

    private function at(int $daysAgo, \Closure $fn): void
    {
        $this->events[] = [$daysAgo, count($this->events), $fn];
    }

    private function runEvents(): void
    {
        usort($this->events, fn ($a, $b) => [$b[0], $a[1]] <=> [$a[0], $b[1]]);

        foreach ($this->events as [$daysAgo, , $fn]) {
            // เวลาสร้างเอกสาร = วันที่ของเหตุการณ์ (หน้ารายการเรียงตามวันจริง)
            Carbon::setTestNow($this->today->copy()->subDays($daysAgo)->setTime(10, 0));
            $fn();
        }

        Carbon::setTestNow();
        $this->events = [];
    }

    /* ==================== ตัวช่วยสร้างเอกสาร ==================== */

    private function ago(int $days): string
    {
        return $this->today->copy()->subDays($days)->toDateString();
    }

    /** @param array<int, array{0: Product, 1: float, 2: float}> $items */
    private function totals(array $items): array
    {
        $subtotal = round(collect($items)->sum(fn ($i) => $i[1] * $i[2]), 2);
        $vat      = round($subtotal * Invoice::VAT_RATE / 100, 2);

        return [$subtotal, $vat, round($subtotal + $vat, 2)];
    }

    private function quotation(Customer $cus, array $items, int $daysAgo, string $status): Quotation
    {
        [$subtotal, $vat, $total] = $this->totals($items);
        $date = $this->today->copy()->subDays($daysAgo);

        $q = Quotation::create([
            'id_customer'         => $cus->id_customer,
            'date_quot'           => $date->toDateString(),
            'end_quot'            => $date->copy()->addDays(30)->toDateString(),
            'status'              => $status,
            'issued_by_user_id'   => $this->staff->id,
            'issued_at'           => $date,
            'approved_by_user_id' => $status === 'approved' ? $this->admin->id : null,
            'approved_at'         => $status === 'approved' ? $date->copy()->addDay() : null,
            'subtotal'            => $subtotal,
            'discount'            => 0,
            'vat_rate'            => Invoice::VAT_RATE,
            'vat_amount'          => $vat,
            'total_amount'        => $total,
        ]);

        foreach ($items as [$product, $qty, $price]) {
            QuotationDetail::create([
                'id_quot'        => $q->id_quot,
                'id_product'     => $product->id_product,
                'quantity'       => $qty,
                'price_per_unit' => $price,
                'total_price'    => round($qty * $price, 2),
            ]);
        }

        return $q;
    }

    private function salesOrder(Customer $cus, array $items, int $daysAgo, ?Quotation $quot, ?string $po): SalesOrder
    {
        [$subtotal, $vat, $total] = $this->totals($items);

        $so = SalesOrder::create([
            'id_quot'      => $quot?->id_quot,
            'id_customer'  => $cus->id_customer,
            'order_date'   => $this->ago($daysAgo),
            'due_date'     => $this->today->copy()->subDays($daysAgo)->addDays(90)->toDateString(),
            'po_number'    => $po,
            'status'       => 'confirmed',
            'subtotal'     => $subtotal,
            'discount'     => 0,
            'vat_amount'   => $vat,
            'total_amount' => $total,
        ]);

        foreach ($items as [$product, $qty, $price]) {
            SalesOrderDetail::create([
                'id_so'          => $so->id_so,
                'id_product'     => $product->id_product,
                'quantity'       => $qty,
                'price_per_unit' => $price,
                'total_price'    => round($qty * $price, 2),
            ]);
        }

        return $so;
    }

    private function camp(Customer $cus, SalesOrder $so, string $name, string $status, array $site, ?Quotation $quot = null): Camp
    {
        $camp = Camp::create($site + [
            'id_customer' => $cus->id_customer,
            'id_quot'     => $quot?->id_quot,
            'code_camp'   => Camp::generateCode(),
            'name_camp'   => $name,
            'province'    => 'นครราชสีมา',
            'status_camp' => $status,
        ]);

        $so->update(['id_camp' => $camp->id_camp, 'status' => 'in_progress']);

        return $camp;
    }

    /** มอบหมายรถ + คนขับเข้าแคมป์ คืน id_assignment */
    private function assign(Camp $camp, Truck $truck, Driver $driver, int $daysAgo): int
    {
        return DB::table('camp_truck')->insertGetId([
            'id_camp'       => $camp->id_camp,
            'id_truck'      => $truck->id_truck,
            'id_driver'     => $driver->id_driver,
            'assigned_date' => $this->ago($daysAgo),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * ใบส่งของ + บันทึกน้ำมันของเที่ยวนั้น (ระยะทางไป-กลับ $km × 2)
     * @param array<int, array{0: Product, 1: float, 2: float}> $items
     */
    private function deliveryNote(Camp $camp, int $daysAgo, array $items, ?Truck $truck, float $km): DeliveryNote
    {
        $dn = DeliveryNote::create([
            'id_quotation'  => $camp->id_quot,
            'id_camp'       => $camp->id_camp,
            'id_customer'   => $camp->id_customer,
            'delivery_date' => $this->ago($daysAgo),
            'status'        => 'delivered',
        ]);

        foreach ($items as [$product, $qty, $price]) {
            DeliveryNoteDetail::create([
                'id_delivery_note' => $dn->id_delivery_note,
                'id_product'       => $product->id_product,
                'quantity'         => $qty,
                'price_per_unit'   => $price,
                'total_price'      => round($qty * $price, 2),
            ]);
        }

        if ($truck && $km > 0) {
            // ส่งหลายวันต่อใบ: คิดเป็นจำนวนเที่ยวตามคิวที่ส่ง (รถ 1 เที่ยว ≈ ความจุกระบะ)
            $cubic    = collect($items)->sum(fn ($i) => $i[1]);
            $trips    = max(1, (int) ceil($cubic / max((float) $truck->cubic_capacity, 1)));
            $distance = round($km * 2 * $trips, 2);
            $liters   = round($distance / max((float) $truck->fuel_rate, 0.1), 2);

            FuelRecord::create([
                'date_record'       => $this->ago($daysAgo),
                'start_point'       => 'บ่อดินบริษัท อ.สูงเนิน',
                'destination'       => $camp->name_camp,
                'destination_detail'=> $camp->full_address,
                'distance'          => $distance,
                'cost_fuel'         => self::DIESEL,
                'total_fuel_liters' => $liters,
                'cost_fuel_total'   => round($liters * self::DIESEL, 2),
                'trucks_id_truck'   => $truck->id_truck,
                'id_camp'           => $camp->id_camp,
            ]);
        }

        return $dn;
    }

    /** ใบแจ้งหนี้รวมหลายใบส่งของ (เหมือน InvoiceController::store) */
    private function invoice(array $notes, int $daysAgo, int $creditDays, ?int $billReceived = null): Invoice
    {
        $notes = collect($notes)->each->load('details');
        $first = $notes->first();

        $lines = $notes->flatMap(fn ($n) => $n->details)
            ->groupBy(fn ($d) => $d->id_product . '|' . $d->price_per_unit)
            ->map(fn ($rows) => [
                'id_product' => $rows->first()->id_product,
                'quantity'   => round($rows->sum(fn ($d) => (float) $d->quantity), 2),
                'price'      => (float) $rows->first()->price_per_unit,
                'total'      => round($rows->sum(fn ($d) => (float) $d->total_price), 2),
            ])->values();

        $subtotal = round($lines->sum('total'), 2);

        $invoice = Invoice::create([
            'invoice_date'       => $this->ago($daysAgo),
            'credit_days'        => $creditDays,
            'bill_received_date' => $billReceived !== null ? $this->ago($billReceived) : null,
            'id_customer'        => $first->id_customer,
            'id_camp'            => $first->id_camp,
            'id_quotation'       => $notes->pluck('id_quotation')->filter()->first(),
            'discount'           => 0,
            'total'              => round($subtotal + $subtotal * Invoice::VAT_RATE / 100, 2),
            'status'             => 'unpaid',
        ]);

        foreach ($lines as $line) {
            InvoiceDetail::create(['id_invoice' => $invoice->id_invoice] + $line);
        }

        DeliveryNote::whereIn('id_delivery_note', $notes->pluck('id_delivery_note'))
            ->update(['id_invoice' => $invoice->id_invoice]);

        return $invoice->fresh();
    }

    private function pay(Invoice $invoice, int $daysAgo, float $amount, string $method, ?string $bank, ?string $ref): void
    {
        $invoice->payments()->create([
            'paid_at'             => $this->ago($daysAgo),
            'amount'              => round($amount, 2),
            'method'              => $method,
            'bank_name'           => $bank,
            'reference_no'        => $ref,
            'note'                => 'ข้อมูลตัวอย่าง (ไม่มีไฟล์สลิป)',
            'recorded_by_user_id' => $this->staff->id,
        ]);

        $invoice->syncPaymentStatus();
    }

    /** ใบเสร็จจากใบแจ้งหนี้ที่ชำระครบแล้ว (เหมือน ReceiptController::createFromInvoice) */
    private function receiptFromInvoice(Invoice $invoice, int $daysAgo): Receipt
    {
        $invoice->unsetRelation('payments');

        return Receipt::create([
            'receipt_type'      => Receipt::TYPE_INVOICE,
            'id_invoice'        => $invoice->id_invoice,
            'id_customer'       => $invoice->id_customer,
            'id_camp'           => $invoice->id_camp,
            'subtotal'          => $invoice->itemsSubtotal(),
            'vat_amount'        => $invoice->itemsVat(),
            'advance_deducted'  => $invoice->advanceAppliedAmount(),
            'total'             => $invoice->cashPaidAmount(),
            'date_receipt'      => $this->ago($daysAgo),
            'issued_by_user_id' => $this->admin->id,
        ]);
    }

    private function advanceReceipt(Camp $camp, int $daysAgo, float $gross): Receipt
    {
        [$subtotal, $vat] = Receipt::splitGross($gross);

        $receipt = Receipt::create([
            'receipt_type'      => Receipt::TYPE_ADVANCE,
            'id_customer'       => $camp->id_customer,
            'id_camp'           => $camp->id_camp,
            'subtotal'          => $subtotal,
            'vat_amount'        => $vat,
            'total'             => $gross,
            'date_receipt'      => $this->ago($daysAgo),
            'description'       => 'มัดจำงวดแรก',
            'issued_by_user_id' => $this->admin->id,
        ]);

        $receipt->items()->create([
            'description' => 'รับเงินล่วงหน้า ' . $camp->code_camp . ' ' . $camp->name_camp . ' — มัดจำงวดแรก',
            'quantity'    => 1,
            'price'       => $subtotal,
            'total'       => $subtotal,
        ]);

        $receipt->payments()->create([
            'paid_at'             => $this->ago($daysAgo),
            'amount'              => $gross,
            'method'              => 'transfer',
            'bank_name'           => 'ธนาคารกรุงไทย',
            'reference_no'        => 'TRF-ADV-0412',
            'note'                => 'ข้อมูลตัวอย่าง (ไม่มีไฟล์สลิป)',
            'recorded_by_user_id' => $this->admin->id,
        ]);

        return $receipt;
    }

    private function applyAdvance(Invoice $invoice, Receipt $advance, int $daysAgo, float $amount): void
    {
        $invoice->payments()->create([
            'id_advance_receipt'  => $advance->id_receipt,
            'paid_at'             => $this->ago($daysAgo),
            'amount'              => $amount,
            'method'              => Payment::METHOD_ADVANCE,
            'reference_no'        => $advance->code_rc,
            'note'                => 'หักจากใบเสร็จรับเงินล่วงหน้า ' . $advance->code_rc,
            'recorded_by_user_id' => $this->admin->id,
        ]);

        $invoice->syncPaymentStatus();
    }
}
