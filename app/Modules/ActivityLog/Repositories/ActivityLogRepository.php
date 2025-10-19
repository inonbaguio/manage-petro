<?php

namespace App\Modules\ActivityLog\Repositories;

use App\Modules\ActivityLog\Domain\Models\ActivityLog;
use App\Modules\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ActivityLogRepository extends BaseRepository
{
    public function __construct(ActivityLog $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all activity logs with user relationship
     */
    public function getAllWithUser(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activity logs for a specific model
     */
    public function getByModel(string $modelType, int $modelId): Collection
    {
        return $this->model
            ->with('user')
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activity logs by user
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with('user')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activity logs by action
     */
    public function getByAction(string $action): Collection
    {
        return $this->model
            ->with('user')
            ->where('action', $action)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Search and filter activity logs
     */
    public function searchAndFilter(array $filters): Collection
    {
        $query = $this->model->with('user');

        // Filter by model type
        if (!empty($filters['model_type'])) {
            $query->where('model_type', $filters['model_type']);
        }

        // Filter by action
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // Filter by user
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get recent activity
     */
    public function getRecent(int $limit = 20): Collection
    {
        return $this->model
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
