<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Http\Requests\StoreOrderRequest;
use App\Modules\Orders\Http\Requests\UpdateOrderRequest;
use App\Modules\Orders\Http\Resources\OrderResource;
use App\Modules\Orders\Services\OrderService;
use App\Modules\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $service
    ) {}

    /**
     * Display a listing of orders
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\Orders\Domain\Models\Order::class);

        // Check if advanced filters are provided
        $filters = $request->only(['date_from', 'date_to', 'status', 'client_id', 'location_id', 'driver_id', 'truck_id']);

        if (!empty(array_filter($filters))) {
            // Use advanced search and filter
            $orders = $this->service->searchAndFilterOrders($filters);
        } else {
            // Legacy simple filtering
            $status = $request->query('status');
            $clientId = $request->query('client_id');
            $orders = $this->service->getAllOrders($status, $clientId);
        }

        return ApiResponse::success(
            OrderResource::collection($orders),
            'Orders retrieved successfully'
        );
    }

    /**
     * Store a newly created order
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->authorize('create', \App\Modules\Orders\Domain\Models\Order::class);

        try {
            $order = $this->service->createOrder(
                $request->validated(),
                $request->user()->id
            );

            return ApiResponse::success(
                new OrderResource($order),
                'Order created successfully',
                201
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Display the specified order
     */
    public function show(string $tenant, string $id): JsonResponse
    {
        $order = $this->service->getOrder((int) $id);

        if (!$order) {
            return ApiResponse::error('Order not found', 404);
        }

        $this->authorize('view', $order);

        return ApiResponse::success(
            new OrderResource($order),
            'Order retrieved successfully'
        );
    }

    /**
     * Update the specified order (only in DRAFT status)
     */
    public function update(UpdateOrderRequest $request, string $tenant, string $id): JsonResponse
    {
        try {
            $order = $this->service->getOrder((int) $id);

            if (!$order) {
                return ApiResponse::error('Order not found', 404);
            }

            $this->authorize('update', $order);

            $order = $this->service->updateOrder((int) $id, $request->validated());

            return ApiResponse::success(
                new OrderResource($order),
                'Order updated successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Remove the specified order (only in DRAFT status)
     */
    public function destroy(string $tenant, string $id): JsonResponse
    {
        try {
            $order = $this->service->getOrder((int) $id);

            if (!$order) {
                return ApiResponse::error('Order not found', 404);
            }

            $this->authorize('delete', $order);

            $this->service->deleteOrder((int) $id);

            return ApiResponse::success(
                null,
                'Order deleted successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }
}
