<?php

namespace App\Modules\Shared\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait for a model.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Auto-fill tenant_id when creating
        static::creating(function ($model) {
            if (!isset($model->tenant_id) && app()->has('tenant')) {
                $model->tenant_id = app('tenant')->id;
            }
        });

        // Apply global scope to filter by tenant
        static::addGlobalScope('tenant', function (Builder $query) {
            if (app()->has('tenant')) {
                $query->where($query->getModel()->getTable() . '.tenant_id', app('tenant')->id);
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
