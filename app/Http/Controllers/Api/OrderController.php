<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{

    /**
     * @OA\Post(
     *     path="/orders",
     *     tags={"Order"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_method", "items"},
     *             @OA\Property(property="payment_method", type="string", example="cash"),
     *             @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/OrderDetail"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="uuid"),
     *             @OA\Property(property="user_id", type="string", example="uuid"),
     *             @OA\Property(property="total_price", type="integer", example=100000),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="payment_method", type="string", example="cash"),
     *             @OA\Property(property="created_at", type="string", example="2024-01-01T00:00:00.000000Z"),
     *             @OA\Property(property="updated_at", type="string", example="2024-01-01T00:00:00.000000Z")
     *         )
     *     )
     * )
     */

    // POST: /api/orders
    public function store(Request $request) {

        $validator = Validator::make($request->all(), [
            'payment_method' => 'nullable|in:transfer,cash,qris',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        try {

            $order = DB::transaction(function () use ($request, $user) {

                $totalPrice = 0;
                $orderDetailsData = [];

                foreach ($request->items as $item) {

                    // Lock Row Product, prevent Race Condition
                    $product = Product::lockForUpdate()->find($item['product_id']);

                    if (!$product) {
                        throw new \Exception("Product not found: {$item['product_id']}");
                    }

                    if ($product->status !== 'active') {
                        throw new \Exception("Product '{$product->name}' not available to buy");
                    }

                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Product Stock '{$product->name}' not enough. Remaining: {$product->stock}");
                    }

                    $subTotal = $product->price * $item['quantity'];
                    $totalPrice += $subTotal;

                    $orderDetailsData[] = [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->price,
                        'sub_total' => $subTotal,
                    ];

                    // Reduce Stock, auto out-of-stock if
                    $product->stock -= $item['quantity'];
                    if ($product->stock === 0) {
                        $product->status = 'out-of-stock';
                    }
                    $product->save();
                }

                $order = new Order();
                $order->timestamps = false;
                $order->user_id = $user->id;
                $order->total_price = $totalPrice;
                $order->status = 'pending';
                $order->payment_method = $request->payment_method ?? 'cash';
                $order->created_at = now();
                $order->updated_at = null;
                $order->save();

                foreach ($orderDetailsData as $detail) {
                    $orderDetail = new OrderDetail();
                    $orderDetail->timestamps = false;
                    $orderDetail->order_id = $order->id;
                    $orderDetail->product_id = $detail['product']->id;
                    $orderDetail->quantity = $detail['quantity'];
                    $orderDetail->unit_price = $detail['unit_price'];
                    $orderDetail->sub_total = $detail['sub_total'];
                    $orderDetail->created_at = now();
                    $orderDetail->updated_at = null;
                    $orderDetail->save();
                }

                return $order;
            });

            return response()->json($order->load('orderDetails.product'), 201);

        } catch (\Exception $e) {

            return response()->json(['message' => $e->getMessage()], 422);

        }



    }

    /**
     * @OA\Get(
     *     path="/orders",
     *     tags={"Order"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Orders list",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="uuid"),
     *             @OA\Property(property="user_id", type="string", example="uuid"),
     *             @OA\Property(property="total_price", type="integer", example=100000),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="payment_method", type="string", example="cash"),
     *             @OA\Property(property="created_at", type="string", example="2024-01-01T00:00:00.000000Z"),
     *             @OA\Property(property="updated_at", type="string", example="2024-01-01T00:00:00.000000Z")
     *         )
     *     )
     * )
     */

    // GET: /api/orders
    public function index(Request $request) {

        $user = $request->user();
        $query = Order::with('orderDetails.product')->latest();

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate($request->get('per_page', 10));
        return response()->json($orders);

    }

    /**
     * @OA\Get(
     *     path="/order/{id}",
     *     tags={"Order"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Order details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="uuid"),
     *             @OA
     *         )
     *     )
     * )
     */

    // GET: /api/order/{id}
    public function show(Request $request, $id) {

        $order = Order::with('orderDetails.product', 'user')->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order Not Found'], 404);
        }

        $user = $request->user();
        if ($user->role !== 'admin' && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Order Not Found'], 404);
        }

        return response()->json($order);

    }

    /**
     * @OA\Put(
     *     path="/order/{id}/status",
     *     tags={"Order"},
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
     *             required={"status"},
     *             @OA\Property(property="status", type="string", example="pending")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order status updated successfully")
     *         )
     *     )
     * )
     */

    // PUT/PATCH: /api/order/{id}/status
    public function updateStatus(Request $request, $id) {

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order Not Found'], 404);
        }

        $user = $request->user();
        $isOwner = $order->user_id === $user->id;
        $isAdmin = $user->role === 'admin';

        if (!$isOwner && !$isAdmin) {
            return response()->json(['message' => 'Order Not Found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,paid,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Customer only allowed to cancel their own order
        if (!$isAdmin) {
            if ($request->status !== 'cancelled') {
                return response()->json(['message' => 'Customer only allowed to cancel order'], 403);
            }
            if ($order->status !== 'pending') {
                return response()->json(['message' => 'Order already processed cannot be cancelled'], 422);
            }
        }

        // Admin is not allowed to cancel paid orders
        if ($isAdmin && $request->status === 'cancelled' && $order->status === 'paid') {
            return response()->json(['message' => 'Paid order cannot be cancelled directly'], 422);
        }

        // If order is cancelled, return product stock
        if ($request->status === 'cancelled' && $order->status !== 'cancelled') {
            DB::transaction(function () use ($order) {
                foreach ($order->orderDetails as $detail) {
                    $product = Product::lockForUpdate()->find($detail->product_id);
                    if ($product) {
                        $product->stock += $detail->quantity;
                        if ($product->status === 'out-of-stock' && $product->stock > 0) {
                            $product->status = 'active';
                        }
                        $product->save();
                    }
                }
                $order->status = 'cancelled';
                $order->save();
            });
        } else {
            $order->update(['status' => $request->status]);
        }

        return response()->json($order->load('orderDetails.product'));

    }

}
