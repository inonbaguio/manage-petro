<?php

namespace App\Modules\ActivityLog\Services;

use App\Modules\ActivityLog\Domain\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Log an activity
     */
    public function log(
        string $modelType,
        int $modelId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): ActivityLog {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('Cannot log activity without authenticated user');
        }

        return ActivityLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Log a model creation
     */
    public function logCreated(Model $model, ?string $description = null): ActivityLog
    {
        return $this->log(
            get_class($model),
            $model->id,
            'created',
            null,
            $model->getAttributes(),
            $description
        );
    }

    /**
     * Log a model update
     */
    public function logUpdated(Model $model, array $originalAttributes, ?string $description = null): ActivityLog
    {
        $changes = $model->getChanges();
        $oldValues = array_intersect_key($originalAttributes, $changes);

        return $this->log(
            get_class($model),
            $model->id,
            'updated',
            $oldValues,
            $changes,
            $description
        );
    }

    /**
     * Log a model deletion
     */
    public function logDeleted(Model $model, ?string $description = null): ActivityLog
    {
        return $this->log(
            get_class($model),
            $model->id,
            'deleted',
            $model->getAttributes(),
            null,
            $description
        );
    }

    /**
     * Log a custom action
     */
    public function logAction(
        Model $model,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): ActivityLog {
        return $this->log(
            get_class($model),
            $model->id,
            $action,
            $oldValues,
            $newValues,
            $description
        );
    }

    /**
     * Log order state transitions
     */
    public function logOrderTransition(
        Model $order,
        string $oldStatus,
        string $newStatus,
        ?array $additionalData = null
    ): ActivityLog {
        $action = strtolower($newStatus); // e.g., 'submitted', 'scheduled', 'dispatched', etc.

        $description = sprintf(
            'Order #%d transitioned from %s to %s',
            $order->id,
            $oldStatus,
            $newStatus
        );

        return $this->log(
            get_class($order),
            $order->id,
            $action,
            ['status' => $oldStatus],
            array_merge(['status' => $newStatus], $additionalData ?? []),
            $description
        );
    }
}
