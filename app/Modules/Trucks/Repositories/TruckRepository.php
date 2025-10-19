<?php

namespace App\Modules\Trucks\Repositories;

use App\Modules\Trucks\Domain\Models\DeliveryTruck;
use App\Modules\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TruckRepository extends BaseRepository
{
    public function __construct(DeliveryTruck $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all active trucks
     */
    public function getActive(): Collection
    {
        return $this->model
            ->where('active', true)
            ->orderBy('plate_no')
            ->get();
    }

    /**
     * Get paginated trucks
     */
    public function getAllPaginated(bool $activeOnly = false): LengthAwarePaginator
    {
        $query = $this->model->query();

        if ($activeOnly) {
            $query->where('active', true);
        }

        return $query
            ->orderBy('plate_no')
            ->paginate(15);
    }

    /**
     * Check if truck is available for a time window
     */
    public function isAvailable(int $truckId, \DateTime $windowStart, \DateTime $windowEnd, ?int $excludeOrderId = null): bool
    {
        $query = $this->model
            ->find($truckId)
            ->orders()
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

        return $query->count() === 0;
    }

    /**
     * Find truck by plate number
     */
    public function findByPlateNo(string $plateNo): ?DeliveryTruck
    {
        return $this->model
            ->where('plate_no', $plateNo)
            ->first();
    }
}
