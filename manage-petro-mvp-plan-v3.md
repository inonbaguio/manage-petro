# Manage Petro — **MVP Plan (v3)**
**Single Laravel app** with React in `resources/js` (Vite).  
**Tenancy**: **slug-in-path** (`/{tenant}/...`) for both SPA and API.  
**Architecture**: **modular monolith** — modules under `app/Modules/*`.  
**Tests**: **PHPUnit**.  
**Docker**: Nginx + PHP-FPM, MySQL 8, Redis 7, Node (Vite).

---

## 0) What you’ll ship today
- One Laravel repository named **`manage-petro/`**.
- React SPA mounted from `resources/js` using **`@vite`**.
- API lives under `/api/{tenant}/...`.
- SPA routes live under `/{tenant}/*` with a Laravel fallback view.
- Core modules: **Auth, Clients, Locations, Trucks, Orders, Shared**.
- Slug tenancy via middleware + global scope.
- **Seed data for multiple tenants (`acme`, `globex`)** to demo isolation.

---

## 1) Repository layout (Laravel default + modules)
```
manage-petro/
  app/
    Http/
      Controllers/
      Middleware/
    Models/
    Modules/
      Shared/                          # Cross-cutting concerns
        Concerns/
          BelongsToTenant.php          # Tenant scoping trait
        Http/
          ApiResponse.php              # Standardized responses
          BaseFormRequest.php          # Base validation
        Repositories/
          BaseRepository.php           # Common CRUD operations
        Tenancy/
          TenantMiddleware.php         # Tenant resolution
      Auth/
        Http/
          LoginController.php
          MeController.php
      Clients/
        Domain/Models/Client.php       # Eloquent model
        Http/
          Controllers/ClientController.php
          Requests/
            StoreClientRequest.php
            UpdateClientRequest.php
          Resources/ClientResource.php
        Repositories/ClientRepository.php
        Services/ClientService.php     # Business logic
      Locations/
        Domain/Models/Location.php
        Http/
          Controllers/LocationController.php
          Requests/...
          Resources/LocationResource.php
        Repositories/LocationRepository.php
        Services/LocationService.php
      Trucks/
        Domain/Models/DeliveryTruck.php
        Http/
          Controllers/TruckController.php
          Requests/...
          Resources/TruckResource.php
        Repositories/TruckRepository.php
        Services/TruckService.php
      Orders/
        Domain/Models/Order.php
        Http/
          Controllers/
            OrderController.php        # CRUD operations
            OrderActionsController.php # Lifecycle actions
          Requests/...
          Resources/OrderResource.php
        Repositories/OrderRepository.php
        Services/OrderService.php      # Lifecycle management
    Providers/
  database/
    factories/                         # All model factories
    migrations/                        # Database schema
    seeders/
      DemoMultiTenantSeeder.php       # Seeds acme & globex
  routes/
    api.php                            # All API routes
    web.php                            # SPA catch-all
  ARCHITECTURE.md                      # Full architecture docs
```

> **Module ownership**: Each module follows layered architecture:
> - **Domain/Models** - Eloquent models with relationships
> - **Repositories** - Database queries and data access
> - **Services** - Business logic and orchestration
> - **Http/Controllers** - Thin HTTP handlers (delegate to services)
> - **Http/Requests** - Form validation
> - **Http/Resources** - JSON transformation
>
> **Shared module** contains cross-cutting concerns used by all modules.

---

## 2) Slug Tenancy (path param)
- **All API routes**: `/api/{tenant}/...` (example: `/api/acme/orders`).  
- **All SPA routes**: `/{tenant}/*` (example: `/acme/dashboard`).  
- **TenantMiddleware** resolves slug → loads `Tenant` → stores in container (`app('tenant')`).  
- **BelongsToTenant** trait sets `tenant_id` on create + adds global scope filtering by `tenant_id`.

### Middleware
```php
// app/Modules/Shared/Tenancy/TenantMiddleware.php
namespace App\Modules\Shared\Tenancy;
use Closure; use App\Models\Tenant;

class TenantMiddleware {
  public function handle($request, Closure $next) {
    $slug = $request->route('tenant');
    $tenant = Tenant::where('slug', $slug)->firstOrFail();
    app()->instance('tenant', $tenant);
    return $next($request);
  }
}
```

