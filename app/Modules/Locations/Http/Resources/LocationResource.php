<?php

namespace App\Modules\Locations\Http\Resources;

use App\Modules\Clients\Http\Resources\ClientResource;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'client_id' => $this->client_id,
            'client' => new ClientResource($this->whenLoaded('client')),
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
