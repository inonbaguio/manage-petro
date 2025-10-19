<?php

namespace App\Modules\Clients\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clients\Http\Requests\StoreClientRequest;
use App\Modules\Clients\Http\Requests\UpdateClientRequest;
use App\Modules\Clients\Http\Resources\ClientResource;
use App\Modules\Clients\Services\ClientService;
use App\Modules\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        private ClientService $service
    ) {}

    /**
     * Display a listing of clients
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $clients = $this->service->getAllClients($search);

        return ApiResponse::success(
            ClientResource::collection($clients),
            'Clients retrieved successfully'
        );
    }

    /**
     * Store a newly created client
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->service->createClient($request->validated());

        return ApiResponse::success(
            new ClientResource($client),
            'Client created successfully',
            201
        );
    }

    /**
     * Display the specified client
     */
    public function show(string $tenant, string $id): JsonResponse
    {
        $client = $this->service->getClient((int) $id);

        if (!$client) {
            return ApiResponse::error('Client not found', 404);
        }

        return ApiResponse::success(
            new ClientResource($client),
            'Client retrieved successfully'
        );
    }

    /**
     * Update the specified client
     */
    public function update(UpdateClientRequest $request, string $tenant, string $id): JsonResponse
    {
        try {
            $client = $this->service->updateClient((int) $id, $request->validated());

            return ApiResponse::success(
                new ClientResource($client),
                'Client updated successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Remove the specified client
     */
    public function destroy(string $tenant, string $id): JsonResponse
    {
        try {
            $this->service->deleteClient((int) $id);

            return ApiResponse::success(
                null,
                'Client deleted successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }
}
