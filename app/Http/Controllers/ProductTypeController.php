<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    /**
     * ประเภทสินค้าเป็นแท็บหนึ่งในหน้าสินค้าแล้ว → ส่งไปแท็บนั้น (พกคำค้นหาไปด้วย)
     * ยกเว้นคนที่ดูได้แค่ประเภทสินค้า แต่ไม่มีสิทธิ์ดูหน้าสินค้า → ใช้หน้าเดิม
     */
    public function index(Request $request)
    {
        if ($request->user()?->canViewModule('products')) {
            return redirect()->route('products.index', ['tab' => 'types'] + $request->query());
        }

        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);

        $types = ProductType::query()
            ->when($search !== '', fn ($q) => $q->where('name_product_type', 'like', "%{$search}%"))
            ->latest('id_product_type')
            ->paginate($perPage)
            ->withQueryString();

        return view('product_types.index', compact('types', 'perPage', 'search'));
    }

    /** ฟอร์มเพิ่มประเภทย้ายไปเป็น modal ในแท็บประเภทสินค้าแล้ว */
    public function create(Request $request)
    {
        if ($request->user()?->canViewModule('products')) {
            return redirect()->route('products.index', ['tab' => 'types', 'open' => 'type']);
        }

        return view('product_types.create');
    }

    public function store(Request $request)
    {
        ProductType::create($this->validateType($request));

        return redirect()
            ->route('products.index', ['tab' => 'types'])
            ->with('ok', 'เพิ่มประเภทสินค้าเรียบร้อย');
    }

    /** แก้ไขประเภทสินค้าทำในหน้าต่าง (modal) ของแท็บประเภทสินค้า */
    public function edit(Request $request, ProductType $product_type)
    {
        if ($request->user()?->canViewModule('products')) {
            return redirect()->route('products.index', ['tab' => 'types', 'edit_type' => $product_type->getKey()]);
        }

        return view('product_types.edit', compact('product_type'));
    }

    public function update(Request $request, ProductType $product_type)
    {
        $product_type->update($this->validateType($request, 'updateType'));

        return redirect()
            ->route('products.index', ['tab' => 'types'])
            ->with('ok', 'แก้ไขข้อมูลเรียบร้อย');
    }

    public function destroy(ProductType $product_type)
    {
        if ($product_type->products()->exists()) {
            return back()->with('error', 'ไม่สามารถลบได้ เนื่องจากมีสินค้าที่ใช้งานประเภทนี้อยู่');
        }

        $product_type->delete();

        return redirect()
            ->route('products.index', ['tab' => 'types'])
            ->with('ok', 'ลบข้อมูลเรียบร้อย');
    }

    private function validateType(Request $request, string $bag = 'default'): array
    {
        return $request->validateWithBag($bag, [
            'name_product_type' => ['required', 'string', 'max:100'],
        ], [
            'name_product_type.required' => 'กรุณาใส่ประเภทสินค้า',
            'name_product_type.max'      => 'ชื่อประเภทสินค้ายาวได้ไม่เกิน 100 ตัวอักษร',
        ]);
    }
}