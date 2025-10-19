<?php

namespace App\Providers;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Clients\Domain\Models\Client;
use App\Modules\Locations\Domain\Models\Location;
use App\Modules\Trucks\Domain\Models\DeliveryTruck;
use App\Policies\OrderPolicy;
use App\Policies\ClientPolicy;
use App\Policies\LocationPolicy;
use App\Policies\TruckPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Order::class => OrderPolicy::class,
        Client::class => ClientPolicy::class,
        Location::class => LocationPolicy::class,
        DeliveryTruck::class => TruckPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
