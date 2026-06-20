<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MainController extends Controller
{

    /**
     * @OA\Get(
     *     path="/",
     *     tags={"Main"},
     *     @OA\Response(
     *         response=200,
     *         description="Welcome message",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Welcome to the API"),
     *             @OA\Property(property="version", type="string", example="1.0.0"),
     *             @OA\Property(property="author", type="string", example="Mirza Qamaruzzaman"),
     *             @OA\Property(property="date", type="string", example="2024-01-01T00:00:00.000000Z")
     *         )
     *     )
     * )
     */

    // GET: /api/
    public function index(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Welcome to the API',
            'version' => '1.0.0',
            'author' => 'Mirza Qamaruzzaman',
            'date' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }
}
