<?php

namespace App\Modules\Trucks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Trucks\Http\Requests\StoreTruckRequest;
use App\Modules\Trucks\Http\Requests\UpdateTruckRequest;
use App\Modules\Trucks\Http\Resources\TruckResource;
use App\Modules\Trucks\Services\TruckService;
use App\Modules\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TruckController extends Controller
{
    public function __construct(
        private TruckService $service
    ) {}

    /**
     * Display a listing of trucks
     */
    public function index(Request $request): JsonResponse
    {
        $activeOnly = $request->query('active_only', false);
        $trucks = $this->service->getAllTrucks($activeOnly);

        return ApiResponse::success(
            TruckResource::collection($trucks),
            'Trucks retrieved successfully'
        );
    }

    /**
     * Store a newly created truck
     */
    public function store(StoreTruckRequest $request): JsonResponse
    {
        try {
            $truck = $this->service->createTruck($request->validated());

            return ApiResponse::success(
                new TruckResource($truck),
                'Truck created successfully',
                201
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Display the specified truck
     */
    public function show(string $tenant, string $id): JsonResponse
    {
        try {
            $truck = $this->service->getTruck((int) $id);

            return ApiResponse::success(
                new TruckResource($truck),
                'Truck retrieved successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 404);
        }
    }

    /**
     * Update the specified truck
     */
    public function update(UpdateTruckRequest $request, string $tenant, string $id): JsonResponse
    {
        try {
            $truck = $this->service->updateTruck((int) $id, $request->validated());

            return ApiResponse::success(
                new TruckResource($truck),
                'Truck updated successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Remove the specified truck
     */
    public function destroy(string $tenant, string $id): JsonResponse
    {
        try {
            $this->service->deleteTruck((int) $id);

            return ApiResponse::success(
                null,
                'Truck deleted successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Toggle truck active status
     */
    public function toggleActive(string $tenant, string $id): JsonResponse
    {
        try {
            $truck = $this->service->toggleActive((int) $id);

            return ApiResponse::success(
                new TruckResource($truck),
                'Truck status updated successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }
}
