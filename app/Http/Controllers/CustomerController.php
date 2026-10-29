<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);

        // ช่องค้นหาช่องเดียว ค้นได้ทุกคอลัมน์ที่แสดง รวมถึงพิมพ์ "บริษัท" / "บุคคล"
        $types = collect(['person' => 'บุคคล', 'company' => 'บริษัท'])
            ->filter(fn ($label) => $search !== '' && mb_stripos($label, $search) !== false)
            ->keys();

        // dropdown ประเภทลูกค้า
        $typeLabels = ['person' => 'บุคคล', 'company' => 'บริษัท'];
        $type = $this->statusFilter($request, array_keys($typeLabels), 'type');

        // ลูกค้าทั่วไป (ไม่ระบุชื่อ) เป็นข้อมูลของระบบ ไม่แสดงในรายชื่อ — เลือกได้จากปุ่มในหน้าต่างขายสด
        $customers = Customer::regular()
            ->when($type, fn ($query) => $query->where('customer_type', $type))
            ->when($search !== '', function ($query) use ($search, $types) {
                $query->where(function ($sub) use ($search, $types) {
                    foreach (['name_customer', 'contact_name', 'tax_id', 'phone_customer', 'email_customer',
                              'address_no', 'address_detail', 'soi', 'road',
                              'subdistrict', 'district', 'province', 'zipcode'] as $col) {
                        $sub->orWhere($col, 'like', "%{$search}%");
                    }
                    if ($types->isNotEmpty()) {
                        $sub->orWhereIn('customer_type', $types);
                    }
                });
            })
            ->latest('id_customer')
            ->paginate($perPage)
            ->withQueryString();

        return view('customers.index', compact('customers', 'perPage', 'search', 'typeLabels', 'type'));
    }

    public function create()
    {
        // เพิ่มลูกค้าทำในหน้าต่าง (modal) ของหน้ารายการ
        return redirect()->route('customers.index', ['modal' => 'create']);
    }

    public function store(Request $request)
    {
        Customer::create($this->validateForm($request, 'createCustomer'));

        return redirect()
            ->route('customers.index')
            ->with('ok', 'เพิ่มข้อมูลลูกค้าเรียบร้อย');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        // แก้ไขลูกค้าทำในหน้าต่าง (modal) ของหน้ารายการ
        return redirect()->route('customers.index', ['edit' => $customer->getKey()]);
    }

    public function update(Request $request, Customer $customer)
    {
        if ($customer->isWalkIn()) {
            return redirect()->route('customers.index')->with('error', Customer::WALK_IN_NAME . ' เป็นข้อมูลของระบบ แก้ไขไม่ได้');
        }

        $customer->update($this->validateForm($request, 'updateCustomer'));

        return redirect()
            ->route('customers.index', $request->only('page', 'search', 'type', 'per_page'))
            ->with('ok', 'แก้ไขข้อมูลเรียบร้อย');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->isWalkIn()) {
            return redirect()->route('customers.index')->with('error', Customer::WALK_IN_NAME . ' เป็นข้อมูลของระบบ ลบไม่ได้');
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('ok', 'ลบข้อมูลเรียบร้อย');
    }

    /**
     * เพิ่มลูกค้าจากหน้าต่าง "ลูกค้าใหม่" ในหน้าใบเสนอราคา / ใบสั่งขาย (customers._quick-modal)
     * ใช้ฟอร์มและกฎตรวจข้อมูลชุดเดียวกับหน้าลูกค้า (validateForm)
     * กรอกผิด → Laravel ตอบ 422 พร้อม errors เป็น JSON ให้เอง (เพราะ request ขอ JSON)
     */
    public function storeAjax(Request $request)
    {
        $customer = Customer::create($this->validateForm($request, 'quickCustomer'));

        // name_customer ของบุคคลถูกรวมจาก คำนำหน้า / ชื่อ / นามสกุล ในโมเดล → อ่านค่าล่าสุดกลับมา
        $customer->refresh();

        return response()->json([
            'id_customer'   => $customer->id_customer,
            'name_customer' => $customer->name_customer,
        ], 201);
    }

    /**
     * ฟอร์มเต็มในหน้าต่างเพิ่ม / แก้ไขลูกค้า
     *   บุคคล  : คำนำหน้า + ชื่อ (+ นามสกุล) → ระบบรวมเป็น name_customer
     *   บริษัท : name_customer = ชื่อบริษัท + สำนักงานใหญ่/สาขา + ผู้ติดต่อ
     */
    private function validateForm(Request $request, string $bag): array
    {
        $isCompany = $request->input('customer_type') === 'company';

        $data = $request->validateWithBag($bag, [
            'customer_type'  => ['required', 'in:person,company'],

            'name_prefix'    => ['nullable', 'string', 'max:30'],
            'first_name'     => [$isCompany ? 'nullable' : 'required', 'string', 'max:100'],
            'last_name'      => ['nullable', 'string', 'max:100'],

            'name_customer'  => [$isCompany ? 'required' : 'nullable', 'string', 'max:255'],
            'branch_kind'    => ['nullable', 'in:head,branch'],
            'branch_no'      => [
                $isCompany && $request->input('branch_kind') === 'branch' ? 'required' : 'nullable',
                'digits_between:1,5',
            ],
            'contact_name'   => ['nullable', 'string', 'max:150'],

            'tax_id'         => ['nullable', 'digits:13'],
            'phone_customer' => ['nullable', 'digits:10'],
            'email_customer' => ['nullable', 'email', 'max:255'],

            'address_no'     => ['nullable', 'string', 'max:30'],
            'moo'            => ['nullable', 'string', 'max:10'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'soi'            => ['nullable', 'string', 'max:100'],
            'road'           => ['nullable', 'string', 'max:100'],
            'province'       => ['nullable', 'string', 'max:100'],
            'district'       => ['nullable', 'string', 'max:100'],
            'subdistrict'    => ['nullable', 'string', 'max:100'],
            'zipcode'        => ['nullable', 'digits:5'],
        ], [
            'first_name.required'    => 'กรุณาใส่ชื่อลูกค้า',
            'name_customer.required' => 'กรุณาใส่ชื่อบริษัท',
            'branch_no.required'     => 'กรุณาใส่เลขที่สาขา',
            'tax_id.digits'          => 'เลขประจำตัวผู้เสียภาษีต้องเป็นตัวเลข 13 หลัก',
            'phone_customer.digits'  => 'เบอร์โทรต้องเป็นตัวเลข 10 หลัก',
            'zipcode.digits'         => 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก',
        ]);

        if ($isCompany) {
            $data['branch_code'] = ($data['branch_kind'] ?? 'head') === 'branch'
                ? str_pad($data['branch_no'], 5, '0', STR_PAD_LEFT)
                : Customer::HEAD_OFFICE;
            $data['name_prefix'] = $data['first_name'] = $data['last_name'] = null;
        } else {
            // ชื่อเต็มรวมจากช่องย่อยในโมเดล
            unset($data['name_customer']);
            $data['branch_code'] = $data['contact_name'] = null;
        }

        unset($data['branch_kind'], $data['branch_no']);

        return $data;
    }
}
