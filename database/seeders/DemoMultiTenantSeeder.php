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
            // Different client types based on tenant
            if ($tenant->slug === 'acme') {
                // ACME has diverse clients: construction, agriculture, mining, logistics
                $clientA = Client::firstOrCreate(
                    ['name' => 'BuildCo Construction', 'tenant_id' => $tenant->id],
                    [
                        'contact_person' => 'Mike Johnson',
                        'contact_phone' => '555-1001',
                        'contact_email' => 'mike@buildco.com'
                    ]
                );

                $clientB = Client::firstOrCreate(
                    ['name' => 'GreenFields Agriculture', 'tenant_id' => $tenant->id],
                    [
                        'contact_person' => 'Sarah Green',
                        'contact_phone' => '555-1002',
                        'contact_email' => 'sarah@greenfields.com'
                    ]
                );

                $clientC = Client::firstOrCreate(
                    ['name' => 'RockSolid Mining Co', 'tenant_id' => $tenant->id],
                    [
                        'contact_person' => 'David Stone',
                        'contact_phone' => '555-1003',
                        'contact_email' => 'david@rocksolid.com'
                    ]
                );

                $clientD = Client::firstOrCreate(
                    ['name' => 'FastTrack Logistics', 'tenant_id' => $tenant->id],
                    [
                        'contact_person' => 'Lisa Thompson',
                        'contact_phone' => '555-1004',
                        'contact_email' => 'lisa@fasttrack.com'
                    ]
                );

                $clientE = Client::firstOrCreate(
                    ['name' => 'Metro Transport Services', 'tenant_id' => $tenant->id],
                    [
                        'contact_person' => 'Robert Martinez',
                        'contact_phone' => '555-1005',
                        'contact_email' => 'robert@metrotransport.com'
                    ]
                );

                // Locations for ACME clients
                $locA1 = Location::firstOrCreate(
                    ['client_id' => $clientA->id, 'address' => '450 Construction Blvd, Industrial Park', 'tenant_id' => $tenant->id],
                    ['lat' => 34.0522, 'lng' => -118.2437]
                );

                $locA2 = Location::firstOrCreate(
                    ['client_id' => $clientA->id, 'address' => '1200 Builder Ave, West Zone', 'tenant_id' => $tenant->id],
                    ['lat' => 34.0622, 'lng' => -118.2537]
                );

                $locB1 = Location::firstOrCreate(
                    ['client_id' => $clientB->id, 'address' => '2500 Farm Road, County Line', 'tenant_id' => $tenant->id],
                    ['lat' => 34.0722, 'lng' => -118.2637]
                );

                $locB2 = Location::firstOrCreate(
                    ['client_id' => $clientB->id, 'address' => '3800 Harvest Lane, North Fields', 'tenant_id' => $tenant->id],
                    ['lat' => 34.0822, 'lng' => -118.2737]
                );

                $locC1 = Location::firstOrCreate(
                    ['client_id' => $clientC->id, 'address' => '5000 Mining Access Rd, Mountain View', 'tenant_id' => $tenant->id],
                    ['lat' => 34.0922, 'lng' => -118.2837]
                );

                $locD1 = Location::firstOrCreate(
                    ['client_id' => $clientD->id, 'address' => '700 Logistics Center Dr, Warehouse District', 'tenant_id' => $tenant->id],
                    ['lat' => 34.1022, 'lng' => -118.2937]
                );

                $locD2 = Location::firstOrCreate(
                    ['client_id' => $clientD->id, 'address' => '850 Distribution Way, South Hub', 'tenant_id' => $tenant->id],
                    ['lat' => 34.1122, 'lng' => -118.3037]
                );

                $locE1 = Location::firstOrCreate(
                    ['client_id' => $clientE->id, 'address' => '1500 Transit Plaza, Downtown', 'tenant_id' => $tenant->id],
                    ['lat' => 34.1222, 'lng' => -118.3137]
                );
            } else {
                // Globex has simpler setup
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
            }

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
            if ($tenant->slug === 'acme') {
                // More diverse orders for ACME
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
                        'status' => 'SUBMITTED'
                    ],
                    [
                        'created_by' => $dispatcher->id,
                        'fuel_liters' => 12000,
                        'window_start' => now()->addDays(1)->addHours(3),
                        'window_end' => now()->addDays(1)->addHours(5),
                    ]
                );

                Order::firstOrCreate(
                    [
                        'client_id' => $clientC->id,
                        'location_id' => $locC1->id,
                        'tenant_id' => $tenant->id,
                        'status' => 'SCHEDULED'
                    ],
                    [
                        'created_by' => $dispatcher->id,
                        'truck_id' => $truck1->id,
                        'fuel_liters' => 15000,
                        'window_start' => now()->addDays(2),
                        'window_end' => now()->addDays(2)->addHours(2),
                    ]
                );

                Order::firstOrCreate(
                    [
                        'client_id' => $clientD->id,
                        'location_id' => $locD1->id,
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
                        'client_id' => $clientE->id,
                        'location_id' => $locE1->id,
                        'tenant_id' => $tenant->id,
                        'status' => 'DELIVERED'
                    ],
                    [
                        'created_by' => $dispatcher->id,
                        'truck_id' => $truck1->id,
                        'driver_id' => $driver->id,
                        'fuel_liters' => 8000,
                        'delivered_liters' => 8000,
                        'delivered_at' => now()->subDay(),
                        'window_start' => now()->subDay(),
                        'window_end' => now()->subDay()->addHours(2),
                    ]
                );

                Order::firstOrCreate(
                    [
                        'client_id' => $clientA->id,
                        'location_id' => $locA2->id,
                        'tenant_id' => $tenant->id,
                        'status' => 'DELIVERED'
                    ],
                    [
                        'created_by' => $dispatcher->id,
                        'truck_id' => $truck2->id,
                        'driver_id' => $driver->id,
                        'fuel_liters' => 10000,
                        'delivered_liters' => 10000,
                        'delivered_at' => now()->subDays(2),
                        'window_start' => now()->subDays(2),
                        'window_end' => now()->subDays(2)->addHours(2),
                    ]
                );

                Order::firstOrCreate(
                    [
                        'client_id' => $clientD->id,
                        'location_id' => $locD2->id,
                        'tenant_id' => $tenant->id,
                        'status' => 'DELIVERED'
                    ],
                    [
                        'created_by' => $dispatcher->id,
                        'truck_id' => $truck1->id,
                        'driver_id' => $driver->id,
                        'fuel_liters' => 9500,
                        'delivered_liters' => 9500,
                        'delivered_at' => now()->subDays(3),
                        'window_start' => now()->subDays(3),
                        'window_end' => now()->subDays(3)->addHours(2),
                    ]
                );
            } else {
                // Globex simple orders
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
}
