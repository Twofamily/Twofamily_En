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
        $canSeeTypes = (bool) $request->user()?->canViewModule('product_types');
        $tab = $request->query('tab') === 'types' && $canSeeTypes ? 'types' : 'products';

        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);

        $types = ProductType::orderBy('name_product_type')->get(['id_product_type', 'name_product_type']);

        $counts = [
            'products' => Product::count(),
            'types'    => $types->count(),
        ];

        $shared = compact('tab', 'canSeeTypes', 'counts', 'types', 'search', 'perPage');

        if ($tab === 'types') {
            $productTypes = ProductType::query()
                ->withCount('products')
                ->when($search !== '', fn ($q) => $q->where('name_product_type', 'like', "%{$search}%"))
                ->latest('id_product_type')
                ->paginate($perPage)
                ->withQueryString();

            return view('products.index', $shared + compact('productTypes'));
        }

        $typeId = $request->integer('type') ?: null;

        $ranges = $this->numberRanges($request, [
            'price' => ['label' => 'ราคาต่อหน่วย', 'unit' => 'บาท', 'step' => '1'],
        ]);

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

        return view('products.index', $shared + compact('products', 'ranges', 'typeId'));
    }

    public function create()
    {
        return redirect()->route('products.index', ['open' => 'product']);
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
        return redirect()->route('products.index', ['edit' => $product->getKey()]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, 'updateProduct');
        unset($data['new_type']);

        if ($request->hasFile('image')) {
            $data['image'] = $this->handleImageUpload($request, $product->image);
        }

        $product->update($data);

        return redirect()
            ->route('products.index', $request->only('page', 'search', 'type', 'per_page'))
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

    private function validateProduct(Request $request, string $bag = 'default'): array
    {
        return $request->validateWithBag($bag, [
            'name_product'    => ['required', 'string', 'max:45'],
            'detail_product'  => ['nullable', 'string', 'max:255'],
            'unit_price'      => ['required', 'integer', 'min:0', 'max:9999999999'],
            'product_type_id' => ['required', 'exists:product_types,id_product_type'],
            'new_type'        => ['nullable', 'string', 'max:255'],
            'image'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'name_product.required'    => 'กรุณาใส่ชื่อสินค้า',
            'name_product.max'         => 'ชื่อสินค้ายาวได้ไม่เกิน 45 ตัวอักษร',
            'unit_price.required'      => 'กรุณาใส่ราคาต่อหน่วย',
            'unit_price.integer'       => 'ราคาต่อหน่วยต้องเป็นจำนวนเต็ม ไม่มีทศนิยม',
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