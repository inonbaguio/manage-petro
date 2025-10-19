<?php

namespace App\Modules\Locations\Services;

use App\Modules\Locations\Domain\Models\Location;
use App\Modules\Locations\Repositories\LocationRepository;
use App\Modules\Clients\Repositories\ClientRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LocationService
{
    public function __construct(
        private LocationRepository $repository,
        private ClientRepository $clientRepository
    ) {}

    /**
     * Get all locations (optionally filtered by client)
     */
    public function getAllLocations(?int $clientId = null, ?string $search = null): LengthAwarePaginator
    {
        if ($search) {
            return $this->repository->search($search);
        }

        if ($clientId) {
            return $this->repository->getByClient($clientId);
        }

        return $this->repository->paginate(15);
    }

    /**
     * Get a single location
     */
    public function getLocation(int $id): ?Location
    {
        return $this->repository->findWithClient($id);
    }

    /**
     * Create a new location
     */
    public function createLocation(array $data): Location
    {
        // Verify client exists and belongs to same tenant
        $this->clientRepository->findOrFail($data['client_id']);

        return $this->repository->create($data);
    }

    /**
     * Update a location
     */
    public function updateLocation(int $id, array $data): Location
    {
        $location = $this->repository->findOrFail($id);

        // If client_id is being changed, verify new client exists
        if (isset($data['client_id']) && $data['client_id'] !== $location->client_id) {
            $this->clientRepository->findOrFail($data['client_id']);
        }

        $this->repository->update($location, $data);

        return $location->fresh();
    }

    /**
     * Delete a location
     */
    public function deleteLocation(int $id): bool
    {
        $location = $this->repository->findOrFail($id);

        // Check if location has orders
        if ($location->orders()->count() > 0) {
            throw new \Exception('Cannot delete location with existing orders');
        }

        return $this->repository->delete($location);
    }

    /**
     * Validate location belongs to client
     */
    public function validateLocationBelongsToClient(int $locationId, int $clientId): bool
    {
        return $this->repository->belongsToClient($locationId, $clientId);
    }
}
