<?php

namespace Database\Factories;

use App\Modules\Orders\Domain\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'tenant_id' => app()->has('tenant') ? app('tenant')->id : null,
            'client_id' => null,
            'location_id' => null,
            'truck_id' => null,
            'created_by' => null,
            'driver_id' => null,
            'fuel_liters' => fake()->numberBetween(1000, 9000),
            'status' => 'DRAFT',
            'window_start' => now()->addDay(),
            'window_end' => now()->addDay()->addHours(2),
            'delivered_liters' => null,
            'delivered_at' => null,
        ];
    }
}
