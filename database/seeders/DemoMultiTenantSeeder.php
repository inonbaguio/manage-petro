<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Tenant, User};
use App\Modules\Clients\Domain\Models\Client;
use App\Modules\Locations\Domain\Models\Location;
use App\Modules\Trucks\Domain\Models\DeliveryTruck;
use App\Modules\Orders\Domain\Models\Order;
use Illuminate\Support\Facades\Hash;

class DemoMultiTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            ['name' => 'Acme Fuel', 'slug' => 'acme'],
            ['name' => 'Globex Energy', 'slug' => 'globex'],
        ];

        foreach ($tenants as $t) {
            $tenant = Tenant::firstOrCreate(['slug' => $t['slug']], $t);

            // Set tenant context so BelongsToTenant applies
            app()->instance('tenant', $tenant);

            // Users
            $admin = User::firstOrCreate(
                ['email' => "admin@{$tenant->slug}.test"],
                [
                    'name' => 'Admin User',
                    'password' => Hash::make('password'),
                    'tenant_id' => $tenant->id,
                    'role' => 'ADMIN'
                ]
            );

            $dispatcher = User::firstOrCreate(
                ['email' => "dispatcher@{$tenant->slug}.test"],
                [
                    'name' => 'Dispatcher User',
                    'password' => Hash::make('password'),
                    'tenant_id' => $tenant->id,
                    'role' => 'DISPATCHER'
                ]
            );

            $driver = User::firstOrCreate(
                ['email' => "driver@{$tenant->slug}.test"],
                [
                    'name' => 'Driver User',
                    'password' => Hash::make('password'),
                    'tenant_id' => $tenant->id,
                    'role' => 'DRIVER'
                ]
            );

            $clientRep = User::firstOrCreate(
                ['email' => "clientrep@{$tenant->slug}.test"],
                [
                    'name' => 'Client Representative',
                    'password' => Hash::make('password'),
                    'tenant_id' => $tenant->id,
                    'role' => 'CLIENT_REP'
                ]
            );

            // Clients + Locations
            $clientA = Client::firstOrCreate(
                ['name' => 'North Site', 'tenant_id' => $tenant->id],
                [
                    'contact_person' => 'John North',
                    'contact_phone' => '555-0001',
                    'contact_email' => 'john@northsite.com'
                ]
            );

            $clientB = Client::firstOrCreate(
                ['name' => 'South Site', 'tenant_id' => $tenant->id],
                [
                    'contact_person' => 'Jane South',
                    'contact_phone' => '555-0002',
                    'contact_email' => 'jane@southsite.com'
                ]
            );

            $locA1 = Location::firstOrCreate(
                ['client_id' => $clientA->id, 'address' => '100 Industrial Way', 'tenant_id' => $tenant->id],
                ['lat' => 34.0522, 'lng' => -118.2437]
            );

            $locB1 = Location::firstOrCreate(
                ['client_id' => $clientB->id, 'address' => '200 Depot Ave', 'tenant_id' => $tenant->id],
                ['lat' => 34.0622, 'lng' => -118.2537]
            );

            // Trucks
            $truck1 = DeliveryTruck::firstOrCreate(
                ['plate_no' => strtoupper($tenant->slug) . '100', 'tenant_id' => $tenant->id],
                ['tank_capacity_l' => 20000, 'active' => true]
            );

            $truck2 = DeliveryTruck::firstOrCreate(
                ['plate_no' => strtoupper($tenant->slug) . '200', 'tenant_id' => $tenant->id],
                ['tank_capacity_l' => 30000, 'active' => true]
            );

            // Orders across statuses
            Order::firstOrCreate(
                [
                    'client_id' => $clientA->id,
                    'location_id' => $locA1->id,
                    'tenant_id' => $tenant->id,
                    'status' => 'SUBMITTED'
                ],
                [
                    'created_by' => $dispatcher->id,
                    'fuel_liters' => 5000,
                    'window_start' => now()->addDays(1),
                    'window_end' => now()->addDays(1)->addHours(2),
                ]
            );

            Order::firstOrCreate(
                [
                    'client_id' => $clientB->id,
                    'location_id' => $locB1->id,
                    'tenant_id' => $tenant->id,
                    'status' => 'SCHEDULED'
                ],
                [
                    'created_by' => $dispatcher->id,
                    'truck_id' => $truck1->id,
                    'fuel_liters' => 8000,
                    'window_start' => now()->addDays(2),
                    'window_end' => now()->addDays(2)->addHours(2),
                ]
            );

            Order::firstOrCreate(
                [
                    'client_id' => $clientB->id,
                    'location_id' => $locB1->id,
                    'tenant_id' => $tenant->id,
                    'status' => 'EN_ROUTE'
                ],
                [
                    'created_by' => $dispatcher->id,
                    'truck_id' => $truck2->id,
                    'driver_id' => $driver->id,
                    'fuel_liters' => 7000,
                    'window_start' => now(),
                    'window_end' => now()->addHours(2),
                ]
            );

            Order::firstOrCreate(
                [
                    'client_id' => $clientA->id,
                    'location_id' => $locA1->id,
                    'tenant_id' => $tenant->id,
                    'status' => 'DELIVERED'
                ],
                [
                    'created_by' => $dispatcher->id,
                    'truck_id' => $truck1->id,
                    'driver_id' => $driver->id,
                    'fuel_liters' => 6000,
                    'delivered_liters' => 6000,
                    'delivered_at' => now()->subDay(),
                    'window_start' => now()->subDay(),
                    'window_end' => now()->subDay()->addHours(2),
                ]
            );
        }
    }
}
