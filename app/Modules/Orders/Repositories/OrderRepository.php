<?php

namespace App\Modules\Orders\Repositories;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all orders with relations
     */
    public function getAllWithRelations(): LengthAwarePaginator
    {
        return $this->model
            ->with(['client', 'location', 'truck', 'creator', 'driver'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    /**
     * Find order with all relations
     */
    public function findWithRelations(int $id): ?Order
    {
        return $this->model
            ->with(['client', 'location', 'truck', 'creator', 'driver'])
            ->find($id);
    }

    /**
     * Get orders by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model
            ->where('status', $status)
            ->with(['client', 'location', 'truck', 'creator', 'driver'])
            ->orderBy('window_start')
            ->get();
    }

    /**
     * Get orders for a specific client
     */
    public function getByClient(int $clientId): LengthAwarePaginator
    {
        return $this->model
            ->where('client_id', $clientId)
            ->with(['location', 'truck', 'creator', 'driver'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    /**
     * Get orders for a specific truck
     */
    public function getByTruck(int $truckId): Collection
    {
        return $this->model
            ->where('truck_id', $truckId)
            ->with(['client', 'location', 'creator', 'driver'])
            ->orderBy('window_start')
            ->get();
    }

    /**
     * Get orders for a specific driver
     */
    public function getByDriver(int $driverId): Collection
    {
        return $this->model
            ->where('driver_id', $driverId)
            ->with(['client', 'location', 'truck'])
            ->orderBy('window_start')
            ->get();
    }

    /**
     * Get scheduled orders for a truck in a time window
     */
    public function getScheduledInWindow(int $truckId, \DateTime $windowStart, \DateTime $windowEnd, ?int $excludeOrderId = null): Collection
    {
        $query = $this->model
            ->where('truck_id', $truckId)
            ->whereIn('status', ['SCHEDULED', 'EN_ROUTE'])
            ->where(function ($q) use ($windowStart, $windowEnd) {
                $q->whereBetween('window_start', [$windowStart, $windowEnd])
                    ->orWhereBetween('window_end', [$windowStart, $windowEnd])
                    ->orWhere(function ($q2) use ($windowStart, $windowEnd) {
                        $q2->where('window_start', '<=', $windowStart)
                            ->where('window_end', '>=', $windowEnd);
                    });
            });

        if ($excludeOrderId) {
            $query->where('id', '!=', $excludeOrderId);
        }

        return $query->get();
    }

    /**
     * Get orders since a specific date
     */
    public function getOrdersSince(\DateTime $dateFrom): Collection
    {
        return $this->model
            ->where('created_at', '>=', $dateFrom)
            ->get();
    }

    /**
     * Get truck IDs that are currently in use (SCHEDULED or EN_ROUTE)
     */
    public function getTrucksInUse(): Collection
    {
        return $this->model
            ->whereIn('status', ['SCHEDULED', 'EN_ROUTE'])
            ->whereNotNull('truck_id')
            ->distinct()
            ->pluck('truck_id');
    }

    /**
     * Get currently active orders (SCHEDULED or EN_ROUTE)
     */
    public function getCurrentActiveOrders(): Collection
    {
        return $this->model
            ->whereIn('status', ['SCHEDULED', 'EN_ROUTE'])
            ->get();
    }

    /**
     * Get recent orders with relations
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model
            ->with(['client', 'location', 'truck', 'creator', 'driver'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search and filter orders with advanced criteria
     */
    public function searchAndFilter(array $filters): Collection
    {
        $query = $this->model->with(['client', 'location', 'truck', 'creator', 'driver']);

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Status filter (can be array)
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Client filter
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Location filter
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        // Driver filter
        if (!empty($filters['driver_id'])) {
            $query->where('driver_id', $filters['driver_id']);
        }

        // Truck filter
        if (!empty($filters['truck_id'])) {
            $query->where('truck_id', $filters['truck_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
