<?php

namespace App\Modules\Locations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Locations\Http\Requests\StoreLocationRequest;
use App\Modules\Locations\Http\Requests\UpdateLocationRequest;
use App\Modules\Locations\Http\Resources\LocationResource;
use App\Modules\Locations\Services\LocationService;
use App\Modules\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(
        private LocationService $service
    ) {}

    /**
     * Display a listing of locations
     */
    public function index(Request $request): JsonResponse
    {
        $clientId = $request->query('client_id');
        $search = $request->query('search');
        $locations = $this->service->getAllLocations($clientId, $search);

        return ApiResponse::success(
            LocationResource::collection($locations),
            'Locations retrieved successfully'
        );
    }

    /**
     * Store a newly created location
     */
    public function store(StoreLocationRequest $request): JsonResponse
    {
        try {
            $location = $this->service->createLocation($request->validated());

            return ApiResponse::success(
                new LocationResource($location),
                'Location created successfully',
                201
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Display the specified location
     */
    public function show(string $tenant, string $id): JsonResponse
    {
        $location = $this->service->getLocation((int) $id);

        if (!$location) {
            return ApiResponse::error('Location not found', 404);
        }

        return ApiResponse::success(
            new LocationResource($location),
            'Location retrieved successfully'
        );
    }

    /**
     * Update the specified location
     */
    public function update(UpdateLocationRequest $request, string $tenant, string $id): JsonResponse
    {
        try {
            $location = $this->service->updateLocation((int) $id, $request->validated());

            return ApiResponse::success(
                new LocationResource($location),
                'Location updated successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Remove the specified location
     */
    public function destroy(string $tenant, string $id): JsonResponse
    {
        try {
            $this->service->deleteLocation((int) $id);

            return ApiResponse::success(
                null,
                'Location deleted successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }
}
