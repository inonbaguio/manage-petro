<?php

namespace App\Modules\ActivityLog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ActivityLog\Repositories\ActivityLogRepository;
use App\Modules\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __construct(
        private ActivityLogRepository $repository
    ) {}

    /**
     * Get all activity logs (ADMIN only)
     */
    public function index(Request $request): JsonResponse
    {
        // Authorization check - only ADMIN can view activity logs
        if ($request->user()->role !== 'ADMIN') {
            return ApiResponse::error('Unauthorized', 403);
        }

        $filters = $request->only(['model_type', 'action', 'user_id', 'date_from', 'date_to']);

        if (!empty(array_filter($filters))) {
            $logs = $this->repository->searchAndFilter($filters);
        } else {
            $logs = $this->repository->getRecent(50);
        }

        return ApiResponse::success(
            $logs,
            'Activity logs retrieved successfully'
        );
    }

    /**
     * Get activity logs for a specific model
     */
    public function getByModel(Request $request, string $tenant, string $modelType, string $modelId): JsonResponse
    {
        // Authorization check
        if ($request->user()->role !== 'ADMIN') {
            return ApiResponse::error('Unauthorized', 403);
        }

        $logs = $this->repository->getByModel($modelType, (int) $modelId);

        return ApiResponse::success(
            $logs,
            'Activity logs retrieved successfully'
        );
    }
}
