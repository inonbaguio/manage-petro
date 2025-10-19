<?php

namespace App\Modules\Trucks\Domain\Models;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryTruck extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'plate_no',
        'tank_capacity_l',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'tank_capacity_l' => 'integer',
        ];
    }

    /**
     * Get all orders for this truck.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'truck_id');
    }
}
