<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);

        $types = ProductType::query()
            ->when($search !== '', fn ($q) => $q->where('name_product_type', 'like', "%{$search}%"))
            ->latest('id_product_type')
            ->paginate($perPage)
            ->withQueryString();

        return view('product_types.index', compact('types', 'perPage', 'search'));
    }

    public function create()
    {
        return view('product_types.create');
    }

    public function store(Request $request)
    {
        ProductType::create($this->validateType($request));

        return redirect()
            ->route('product_types.index')
            ->with('ok', 'เพิ่มประเภทสินค้าเรียบร้อย');
    }

    public function edit(ProductType $product_type)
    {
        return view('product_types.edit', compact('product_type'));
    }

    public function update(Request $request, ProductType $product_type)
    {
        $product_type->update($this->validateType($request));

        return redirect()
            ->route('product_types.index')
            ->with('ok', 'แก้ไขข้อมูลเรียบร้อย');
    }

    public function destroy(ProductType $product_type)
    {
        if ($product_type->products()->exists()) {
            return back()->with('error', 'ไม่สามารถลบได้ เนื่องจากมีสินค้าที่ใช้งานประเภทนี้อยู่');
        }

        $product_type->delete();

        return redirect()
            ->route('product_types.index')
            ->with('ok', 'ลบข้อมูลเรียบร้อย');
    }

    private function validateType(Request $request): array
    {
        return $request->validate([
            'name_product_type' => ['required', 'string', 'max:100'],
        ], [
            'name_product_type.required' => 'กรุณาใส่ประเภทสินค้า',
            'name_product_type.max'      => 'ชื่อประเภทสินค้ายาวได้ไม่เกิน 100 ตัวอักษร',
        ]);
    }
}