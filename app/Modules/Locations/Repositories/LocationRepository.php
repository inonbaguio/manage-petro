<?php

namespace App\Modules\Locations\Repositories;

use App\Modules\Locations\Domain\Models\Location;
use App\Modules\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LocationRepository extends BaseRepository
{
    public function __construct(Location $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all locations for a specific client
     */
    public function getByClient(int $clientId): LengthAwarePaginator
    {
        return $this->model
            ->where('client_id', $clientId)
            ->with('client')
            ->orderBy('address')
            ->paginate(15);
    }

    /**
     * Find location with client
     */
    public function findWithClient(int $id): ?Location
    {
        return $this->model
            ->with('client')
            ->find($id);
    }

    /**
     * Search locations by address
     */
    public function search(string $query): LengthAwarePaginator
    {
        return $this->model
            ->where('address', 'like', "%{$query}%")
            ->with('client')
            ->paginate(15);
    }

    /**
     * Validate that location belongs to client
     */
    public function belongsToClient(int $locationId, int $clientId): bool
    {
        return $this->model
            ->where('id', $locationId)
            ->where('client_id', $clientId)
            ->exists();
    }
}