### Model concern
```php
// app/Modules/Shared/Concerns/BelongsToTenant.php
namespace App\Modules\Shared\Concerns;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant {
  protected static function bootBelongsToTenant(): void {
    static::creating(fn($m) => $m->tenant_id ??= app('tenant')->id);
    static::addGlobalScope('tenant', fn(Builder $q) => $q->where($q->getModel()->getTable().'.tenant_id', app('tenant')->id));
  }
}
```

---

## 3) Routes (web + api)
```php
// routes/web.php
use Illuminate\Support\Facades\Route;

Route::get('/{tenant}/{any?}', function () {
  return view('app'); // SPA shell
})->where('any', '.*');
```

```php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Modules\Shared\Tenancy\TenantMiddleware;

Route::prefix('{tenant}')->middleware([TenantMiddleware::class])->group(function () {
  // Auth
  Route::post('auth/login', [\App\Modules\Auth\Http\LoginController::class, 'login']);
  Route::post('auth/logout', [\App\Modules\Auth\Http\LoginController::class, 'logout']);
  Route::get('auth/me', [\App\Modules\Auth\Http\MeController::class, 'show'])->middleware('auth:sanctum');

  // Resources
  Route::apiResource('clients', \App\Modules\Clients\Http\ClientController::class);
  Route::apiResource('clients.locations', \App\Modules\Locations\Http\LocationController::class);
  Route::apiResource('trucks', \App\Modules\Trucks\Http\TruckController::class);
  Route::apiResource('orders', \App\Modules\Orders\Http\OrderController::class);

  // Actions
  Route::post('orders/{order}/submit', [\App\Modules\Orders\Http\OrderActions::class, 'submit']);
  Route::post('orders/{order}/schedule', [\App\Modules\Orders\Http\OrderActions::class, 'schedule']);
  Route::post('orders/{order}/dispatch', [\App\Modules\Orders\Http\OrderActions::class, 'dispatch']);
  Route::post('orders/{order}/deliver', [\App\Modules\Orders\Http\OrderActions::class, 'deliver']);
  Route::post('orders/{order}/cancel', [\App\Modules\Orders\Http\OrderActions::class, 'cancel']);
});
```

---

## 4) SPA shell & Vite
```blade
{{-- resources/views/app.blade.php --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Petro</title>
  @vite('resources/js/app.tsx')
</head>
<body>
  <div id="root"></div>
</body>
</html>
```

### React entry
```tsx
// resources/js/app.tsx
import React from "react";
import ReactDOM from "react-dom/client";
import { createBrowserRouter, RouterProvider } from "react-router-dom";
import { routes } from "./router";

ReactDOM.createRoot(document.getElementById("root")!).render(
  <React.StrictMode>
    <RouterProvider router={routes} />
  </React.StrictMode>
);
```

### Router with tenant prefix
```tsx
// resources/js/router.tsx
import { createBrowserRouter } from "react-router-dom";
import AppLayout from "./features/layout/AppLayout";
import Login from "./features/auth/Login";
import Dashboard from "./features/dashboard/Dashboard";
import Clients from "./features/clients/List";
import Trucks from "./features/trucks/List";
import Orders from "./features/orders/List";

export const routes = createBrowserRouter([
  {
    path: "/:tenant",
    element: <AppLayout/>,
    children: [
      { path: "login", element: <Login/> },
      { path: "dashboard", element: <Dashboard/> },
      { path: "clients", element: <Clients/> },
      { path: "trucks", element: <Trucks/> },
      { path: "orders", element: <Orders/> },
    ],
  },
  { path: "*", element: <Login/> },
]);
```

### Axios bound to tenant
```ts
// resources/js/lib/useTenant.ts
import { useParams } from "react-router-dom";
export const useTenant = () => useParams<{tenant: string}>().tenant!;

// resources/js/lib/api.ts
import axios from "axios";
export const api = (tenant: string) => axios.create({ baseURL: `/api/${tenant}`, withCredentials: true });
```

### Vite config
```ts
// vite.config.ts
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [
    laravel({ input: ['resources/js/app.tsx'], refresh: true }),
    react(),
  ],
  server: { host: true, port: 5173 },
})
```

---

## 5) Data model (core)
**Tenant, User, Client, Location, DeliveryTruck, Order** with `tenant_id` on all.

Key constraints:
- unique `(plate_no, tenant_id)`.
- `Location.client_id` must belong to same `tenant_id`.
- order lifecycle/status checks in service layer.

