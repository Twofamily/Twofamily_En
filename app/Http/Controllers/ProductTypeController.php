<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    public function index()
    {
        $types = ProductType::latest('id_product_type')->paginate(10);

        return view('product_types.index', compact('types'));
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
        ]);
    }
}