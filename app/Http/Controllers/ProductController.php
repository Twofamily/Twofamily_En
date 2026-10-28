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
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);
        $typeId  = $request->integer('type') ?: null;

        // กรองช่วงราคาต่อหน่วย (ต่ำสุด–สูงสุด)
        $ranges = $this->numberRanges($request, [
            'price' => ['label' => 'ราคาต่อหน่วย', 'unit' => 'บาท', 'step' => '1'],
        ]);

        $types = ProductType::orderBy('name_product_type')->pluck('name_product_type', 'id_product_type');

        // ช่องค้นหาช่องเดียว: ชื่อ / รายละเอียด / ชื่อประเภท / ราคา
        $products = Product::with('type')
            ->when($typeId, fn ($query) => $query->where('product_type_id', $typeId))
            ->tap(fn ($query) => $this->applyNumberRange($query, 'unit_price', $ranges['price']))
            ->when($search !== '', fn ($query) => $query->where(fn ($sub) =>
                $sub->where('name_product', 'like', "%{$search}%")
                    ->orWhere('detail_product', 'like', "%{$search}%")
                    ->orWhere('unit_price', 'like', "%{$search}%")
                    ->orWhereHas('type', fn ($t) => $t->where('name_product_type', 'like', "%{$search}%"))
            ))
            ->latest('id_product')
            ->paginate($perPage)
            ->withQueryString();

        return view('products.index', compact('products', 'search', 'perPage', 'ranges', 'types', 'typeId'));
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
            'product_type_id' => ['required', 'exists:product_types,id_product_type'],
            'new_type'        => ['nullable', 'string', 'max:255'],
            'image'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'name_product.required'    => 'กรุณาใส่ชื่อสินค้า',
            'name_product.max'         => 'ชื่อสินค้ายาวได้ไม่เกิน 45 ตัวอักษร',
            'unit_price.required'      => 'กรุณาใส่ราคาต่อหน่วย',
            'unit_price.integer'       => 'ราคาต่อหน่วยต้องเป็นตัวเลขจำนวนเต็ม',
            'unit_price.min'           => 'ราคาต่อหน่วยต้องไม่ติดลบ',
            'product_type_id.required' => 'กรุณาเลือกประเภทสินค้า',
            'product_type_id.exists'   => 'ไม่พบประเภทสินค้าที่เลือก',
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