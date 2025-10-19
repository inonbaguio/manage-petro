<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $service
    ) {}

    /**
     * Get dashboard statistics
     */
    public function index(Request $request, string $tenant): JsonResponse
    {
        $period = $request->query('period', 'today'); // today, week, month

        $stats = [
            'orders' => $this->service->getOrderStatistics($period),
            'fleet' => $this->service->getFleetStatistics(),
            'recent_activity' => $this->service->getRecentActivity(),
        ];

        return ApiResponse::success($stats, 'Dashboard data retrieved successfully');
    }
}
