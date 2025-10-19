<?php

namespace App\Modules\Trucks\Services;

use App\Modules\Trucks\Domain\Models\DeliveryTruck;
use App\Modules\Trucks\Repositories\TruckRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TruckService
{
    public function __construct(
        private TruckRepository $repository
    ) {}

    /**
     * Get all trucks
     */
    public function getAllTrucks(bool $activeOnly = false): LengthAwarePaginator
    {
        return $this->repository->getAllPaginated($activeOnly);
    }

    /**
     * Get active trucks for dropdown
     */
    public function getActiveTrucks(): Collection
    {
        return $this->repository->getActive();
    }

    /**
     * Get a single truck
     */
    public function getTruck(int $id): ?DeliveryTruck
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Create a new truck
     */
    public function createTruck(array $data): DeliveryTruck
    {
        // Check if plate number already exists for this tenant
        if ($this->repository->findByPlateNo($data['plate_no'])) {
            throw new \Exception('Truck with this plate number already exists');
        }

        return $this->repository->create($data);
    }

    /**
     * Update a truck
     */
    public function updateTruck(int $id, array $data): DeliveryTruck
    {
        $truck = $this->repository->findOrFail($id);

        // If plate number is changing, check it doesn't exist
        if (isset($data['plate_no']) && $data['plate_no'] !== $truck->plate_no) {
            $existing = $this->repository->findByPlateNo($data['plate_no']);
            if ($existing && $existing->id !== $truck->id) {
                throw new \Exception('Truck with this plate number already exists');
            }
        }

        $this->repository->update($truck, $data);

        return $truck->fresh();
    }

    /**
     * Delete a truck
     */
    public function deleteTruck(int $id): bool
    {
        $truck = $this->repository->findOrFail($id);

        // Check if truck has orders
        if ($truck->orders()->count() > 0) {
            throw new \Exception('Cannot delete truck with existing orders. Set as inactive instead.');
        }

        return $this->repository->delete($truck);
    }

    /**
     * Toggle truck active status
     */
    public function toggleActive(int $id): DeliveryTruck
    {
        $truck = $this->repository->findOrFail($id);
        $truck->active = !$truck->active;
        $truck->save();

        return $truck;
    }

    /**
     * Check if truck is available for a time window
     */
    public function isTruckAvailable(int $truckId, \DateTime $windowStart, \DateTime $windowEnd, ?int $excludeOrderId = null): bool
    {
        return $this->repository->isAvailable($truckId, $windowStart, $windowEnd, $excludeOrderId);
    }
}