---

## 6) Docker (dev)
> Serves Laravel via **Nginx** on `http://localhost:8000`. Vite dev server at `http://localhost:5173` (loaded by `@vite`).

### `docker-compose.yml`
```yaml
version: "3.9"
services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: manage_petro
      MYSQL_USER: mp
      MYSQL_PASSWORD: mp
      MYSQL_ROOT_PASSWORD: root
    ports: ["3307:3306"]
    volumes: [ "mysql_data:/var/lib/mysql" ]
    command: ["--default-authentication-plugin=mysql_native_password"]

  redis:
    image: redis:7-alpine
    ports: ["6379:6379"]

  php:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - ./:/var/www/html
    environment:
      DB_HOST: mysql
      DB_PORT: 3306
      DB_DATABASE: manage_petro
      DB_USERNAME: mp
      DB_PASSWORD: mp
      REDIS_HOST: redis
    depends_on: [ mysql, redis ]

  node:
    image: node:20-alpine
    working_dir: /var/www/html
    command: sh -c "npm ci && npm run dev -- --host 0.0.0.0"
    volumes:
      - ./:/var/www/html
    ports: ["5173:5173"]
    depends_on: [ php ]

  nginx:
    image: nginx:alpine
    ports: ["8000:80"]
    volumes:
      - ./public:/var/www/html/public:ro
      - ./ops/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on: [ php, node ]

volumes:
  mysql_data:
```

### `Dockerfile` (PHP-FPM)
```dockerfile
FROM php:8.3-fpm-alpine

RUN docker-php-ext-install pdo pdo_mysql
RUN apk add --no-cache git zip unzip
RUN pecl install redis && docker-php-ext-enable redis

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
```

### `ops/nginx/default.conf`
```nginx
server {
  listen 80;
  server_name localhost;

  root /var/www/html/public;
  index index.php;

  location / {
    try_files $uri $uri/ /index.php?$query_string;
  }

  location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass php:9000;
  }

  location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
    expires 1d;
    add_header Cache-Control "public, must-revalidate";
    try_files $uri /index.php?$query_string;
  }
}
```

---

## 7) Install & run
```bash
# 1) Copy env
cp .env.example .env

# 2) Boot containers
docker compose up -d --build

# 3) Backend deps & key
docker compose exec php composer install
docker compose exec php php artisan key:generate

# 4) Migrate + seed (creates multiple tenants)
docker compose exec php php artisan migrate --seed

# 5) Open
open http://localhost:8000/acme/login
open http://localhost:8000/globex/login
```

**.env essentials**
```
APP_URL=http://localhost:8000
SESSION_DOMAIN=localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=manage_petro
DB_USERNAME=mp
DB_PASSWORD=mp

REDIS_HOST=redis
REDIS_PORT=6379

SANCTUM_STATEFUL_DOMAINS=localhost:8000
```

---

## 8) PHPUnit config
```xml
<!-- phpunit.xml -->
<phpunit bootstrap="vendor/autoload.php" colors="true">
  <testsuites>
    <testsuite name="Feature">
      <directory suffix="Test.php">./tests/Feature</directory>
    </testsuite>
    <testsuite name="Unit">
      <directory suffix="Test.php">./tests/Unit</directory>
    </testsuites>
</phpunit>
```

Sample feature test (slug path + API):
```php
public function test_creates_order_scoped_to_tenant(): void
{
    $tenant = Tenant::factory()->create(['slug' => 'acme']);
    $user = User::factory()->for($tenant)->create();

    $payload = [...]; // client_id, location_id, fuel_liters, window_start, window_end

    $this->actingAs($user)
         ->postJson("/api/{$tenant->slug}/orders", $payload)
         ->assertCreated()
         ->assertJsonPath('data.tenant_id', $tenant->id);
}
```
Cross-tenant read should fail:
```php
$this->actingAs($userFromAcme)
     ->getJson("/api/globex/orders/{$acmeOrder->id}")
     ->assertStatus(404); // or 403 by policy
```

---

## 9) **Multi-tenant Seeding (required)**

> Seeds **two tenants** (`acme`, `globex`) with users, clients, locations, trucks, and a small set of orders in different statuses. Data is isolated per tenant. During seeding we **set the TenantContext** so `BelongsToTenant` auto-fills `tenant_id`.

