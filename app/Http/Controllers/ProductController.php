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
        $q      = $request->q;
        $typeId = $request->type;

        $products = Product::with('type')
            ->when($q, fn($query) => $query->where(fn($subQuery) =>
                $subQuery->where('name_product', 'like', "%{$q}%")
                    ->orWhere('detail_product', 'like', "%{$q}%")
            ))
            ->when($typeId, fn($query) => $query->where('product_type_id', $typeId))
            ->latest('id_product')
            ->paginate(10)
            ->withQueryString();

        $types = ProductType::orderBy('name_product_type')->get();

        return view('products.index', compact('products', 'types', 'q', 'typeId'));
    }

    public function create()
    {
        return view('products.create', [
            'types' => ProductType::orderBy('name_product_type')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);

        if ($request->filled('new_type')) {
            $newType = ProductType::create([
                'name_product_type' => $request->new_type,
            ]);
            $data['product_type_id'] = $newType->id_product_type;
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->handleImageUpload($request);
        }

        Product::create($data);

        return redirect()
            ->route('products.index')
            ->with('ok', 'เพิ่มสินค้าเรียบร้อย');
    }

    public function edit(Product $product)
    {
        return view('products.edit', [
            'product' => $product,
            'types'   => ProductType::orderBy('name_product_type')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request);

        if ($request->hasFile('image')) {
            $data['image'] = $this->handleImageUpload($request, $product->image);
        }

        $product->update($data);

        return redirect()
            ->route('products.index')
            ->with('ok', 'แก้ไขข้อมูลเรียบร้อย');
    }

    public function destroy(Product $product)
    {
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('ok', 'ลบสินค้าเรียบร้อย');
    }

    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'name_product'    => ['required', 'string', 'max:45'],
            'detail_product'  => ['nullable', 'string', 'max:255'],
            'unit_price'      => ['required', 'integer', 'min:0'],
            'product_type_id' => ['nullable', 'exists:product_types,id_product_type'],
            'new_type'        => ['nullable', 'string', 'max:255'],
            'image'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);
    }

    private function handleImageUpload(Request $request, ?string $oldPath = null): string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $request->file('image')->store('products', 'public');
    }
}