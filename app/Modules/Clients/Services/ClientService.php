<?php

namespace App\Modules\Clients\Services;

use App\Modules\Clients\Domain\Models\Client;
use App\Modules\Clients\Repositories\ClientRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClientService
{
    public function __construct(
        private ClientRepository $repository
    ) {}

    /**
     * Get all clients
     */
    public function getAllClients(?string $search = null): LengthAwarePaginator
    {
        if ($search) {
            return $this->repository->search($search);
        }

        return $this->repository->getAllWithLocationsCount();
    }

    /**
     * Get a single client
     */
    public function getClient(int $id): ?Client
    {
        return $this->repository->findWithLocations($id);
    }

    /**
     * Create a new client
     */
    public function createClient(array $data): Client
    {
        return $this->repository->create($data);
    }

    /**
     * Update a client
     */
    public function updateClient(int $id, array $data): Client
    {
        $client = $this->repository->findOrFail($id);
        $this->repository->update($client, $data);

        return $client->fresh();
    }

    /**
     * Delete a client
     */
    public function deleteClient(int $id): bool
    {
        $client = $this->repository->findOrFail($id);

        // Check if client has locations
        if ($client->locations()->count() > 0) {
            throw new \Exception('Cannot delete client with existing locations');
        }

        return $this->repository->delete($client);
    }
}
