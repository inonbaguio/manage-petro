<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Orders\Repositories\OrderRepository;
use App\Modules\Clients\Repositories\ClientRepository;
use App\Modules\Locations\Repositories\LocationRepository;
use App\Modules\Trucks\Repositories\TruckRepository;
use App\Modules\ActivityLog\Services\ActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class OrderService
{
    public function __construct(
        private OrderRepository $repository,
        private ClientRepository $clientRepository,
        private LocationRepository $locationRepository,
        private TruckRepository $truckRepository,
        private ActivityLogger $activityLogger
    ) {}

    /**
     * Get all orders
     */
    public function getAllOrders(?string $status = null, ?int $clientId = null): LengthAwarePaginator|Collection
    {
        if ($status) {
            return $this->repository->getByStatus($status);
        }

        if ($clientId) {
            return $this->repository->getByClient($clientId);
        }

        return $this->repository->getAllWithRelations();
    }

    /**
     * Search and filter orders
     */
    public function searchAndFilterOrders(array $filters): Collection
    {
        return $this->repository->searchAndFilter($filters);
    }

    /**
     * Get a single order
     */
    public function getOrder(int $id): ?Order
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Create a new order (DRAFT status)
     */
    public function createOrder(array $data, int $userId): Order
    {
        // Validate client exists
        $this->clientRepository->findOrFail($data['client_id']);

        // Validate location exists and belongs to client
        $location = $this->locationRepository->findOrFail($data['location_id']);
        if ($location->client_id !== $data['client_id']) {
            throw new \Exception('Location does not belong to the specified client');
        }

        // Set default status and creator
        $data['status'] = 'DRAFT';
        $data['created_by'] = $userId;

        $order = $this->repository->create($data);

        // Log activity
        $this->activityLogger->logCreated($order, "Order created for {$order->client->name}");

        return $order;
    }

    /**
     * Update an order (only allowed in DRAFT status)
     */
    public function updateOrder(int $id, array $data): Order
    {
        $order = $this->repository->findOrFail($id);

        if ($order->status !== 'DRAFT') {
            throw new \Exception('Can only update orders in DRAFT status');
        }

        // If client or location changed, validate
        if (isset($data['client_id']) || isset($data['location_id'])) {
            $clientId = $data['client_id'] ?? $order->client_id;
            $locationId = $data['location_id'] ?? $order->location_id;

            $location = $this->locationRepository->findOrFail($locationId);
            if ($location->client_id !== $clientId) {
                throw new \Exception('Location does not belong to the specified client');
            }
        }

        $originalAttributes = $order->getAttributes();
        $this->repository->update($order, $data);

        // Log activity
        $this->activityLogger->logUpdated($order->fresh(), $originalAttributes, "Order #{$order->id} updated");

        return $order->fresh();
    }

    /**
     * Delete an order (only allowed in DRAFT status)
     */
    public function deleteOrder(int $id): bool
    {
        $order = $this->repository->findOrFail($id);

        if ($order->status !== 'DRAFT') {
            throw new \Exception('Can only delete orders in DRAFT status');
        }

        // Log activity before deletion
        $this->activityLogger->logDeleted($order, "Order #{$order->id} deleted");

        return $this->repository->delete($order);
    }

    /**
     * Submit order (DRAFT -> SUBMITTED)
     */
    public function submitOrder(int $id): Order
    {
        $order = $this->repository->findOrFail($id);

        if ($order->status !== 'DRAFT') {
            throw new \Exception('Can only submit orders in DRAFT status');
        }

        // Validate required fields
        if (!$order->fuel_liters || !$order->window_start || !$order->window_end) {
            throw new \Exception('Order must have fuel_liters, window_start, and window_end to be submitted');
        }

        $oldStatus = $order->status;
        $order->status = 'SUBMITTED';
        $order->save();

        // Log activity
        $this->activityLogger->logOrderTransition($order, $oldStatus, 'SUBMITTED');

        return $order->fresh();
    }

    /**
     * Schedule order (SUBMITTED -> SCHEDULED)
     */
    public function scheduleOrder(int $id, int $truckId): Order
    {
        $order = $this->repository->findOrFail($id);

        if ($order->status !== 'SUBMITTED') {
            throw new \Exception('Can only schedule orders in SUBMITTED status');
        }

        // Validate truck exists and is active
        $truck = $this->truckRepository->findOrFail($truckId);
        if (!$truck->active) {
            throw new \Exception('Cannot schedule order with inactive truck');
        }

        // Check truck capacity
        if ($order->fuel_liters > $truck->tank_capacity_l) {
            throw new \Exception("Order requires {$order->fuel_liters}L but truck capacity is only {$truck->tank_capacity_l}L");
        }

        // Check truck availability (no overlapping orders)
        $conflicts = $this->repository->getScheduledInWindow(
            $truckId,
            $order->window_start,
            $order->window_end
        );

        if ($conflicts->count() > 0) {
            throw new \Exception('Truck has conflicting orders in this time window');
        }

        $oldStatus = $order->status;
        $order->truck_id = $truckId;
        $order->status = 'SCHEDULED';
        $order->save();

        // Log activity
        $this->activityLogger->logOrderTransition(
            $order,
            $oldStatus,
            'SCHEDULED',
            ['truck_id' => $truckId, 'truck_plate' => $truck->plate_no]
        );

        return $order->fresh();
    }

    /**
     * Dispatch order (SCHEDULED -> EN_ROUTE)
     */
    public function dispatchOrder(int $id, int $driverId): Order
    {
        $order = $this->repository->findOrFail($id);

        if ($order->status !== 'SCHEDULED') {
            throw new \Exception('Can only dispatch orders in SCHEDULED status');
        }

        if (!$order->truck_id) {
            throw new \Exception('Order must have a truck assigned to be dispatched');
        }

        // Validate driver exists
        // Assuming drivers are users with role DRIVER
        // You can add additional validation here

        $oldStatus = $order->status;
        $order->driver_id = $driverId;
        $order->status = 'EN_ROUTE';
        $order->save();

        // Log activity
        $this->activityLogger->logOrderTransition(
            $order,
            $oldStatus,
            'EN_ROUTE',
            ['driver_id' => $driverId]
        );

        return $order->fresh();
    }

    /**
     * Deliver order (EN_ROUTE -> DELIVERED)
     */
    public function deliverOrder(int $id, int $deliveredLiters): Order
    {
        $order = $this->repository->findOrFail($id);

        if ($order->status !== 'EN_ROUTE') {
            throw new \Exception('Can only deliver orders in EN_ROUTE status');
        }

        if ($deliveredLiters <= 0) {
            throw new \Exception('Delivered liters must be greater than 0');
        }

        if ($deliveredLiters > $order->fuel_liters * 1.1) {
            throw new \Exception('Delivered liters cannot exceed ordered amount by more than 10%');
        }

        $oldStatus = $order->status;
        $order->delivered_liters = $deliveredLiters;
        $order->delivered_at = now();
        $order->status = 'DELIVERED';
        $order->save();

        // Log activity
        $this->activityLogger->logOrderTransition(
            $order,
            $oldStatus,
            'DELIVERED',
            ['delivered_liters' => $deliveredLiters, 'delivered_at' => $order->delivered_at]
        );

        return $order->fresh();
    }

    /**
     * Cancel order (any status except DELIVERED -> CANCELLED)
     */
    public function cancelOrder(int $id, string $reason = null): Order
    {
        $order = $this->repository->findOrFail($id);

        if ($order->status === 'DELIVERED') {
            throw new \Exception('Cannot cancel a delivered order');
        }

        if ($order->status === 'CANCELLED') {
            throw new \Exception('Order is already cancelled');
        }

        $oldStatus = $order->status;
        $order->status = 'CANCELLED';
        if ($reason) {
            $order->cancellation_reason = $reason;
        }
        $order->save();

        // Log activity
        $this->activityLogger->logOrderTransition(
            $order,
            $oldStatus,
            'CANCELLED',
            ['cancellation_reason' => $reason]
        );

        return $order->fresh();
    }

    /**
     * Get orders by driver
     */
    public function getOrdersByDriver(int $driverId): Collection
    {
        return $this->repository->getByDriver($driverId);
    }

    /**
     * Get orders by truck
     */
    public function getOrdersByTruck(int $truckId): Collection
    {
        return $this->repository->getByTruck($truckId);
    }
}
