<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProductController extends Controller
{

    // GET: /api/product/
    public function index(Request $request)
    {

        $query = Product::query()->withSum('orderDetails as sold_count', 'quantity');

        // Customer only can see product 'active' & 'out of stock', admin can see all status
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            $query->whereIn('status', ['active', 'out-of-stock']);
        } elseif ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Search by type_id
        if ($request->has('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        // Eager load product type
        $products = $query->with('type')->latest()->paginate($request->get('per_page', 10));
        return response()->json($products);
    }

    // GET: /api/products/best-sellers
    public function bestSellers(Request $request)
    {
        $limit = $request->get('limit', 10);
        $products = Product::whereIn('status', ['active', 'out-of-stock'])
            ->withSum('orderDetails as sold_count', 'quantity')
            ->with('type')
            ->orderByDesc('sold_count')
            ->take($limit)
            ->get();

        return response()->json($products);
    }

    // GET: /api/product/{id}
    public function show(Request $request, $id)
    {
        $product = Product::withSum('orderDetails as sold_count', 'quantity')->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product Not Found'], 404);
        }

        $user = $request->user();
        if ($product->status !== 'active' && (!$user || $user->role !== 'admin')) {
            return response()->json(['message' => 'Product Not Found'], 404);
        }

        return response()->json($product);
    }

    // POST: /api/product
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_id' => 'required|exists:product_types,id',
            'name' => 'required|string|max:255',
            'desc' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'nullable|in:active,inactive,draft,out-of-stock',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['type_id', 'name', 'desc', 'price', 'stock', 'status']);

        // 1. product yang baru dibuat mau itu ada stock nya atau ga ada stock nya akan berstatus draft
        $data['status'] = 'draft';

        // handle image upload
        $imageResult = $this->handleImageUpload($request);

        if ($imageResult['error']) {
            return response()->json(['errors' => ['image' => [$imageResult['error']]]], 422);
        }

        if ($imageResult['path']) {
            $data['image'] = $imageResult['path'];
        }

        // Manual Timestamps
        $product = new Product();

        $product->type_id = $data['type_id'];
        $product->name = $data['name'];
        $product->desc = $data['desc'] ?? null;
        $product->price = $data['price'];
        $product->stock = $data['stock'];
        $product->image = $data['image'] ?? null;
        $product->status = $data['status'];
        $product->timestamps = false;
        $product->created_at = now();
        $product->updated_at = null;
        $product->save();

        return response()->json($product, 201);
    }

    // PUT/PATCH: /api/product/{id}
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product Not Found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'type_id' => 'sometimes|required|exists:product_types,id',
            'name' => 'sometimes|required|string|max:255',
            'desc' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'nullable|in:active,inactive,draft,out-of-stock',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['type_id', 'name', 'desc', 'price', 'stock', 'status']);

        $newStock = array_key_exists('stock', $data) ? (int) $data['stock'] : (int) $product->stock;

        if ($product->status === 'out-of-stock' && $newStock > 0) {
            // 2. product yang awalnya out-of-stock pas ditambah stock jangan langsung aktif tapi draft dulu
            $data['status'] = 'draft';
        } elseif ($newStock === 0) {
            // jika stock jadi 0, harus out-of-stock
            $data['status'] = 'out-of-stock';
        }

        $imageResult = $this->handleImageUpload($request);

        if ($imageResult['error']) {
            return response()->json(['errors' => ['image' => [$imageResult['error']]]], 422);
        }

        if ($imageResult['path']) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $imageResult['path'];
        }

        // Custom timestamps
        $product->timestamps = false;

        // set update value
        $product->type_id = $data['type_id'] ?? $product->type_id;
        $product->name = $data['name'] ?? $product->name;
        $product->desc = $data['desc'] ?? $product->desc;
        $product->price = $data['price'] ?? $product->price;
        $product->stock = $data['stock'] ?? $product->stock;
        $product->image = $data['image'] ?? $product->image;
        $product->status = $data['status'] ?? $product->status;
        $product->created_at = $product->created_at;
        $product->updated_at = now();

        // save
        $product->save();

        return response()->json($product);
    }

    // DELETE: /api/product/{id}
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product Not Found'], 404);
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'Product Success Deleted']);
    }

    protected function handleImageUpload(Request $request): array
    {
        // Case 1: form-data
        if ($request->hasFile('image')) {
            $validator = Validator::make($request->all(), [
                'image' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return ['path' => null, 'error' => $validator->errors()->first('image')];
            }

            $path = $request->file('image')->store('product_images', 'public');
            return ['path' => $path, 'error' => null];
        }

        // Case 2: base64 string (JSON)
        if ($request->filled('image') && is_string($request->image) && str_starts_with($request->image, 'data:image')) {
            if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $request->image, $matches)) {
                return ['path' => null, 'error' => 'Invalid base64 image format. Use jpg, jpeg, png, or webp.'];
            }

            $extension = $matches[1];
            $base64Data = substr($request->image, strpos($request->image, ',') + 1);
            $decoded = base64_decode($base64Data, true);

            if ($decoded === false) {
                return ['path' => null, 'error' => 'Failed to decode base64 image.'];
            }

            // Max size image 2MB
            if (strlen($decoded) > 2 * 1024 * 1024) {
                return ['path' => null, 'error' => 'Image size must be less than 2MB.'];
            }

            $filename = 'product_images/' . uniqid() . '.' . $extension;
            Storage::disk('public')->put($filename, $decoded);

            return ['path' => $filename, 'error' => null];
        }

        // No image was sent at all
        return ['path' => null, 'error' => null];
    }
}

