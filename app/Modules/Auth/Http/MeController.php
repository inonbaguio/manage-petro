<?php

namespace App\Modules\Auth\Http;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Get the authenticated user.
     */
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($request->user());
    }
}
