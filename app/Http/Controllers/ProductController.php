<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->q;
        $typeId = $request->type;

        $query = Product::with('type');

        if ($q) {
            $query->where(function ($qq) use ($q) {
                $qq->where('name_product', 'like', "%{$q}%")
                    ->orWhere('detail_product', 'like', "%{$q}%");
            });
        }

        if ($typeId) {
            $query->where('product_type_id', $typeId);
        }

        $products = $query->paginate(10)->withQueryString();
        $types = ProductType::all();

        return view('products.index', compact('products', 'types', 'q', 'typeId'));
    }

    public function create()
    {
        $types = ProductType::all();
        return view('products.create', compact('types'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_product'    => 'required|string|max:45',
            'detail_product'  => 'nullable|string|max:255',
            'unit_price'      => 'required|integer|min:0',
            'product_type_id' => 'nullable|exists:product_types,id_product_type',
            'new_type'        => 'nullable|string|max:255',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->filled('new_type')) {
            $newType = ProductType::create([
                'name_product_type' => $request->new_type,
            ]);

            $request->merge([
                'product_type_id' => $newType->id_product_type
            ]);
        }

        $data = $request->only([
            'name_product',
            'detail_product',
            'unit_price',
            'product_type_id',
        ]);

        // จัดการอัปโหลดรูปภาพสินค้า
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

        return redirect()
            ->route('products.index')
            ->with('ok', 'เพิ่มสินค้าเรียบร้อย');
    }

    public function edit(Product $product)
    {
        $types = ProductType::all();
        return view('products.edit', compact('product', 'types'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name_product'    => 'required|string|max:45',
            'detail_product'  => 'nullable|string|max:255',
            'unit_price'      => 'required|integer|min:0',
            'product_type_id' => 'nullable|exists:product_types,id_product_type',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = $request->only([
            'name_product',
            'detail_product',
            'unit_price',
            'product_type_id',
        ]);

        // จัดการอัปโหลดรูปภาพใหม่ และลบรูปภาพเดิมออกจาก Storage
        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return redirect()
            ->route('products.index')
            ->with('ok', 'แก้ไขข้อมูลเรียบร้อย');
    }

    public function destroy(Product $product)
    {
        // ลบไฟล์รูปภาพออกจาก Storage ก่อนลบข้อมูลใน DB
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('ok', 'ลบสินค้าเรียบร้อย');
    }
}