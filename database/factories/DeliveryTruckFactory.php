<?php

namespace Database\Factories;

use App\Modules\Trucks\Domain\Models\DeliveryTruck;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryTruckFactory extends Factory
{
    protected $model = DeliveryTruck::class;

    public function definition(): array
    {
        return [
            'tenant_id' => app()->has('tenant') ? app('tenant')->id : null,
            'plate_no' => strtoupper(fake()->bothify('TRK-###')),
            'tank_capacity_l' => fake()->numberBetween(10000, 30000),
            'active' => true,
        ];
    }
}
