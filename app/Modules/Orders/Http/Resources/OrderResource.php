<?php

namespace App\Modules\Orders\Http\Resources;

use App\Modules\Clients\Http\Resources\ClientResource;
use App\Modules\Locations\Http\Resources\LocationResource;
use App\Modules\Trucks\Http\Resources\TruckResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'client_id' => $this->client_id,
            'location_id' => $this->location_id,
            'truck_id' => $this->truck_id,
            'created_by' => $this->created_by,
            'driver_id' => $this->driver_id,
            'fuel_liters' => $this->fuel_liters,
            'status' => $this->status,
            'window_start' => $this->window_start?->toISOString(),
            'window_end' => $this->window_end?->toISOString(),
            'delivered_liters' => $this->delivered_liters,
            'delivered_at' => $this->delivered_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,

            // Relationships
            'client' => new ClientResource($this->whenLoaded('client')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'truck' => new TruckResource($this->whenLoaded('truck')),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'driver' => $this->whenLoaded('driver', function () {
                return $this->driver ? [
                    'id' => $this->driver->id,
                    'name' => $this->driver->name,
                    'email' => $this->driver->email,
                ] : null;
            }),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
