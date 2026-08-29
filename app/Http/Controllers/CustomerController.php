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
        $q = $request->q;

        $customers = Customer::when(
            $q,
            fn($query) =>
            $query->where('name_customer', 'like', "%{$q}%")
                ->orWhere('phone_customer', 'like', "%{$q}%")
                ->orWhere('email_customer', 'like', "%{$q}%")
        )
            ->latest('id_customer')
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers', 'q'));
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
