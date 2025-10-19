<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Http\Requests\CancelOrderRequest;
use App\Modules\Orders\Http\Requests\DeliverOrderRequest;
use App\Modules\Orders\Http\Requests\DispatchOrderRequest;
use App\Modules\Orders\Http\Requests\ScheduleOrderRequest;
use App\Modules\Orders\Http\Resources\OrderResource;
use App\Modules\Orders\Services\OrderService;
use App\Modules\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrderActionsController extends Controller
{
    public function __construct(
        private OrderService $service
    ) {}

    /**
     * Submit order (DRAFT -> SUBMITTED)
     */
    public function submit(string $tenant, string $id): JsonResponse
    {
        try {
            $order = $this->service->getOrder((int) $id);

            if (!$order) {
                return ApiResponse::error('Order not found', 404);
            }

            $this->authorize('submit', $order);

            $order = $this->service->submitOrder((int) $id);

            return ApiResponse::success(
                new OrderResource($order),
                'Order submitted successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Schedule order (SUBMITTED -> SCHEDULED)
     */
    public function schedule(ScheduleOrderRequest $request, string $tenant, string $id): JsonResponse
    {
        try {
            $order = $this->service->getOrder((int) $id);

            if (!$order) {
                return ApiResponse::error('Order not found', 404);
            }

            $this->authorize('schedule', $order);

            $order = $this->service->scheduleOrder(
                (int) $id,
                $request->validated('truck_id')
            );

            return ApiResponse::success(
                new OrderResource($order),
                'Order scheduled successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Dispatch order (SCHEDULED -> EN_ROUTE)
     */
    public function dispatch(DispatchOrderRequest $request, string $tenant, string $id): JsonResponse
    {
        try {
            $order = $this->service->getOrder((int) $id);

            if (!$order) {
                return ApiResponse::error('Order not found', 404);
            }

            $this->authorize('dispatch', $order);

            $order = $this->service->dispatchOrder(
                (int) $id,
                $request->validated('driver_id')
            );

            return ApiResponse::success(
                new OrderResource($order),
                'Order dispatched successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Deliver order (EN_ROUTE -> DELIVERED)
     */
    public function deliver(DeliverOrderRequest $request, string $tenant, string $id): JsonResponse
    {
        try {
            $order = $this->service->getOrder((int) $id);

            if (!$order) {
                return ApiResponse::error('Order not found', 404);
            }

            $this->authorize('deliver', $order);

            $order = $this->service->deliverOrder(
                (int) $id,
                $request->validated('delivered_liters')
            );

            return ApiResponse::success(
                new OrderResource($order),
                'Order delivered successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Cancel order (any status except DELIVERED -> CANCELLED)
     */
    public function cancel(CancelOrderRequest $request, string $tenant, string $id): JsonResponse
    {
        try {
            $order = $this->service->getOrder((int) $id);

            if (!$order) {
                return ApiResponse::error('Order not found', 404);
            }

            $this->authorize('cancel', $order);

            $order = $this->service->cancelOrder(
                (int) $id,
                $request->validated('reason')
            );

            return ApiResponse::success(
                new OrderResource($order),
                'Order cancelled successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }
}
