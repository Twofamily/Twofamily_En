<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private const PROVINCES = [
        'กรุงเทพมหานคร',
        'กระบี่',
        'กาญจนบุรี',
        'กาฬสินธุ์',
        'กำแพงเพชร',
        'ขอนแก่น',
        'จันทบุรี',
        'ฉะเชิงเทรา',
        'ชลบุรี',
        'ชัยนาท',
        'ชัยภูมิ',
        'ชุมพร',
        'เชียงราย',
        'เชียงใหม่',
        'ตรัง',
        'ตราด',
        'ตาก',
        'นครนายก',
        'นครปฐม',
        'นครพนม',
        'นครราชสีมา',
        'นครศรีธรรมราช',
        'นครสวรรค์',
        'นนทบุรี',
        'นราธิวาส',
        'น่าน',
        'บึงกาฬ',
        'บุรีรัมย์',
        'ปทุมธานี',
        'ประจวบคีรีขันธ์',
        'ปราจีนบุรี',
        'ปัตตานี',
        'พระนครศรีอยุธยา',
        'พะเยา',
        'พังงา',
        'พัทลุง',
        'พิจิตร',
        'พิษณุโลก',
        'เพชรบุรี',
        'เพชรบูรณ์',
        'แพร่',
        'ภูเก็ต',
        'มหาสารคาม',
        'มุกดาหาร',
        'แม่ฮ่องสอน',
        'ยโสธร',
        'ยะลา',
        'ร้อยเอ็ด',
        'ระนอง',
        'ระยอง',
        'ราชบุรี',
        'ลพบุรี',
        'ลำปาง',
        'ลำพูน',
        'เลย',
        'ศรีสะเกษ',
        'สกลนคร',
        'สงขลา',
        'สตูล',
        'สมุทรปราการ',
        'สมุทรสงคราม',
        'สมุทรสาคร',
        'สระแก้ว',
        'สระบุรี',
        'สิงห์บุรี',
        'สุโขทัย',
        'สุพรรณบุรี',
        'สุราษฎร์ธานี',
        'สุรินทร์',
        'หนองคาย',
        'หนองบัวลำภู',
        'อ่างทอง',
        'อำนาจเจริญ',
        'อุดรธานี',
        'อุตรดิตถ์',
        'อุทัยธานี',
        'อุบลราชธานี',
    ];

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

        $customers = Customer::query()
            ->when($type, fn ($query) => $query->where('customer_type', $type))
            ->when($search !== '', function ($query) use ($search, $types) {
                $query->where(function ($sub) use ($search, $types) {
                    foreach (['name_customer', 'phone_customer', 'email_customer', 'address_detail',
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
        return view('customers.create', [
            'provinces' => self::PROVINCES,
        ]);
    }

    public function store(Request $request)
    {
        Customer::create($this->validateCustomer($request));

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
        return view('customers.edit', [
            'customer'  => $customer,
            'provinces' => self::PROVINCES,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validateCustomer($request));

        return redirect()
            ->route('customers.index')
            ->with('ok', 'แก้ไขข้อมูลเรียบร้อย');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('ok', 'ลบข้อมูลเรียบร้อย');
    }

    public function storeAjax(Request $request)
    {
        $data = $this->validateCustomer($request);
        $customer = Customer::create($data);

        return response()->json($customer);
    }

    private function validateCustomer(Request $request): array
    {
        return $request->validate([
            'name_customer'  => ['required', 'string', 'max:255'],
            'customer_type'  => ['required', 'string'],
            'phone_customer' => ['nullable', 'digits:10'],
            'email_customer' => ['nullable', 'email'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'subdistrict'    => ['nullable', 'string', 'max:100'],
            'district'       => ['nullable', 'string', 'max:100'],
            'province'       => ['nullable', 'string', 'max:100'],
            'zipcode'        => ['nullable', 'digits:5'],
        ]);
    }
}
