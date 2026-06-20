<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * @OA\Info(
 *     title="Adhivasindo Online Shop API",
 *     version="1.0.0",
 *     description="API Documentation Online Shop for Take Home Test from PT. Adhikari Inovasi Indonesia (Adhivasindo)"
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class MainController extends Controller
{

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
