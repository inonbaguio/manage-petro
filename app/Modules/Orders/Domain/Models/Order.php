<?php

namespace App\Modules\Orders\Domain\Models;

use App\Models\User;
use App\Modules\Clients\Domain\Models\Client;
use App\Modules\Locations\Domain\Models\Location;
use App\Modules\Shared\Concerns\BelongsToTenant;
use App\Modules\Trucks\Domain\Models\DeliveryTruck;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'location_id',
        'truck_id',
        'created_by',
        'driver_id',
        'fuel_liters',
        'status',
        'window_start',
        'window_end',
        'delivered_liters',
        'delivered_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'fuel_liters' => 'integer',
            'delivered_liters' => 'integer',
            'window_start' => 'datetime',
            'window_end' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Get the client for this order.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the location for this order.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the truck assigned to this order.
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(DeliveryTruck::class, 'truck_id');
    }

    /**
     * Get the user who created this order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the driver assigned to this order.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
