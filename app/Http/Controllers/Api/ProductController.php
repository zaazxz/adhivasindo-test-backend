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

    /**
     * @OA\Get(
     *     path="/products",
     *     tags={"Product"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Products list",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="uuid"),
     *             @OA\Property(property="name", type="string", example="Product Name"),
     *             @OA\Property(property="price", type="integer", example=100000),
     *             @OA\Property(property="stock", type="integer", example=10),
     *             @OA\Property(property="status", type="string", example="active"),
     *             @OA\Property(property="created_at", type="string", example="2024-01-01T00:00:00.000000Z"),
     *             @OA\Property(property="updated_at", type="string", example="2024-01-01T00:00:00.000000Z")
     *         )
     *     )
     * )
     */

    // GET: /api/product/
    public function index(Request $request)
    {

        $query = Product::query();

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

    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     tags={"Product"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Product details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="uuid"),
     *             @OA\Property(property="name", type="string", example="Product Name"),
     *             @OA\Property(property="price", type="integer", example=100000),
     *             @OA\Property(property="stock", type="integer", example=10),
     *             @OA\Property(property="status", type="string", example="active"),
     *             @OA\Property(property="created_at", type="string", example="2024-01-01T00:00:00.000000Z"),
     *             @OA\Property(property="updated_at", type="string", example="2024-01-01T00:00:00.000000Z")
     *         )
     *     )
     * )
     */

    // GET: /api/product/{id}
    public function show(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product Not Found'], 404);
        }

        $user = $request->user();
        if ($product->status !== 'active' && (!$user || $user->role !== 'admin')) {
            return response()->json(['message' => 'Product Not Found'], 404);
        }

        return response()->json($product);
    }

    /**
     * @OA\Post(
     *     path="/products",
     *     tags={"Product"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "price", "stock"},
     *             @OA
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             @OA
     *         )
     *     )
     * )
     */

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

        // auto set 'out of stock' if stock is 0 and status is not set
        if (!$request->has('status') && (int) $request->stock === 0) {
            $data['status'] = 'out-of-stock';
        }

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
        $product->status = $data['status'] ?? 'draft';
        $product->timestamps = false;
        $product->created_at = now();
        $product->updated_at = null;
        $product->save();

        return response()->json($product, 201);
    }

    /**
     * @OA\Put(
     *     path="/products/{id}",
     *     tags={"Product"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "price", "stock"},
     *             @OA\Property(property="name", type="string", example="Product Name"),
     *             @OA
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             @OA
     *         )
     *     )
     * )
     */

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

        // auto set 'out of stock' if stock is 0 and status is not set
        if ($request->has('stock') && !$request->has('status') && (int) $request->stock === 0) {
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

    /**
     * @OA\Delete(
     *     path="/products/{id}",
     *     tags={"Product"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             @OA
     *         )
     *     )
     * )
     */

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

    /**
     * @OA\Get(
     *     path="/products/{id}/image",
     *     tags={"Product"},
     *     security={{ "bearerAuth": {} }},
     *     @OA
     * )
     */

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
