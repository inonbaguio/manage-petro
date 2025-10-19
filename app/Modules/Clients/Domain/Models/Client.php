<?php

namespace App\Modules\Clients\Domain\Models;

use App\Modules\Locations\Domain\Models\Location;
use App\Modules\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'contact_person',
        'contact_phone',
        'contact_email',
    ];

    /**
     * Get all locations for this client.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