### Seeder
```php
// database/seeders/DemoMultiTenantSeeder.php
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
                ['name' => 'Admin', 'password' => Hash::make('password'), 'tenant_id' => $tenant->id, 'role' => 'ADMIN']
            );
            $dispatcher = User::firstOrCreate(
                ['email' => "dispatcher@{$tenant->slug}.test"],
                ['name' => 'Dispatcher', 'password' => Hash::make('password'), 'tenant_id' => $tenant->id, 'role' => 'DISPATCHER']
            );
            $driver = User::firstOrCreate(
                ['email' => "driver@{$tenant->slug}.test"],
                ['name' => 'Driver', 'password' => Hash::make('password'), 'tenant_id' => $tenant->id, 'role' => 'DRIVER']
            );
            $clientRep = User::firstOrCreate(
                ['email' => "clientrep@{$tenant->slug}.test"],
                ['name' => 'Client Rep', 'password' => Hash::make('password'), 'tenant_id' => $tenant->id, 'role' => 'CLIENT_REP']
            );

            // Clients + Locations
            $clientA = Client::factory()->create(['name' => 'North Site']);
            $clientB = Client::factory()->create(['name' => 'South Site']);
            $locA1 = Location::factory()->for($clientA)->create(['address' => '100 Industrial Way']);
            $locB1 = Location::factory()->for($clientB)->create(['address' => '200 Depot Ave']);

            // Trucks
            $truck1 = DeliveryTruck::factory()->create(['plate_no' => strtoupper($tenant->slug).'100', 'tank_capacity_l' => 20000, 'active' => true]);
            $truck2 = DeliveryTruck::factory()->create(['plate_no' => strtoupper($tenant->slug).'200', 'tank_capacity_l' => 30000, 'active' => true]);

            // Orders across statuses
            Order::factory()->create([
                'client_id' => $clientA->id, 'location_id' => $locA1->id,
                'created_by' => $dispatcher->id, 'fuel_liters' => 5000,
                'status' => 'SUBMITTED'
            ]);
            Order::factory()->create([
                'client_id' => $clientB->id, 'location_id' => $locB1->id,
                'created_by' => $dispatcher->id, 'truck_id' => $truck1->id,
                'fuel_liters' => 8000, 'status' => 'SCHEDULED'
            ]);
            Order::factory()->create([
                'client_id' => $clientB->id, 'location_id' => $locB1->id,
                'created_by' => $dispatcher->id, 'truck_id' => $truck2->id, 'driver_id' => $driver->id,
                'fuel_liters' => 7000, 'status' => 'EN_ROUTE'
            ]);
            Order::factory()->create([
                'client_id' => $clientA->id, 'location_id' => $locA1->id,
                'created_by' => $dispatcher->id, 'truck_id' => $truck1->id, 'driver_id' => $driver->id,
                'fuel_liters' => 6000, 'status' => 'DELIVERED'
            ]);
        }
    }
}
```

### DatabaseSeeder
```php
// database/seeders/DatabaseSeeder.php
public function run(): void
{
    $this->call(DemoMultiTenantSeeder::class);
}
```

### Factories (examples)
```php
// database/factories/ClientFactory.php
public function definition(): array {
  return ['name' => fake()->company(), 'tenant_id' => app('tenant')->id];
}

// database/factories/LocationFactory.php
public function definition(): array {
  return [
    'client_id' => null, // set via ->for($client)
    'address' => fake()->streetAddress(),
    'lat' => fake()->latitude(), 'lng' => fake()->longitude(),
  ];
}

// database/factories/DeliveryTruckFactory.php
public function definition(): array {
  return [
    'tenant_id' => app('tenant')->id,
    'plate_no' => strtoupper(fake()->bothify('TRK-###')),
    'tank_capacity_l' => fake()->numberBetween(10000, 30000),
    'active' => true,
  ];
}

// database/factories/OrderFactory.php
public function definition(): array {
  return [
    'tenant_id' => app('tenant')->id,
    'client_id' => null, 'location_id' => null,
    'created_by' => null, 'driver_id' => null, 'truck_id' => null,
    'fuel_liters' => fake()->numberBetween(1000, 9000),
    'status' => 'DRAFT',
    'window_start' => now()->addDay(), 'window_end' => now()->addDay()->addHours(2),
  ];
}
```

