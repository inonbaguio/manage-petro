<?php

namespace Database\Factories;

use App\Modules\Locations\Domain\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'tenant_id' => app()->has('tenant') ? app('tenant')->id : null,
            'client_id' => null, // Set via ->for($client)
            'address' => fake()->streetAddress(),
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),
        ];
    }
}
