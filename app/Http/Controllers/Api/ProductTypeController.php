<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductTypeController extends Controller
{

    /**
     * @OA\Get(
     *     path="/product-types",
     *     tags={"Product Type"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Product type list",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="uuid"),
     *             @OA
     *         )
     *     )
     * )
     */

    // GET: /api/product-types
    public function index(Request $request)
    {

        // query
        $query = ProductType::query();

        // per page
        $perPage = $request->get('per_page', 10);

        // search
        if ($request->has('search')) {
            $query->where('type_name', 'like', '%' . $request->search . '%');
        }

        // Eager load products
        $types = $query->with('products')->latest()->paginate($perPage);

        return response()->json($types);
    }

    /**
     * @OA\Post(
     *     path="/product-types",
     *     tags={"Product Type"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type_name"},
     *             @OA
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product type created successfully",
     *         @OA\JsonContent(
     *             @OA
     *         )
     *     )
     * )
     */

    // POST: /api/product-types
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_name' => 'required|string|max:255|unique:product_types,type_name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create New Instance
        $type = new ProductType();
        $type->type_name = $request->type_name;

        // Manual Set timestamp
        $type->created_at = now();
        $type->updated_at = null;

        // Turn off timestamp
        $type->timestamps = false;
        $type->save();

        return response()->json($type, 201);
    }

    /**
     * @OA\Put(
     *     path="/product-types/{id}",
     *     tags={"Product Type"},
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
     *             required={"type_name"},
     *             @OA
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product type updated successfully",
     *         @OA\JsonContent(
     *             @OA
     *         )
     *     )
     * )
     */

    // PUT/PATCH: /api/product-types/{id}
    public function update(Request $request, string $id)
    {
        // update product type by id
        $type = ProductType::find($id);

        if (!$type) {
            return response()->json(['message' => 'Product type not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'type_name' => 'sometimes|required|string|max:255|unique:product_types,type_name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Custom timestamps
        $type->timestamps = false;

        // set update value
        $type->type_name = $request->type_name ?? $type->type_name;
        $type->updated_at = now();

        // save
        $type->save();

        return response()->json($type);
    }

    /**
     * @OA\Delete(
     *     path="/product-types/{id}",
     *     tags={"Product Type"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product type deleted successfully",
     *         @OA\JsonContent(
     *             @OA
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product type not found",
     *         @OA\JsonContent(
     *             @OA
     *         )
     *     )
     * )
     */

    // DELETE: /api/product-types/{id}
    public function destroy(string $id)
    {
        // delete product type by id
        $type = ProductType::find($id);

        if (!$type) {
            return response()->json(['message' => 'Product type not found'], 404);
        }

        $type->delete();

        return response()->json(['message' => 'Product type deleted successfully']);
    }
}