### Login credentials (per tenant)
- **Admin**: `admin@acme.test` / `password`
- **Dispatcher**: `dispatcher@acme.test` / `password`
- **Driver**: `driver@acme.test` / `password`
- **Client Rep**: `clientrep@acme.test` / `password`
(Replace `acme` with `globex` for the second tenant.)

---

## 10) Implemented API Endpoints

### Authentication
```
POST   /api/{tenant}/auth/login       # Login and get token
POST   /api/{tenant}/auth/logout      # Logout (requires auth)
GET    /api/{tenant}/auth/me          # Get current user (requires auth)
```

### Clients (all require auth:sanctum)
```
GET    /api/{tenant}/clients          # List clients (with search)
POST   /api/{tenant}/clients          # Create client
GET    /api/{tenant}/clients/{id}     # Get client with locations
PUT    /api/{tenant}/clients/{id}     # Update client
DELETE /api/{tenant}/clients/{id}     # Delete client (if no locations)
```

### Locations (all require auth:sanctum)
```
GET    /api/{tenant}/locations        # List locations (filter by client_id)
POST   /api/{tenant}/locations        # Create location
GET    /api/{tenant}/locations/{id}   # Get location with client
PUT    /api/{tenant}/locations/{id}   # Update location
DELETE /api/{tenant}/locations/{id}   # Delete location (if no orders)
```

### Trucks (all require auth:sanctum)
```
GET    /api/{tenant}/trucks           # List trucks (filter by active_only)
POST   /api/{tenant}/trucks           # Create truck
GET    /api/{tenant}/trucks/{id}      # Get truck
PUT    /api/{tenant}/trucks/{id}      # Update truck
DELETE /api/{tenant}/trucks/{id}      # Delete truck (if no orders)
POST   /api/{tenant}/trucks/{id}/toggle-active  # Toggle active status
```

### Orders CRUD (all require auth:sanctum)
```
GET    /api/{tenant}/orders           # List orders (filter by status, client_id)
POST   /api/{tenant}/orders           # Create order (DRAFT status)
GET    /api/{tenant}/orders/{id}      # Get order with all relations
PUT    /api/{tenant}/orders/{id}      # Update order (DRAFT only)
DELETE /api/{tenant}/orders/{id}      # Delete order (DRAFT only)
```

### Order Lifecycle Actions (all require auth:sanctum)
```
POST   /api/{tenant}/orders/{id}/submit    # DRAFT → SUBMITTED
POST   /api/{tenant}/orders/{id}/schedule  # SUBMITTED → SCHEDULED (requires truck_id)
POST   /api/{tenant}/orders/{id}/dispatch  # SCHEDULED → EN_ROUTE (requires driver_id)
POST   /api/{tenant}/orders/{id}/deliver   # EN_ROUTE → DELIVERED (requires delivered_liters)
POST   /api/{tenant}/orders/{id}/cancel    # Any → CANCELLED (optional reason)
```

**Order Status Flow**:
```
DRAFT → submit() → SUBMITTED → schedule(truck_id) → SCHEDULED
  → dispatch(driver_id) → EN_ROUTE → deliver(delivered_liters) → DELIVERED

Any status (except DELIVERED) → cancel(reason?) → CANCELLED
```

**Business Rules**:
- Only DRAFT orders can be updated/deleted
- Schedule validates truck capacity and availability
- Dispatch requires truck to be assigned
- Deliver validates delivered amount (max 110% of ordered)
- Delivered orders cannot be cancelled

---

## 11) Minimal acceptance (MVP)
- SPA served at `/{tenant}/login`; all SPA routes prefixed by slug.
- API under `/api/{tenant}/...` enforces `tenant_id` via global scope.
- Seed creates **multiple tenants** with complete demo data.
- Can CRUD **Clients**, **Locations**, **Trucks**, **Orders**.
- Order actions: submit → schedule → dispatch → deliver (requires `delivered_liters`).  
- Overlap and capacity checks enforced on schedule.  
- Cross-tenant data is not visible (403/404).

---

## 11) What to demo
1. `http://localhost:8000/acme/login` and `http://localhost:8000/globex/login`.  
2. Show different datasets per tenant (lists differ).  
3. Move an `acme` order through the lifecycle; verify `globex` does not see it.  
4. Prove API isolation by hitting `/api/globex/orders/{acmeOrder}` → 404/403.

Ready for the exam: lean, single-repo Laravel, default resources frontend, slug tenancy, **multi-tenant seeds included**. ✅
