<?php

namespace App\Modules\ActivityLog\Domain\Models;

use App\Modules\Shared\Traits\BelongsToTenant;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'model_type',
        'model_id',
        'action',
        'old_values',
        'new_values',
        'description',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who performed the activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model that this activity log belongs to
     */
    public function subject()
    {
        return $this->morphTo('subject', 'model_type', 'model_id');
    }

    /**
     * Get a human-readable description of the activity
     */
    public function getFormattedDescriptionAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $modelName = class_basename($this->model_type);
        $userName = $this->user->name ?? 'Unknown User';

        return match($this->action) {
            'created' => "{$userName} created {$modelName} #{$this->model_id}",
            'updated' => "{$userName} updated {$modelName} #{$this->model_id}",
            'deleted' => "{$userName} deleted {$modelName} #{$this->model_id}",
            'submitted' => "{$userName} submitted {$modelName} #{$this->model_id}",
            'scheduled' => "{$userName} scheduled {$modelName} #{$this->model_id}",
            'dispatched' => "{$userName} dispatched {$modelName} #{$this->model_id}",
            'delivered' => "{$userName} delivered {$modelName} #{$this->model_id}",
            'cancelled' => "{$userName} cancelled {$modelName} #{$this->model_id}",
            default => "{$userName} performed {$this->action} on {$modelName} #{$this->model_id}",
        };
    }
}
