<?php

namespace Database\Factories;

use App\Modules\Clients\Domain\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'tenant_id' => app()->has('tenant') ? app('tenant')->id : null,
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'contact_email' => fake()->companyEmail(),
        ];
    }
}
