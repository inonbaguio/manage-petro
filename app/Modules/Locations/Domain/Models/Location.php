<?php

namespace App\Modules\Locations\Domain\Models;

use App\Modules\Clients\Domain\Models\Client;
use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'address',
        'lat',
        'lng',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    /**
     * Get the client that owns this location.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get all orders for this location.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
