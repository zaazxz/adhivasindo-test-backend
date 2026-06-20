<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
