<?php

namespace App\Modules\Clients\Repositories;

use App\Modules\Clients\Domain\Models\Client;
use App\Modules\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClientRepository extends BaseRepository
{
    public function __construct(Client $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all clients with their locations count
     */
    public function getAllWithLocationsCount(): LengthAwarePaginator
    {
        return $this->model
            ->withCount('locations')
            ->orderBy('name')
            ->paginate(15);
    }

    /**
     * Find client with locations
     */
    public function findWithLocations(int $id): ?Client
    {
        return $this->model
            ->with('locations')
            ->find($id);
    }

    /**
     * Search clients by name
     */
    public function search(string $query): LengthAwarePaginator
    {
        return $this->model
            ->where('name', 'like', "%{$query}%")
            ->orWhere('contact_person', 'like', "%{$query}%")
            ->orWhere('contact_email', 'like', "%{$query}%")
            ->paginate(15);
    }

    /**
     * Search and filter clients with advanced criteria
     */
    public function searchAndFilter(array $filters): \Illuminate\Support\Collection
    {
        $query = $this->model->withCount('locations');

        // Full-text search across name, contact person, email, phone
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('contact_person', 'like', "%{$searchTerm}%")
                  ->orWhere('contact_email', 'like', "%{$searchTerm}%")
                  ->orWhere('contact_phone', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by having locations
        if (isset($filters['has_locations'])) {
            if ($filters['has_locations']) {
                $query->has('locations');
            } else {
                $query->doesntHave('locations');
            }
        }

        // Filter by minimum location count
        if (!empty($filters['min_locations'])) {
            $query->has('locations', '>=', (int)$filters['min_locations']);
        }

        return $query->orderBy('name')->get();
    }
}
