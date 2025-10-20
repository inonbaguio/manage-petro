l# User Roles and Permissions Analysis

## Overview

This document provides a comprehensive analysis of the roles and permissions system in the Manage Petro application. The system implements a policy-based Role-Based Access Control (RBAC) using Laravel's built-in authorization system.

---

## 1. USER ROLES DEFINED IN THE SYSTEM

The application uses four primary roles defined as an **ENUM in the database schema**:

**File**: `database/migrations/0000_01_01_000001_create_users_table.php`

```sql
ENUM role VALUES: ['ADMIN', 'DISPATCHER', 'DRIVER', 'CLIENT_REP']
DEFAULT: 'CLIENT_REP'
```

### Role Descriptions

| Role | Description | Access Level |
|------|-------------|--------------|
| **ADMIN** | System administrator with full system access | Highest |
| **DISPATCHER** | Operations manager handling order scheduling and truck management | High |
| **DRIVER** | Delivery personnel executing orders | Medium |
| **CLIENT_REP** | Client representative viewing their client's data | Low |

---

## 2. POLICY-BASED AUTHORIZATION (Laravel Policies)

The application implements **Laravel's Policy pattern** for role-based access control (RBAC). All authorization is handled through policies mapped in the `AuthServiceProvider`.

**File**: `app/Providers/AuthServiceProvider.php`

```php
protected $policies = [
    Order::class => OrderPolicy::class,
    Client::class => ClientPolicy::class,
    Location::class => LocationPolicy::class,
    DeliveryTruck::class => TruckPolicy::class,
];
```

### 2.1 ORDER POLICY

**File**: `app/Policies/OrderPolicy.php`

| Policy Method | Admin | Dispatcher | Driver | Client Rep | Notes |
|---|---|---|---|---|---|
| **viewAny()** | ✓ | ✓ | ✓ | ✓ | All authenticated roles can view orders list |
| **view()** | ✓ | ✓ | Own orders only | TODO | ADMIN/DISPATCHER view all; DRIVER views assigned orders |
| **create()** | ✓ | ✓ | ✗ | ✗ | Only ADMIN and DISPATCHER can create orders |
| **update()** | ✓ | ✓ | ✗ | ✗ | Only ADMIN/DISPATCHER can update orders |
| **delete()** | ✓ | ✓ | ✗ | ✗ | Only ADMIN/DISPATCHER can delete (DRAFT status enforced in service) |
| **submit()** | ✓ | ✓ | ✗ | ✗ | Transition from DRAFT → SUBMITTED |
| **schedule()** | ✓ | ✓ | ✗ | ✗ | Assign truck to order (SUBMITTED → SCHEDULED) |
| **dispatch()** | ✓ | ✓ | ✗ | ✗ | Assign driver to order (SCHEDULED → EN_ROUTE) |
| **deliver()** | ✓ | Own orders only | ✓ | ✗ | ADMIN can deliver any; DRIVER delivers assigned orders |
| **cancel()** | ✓ | ✓ | ✗ | ✗ | Only ADMIN/DISPATCHER can cancel |

### 2.2 CLIENT POLICY

**File**: `app/Policies/ClientPolicy.php`

| Policy Method | Admin | Dispatcher | Driver | Client Rep | Notes |
|---|---|---|---|---|---|
| **viewAny()** | ✓ | ✓ | ✓ | ✓ | All authenticated users can view clients |
| **view()** | ✓ | ✓ | ✗ | TODO | ADMIN/DISPATCHER view all; CLIENT_REP view own client (requires client_id on users table) |
| **create()** | ✓ | ✓ | ✗ | ✗ | Only ADMIN and DISPATCHER |
| **update()** | ✓ | ✓ | ✗ | ✗ | Only ADMIN and DISPATCHER |
| **delete()** | ✓ | ✗ | ✗ | ✗ | Only ADMIN can delete clients |

### 2.3 LOCATION POLICY

**File**: `app/Policies/LocationPolicy.php`

| Policy Method | Admin | Dispatcher | Driver | Client Rep | Notes |
|---|---|---|---|---|---|
| **viewAny()** | ✓ | ✓ | ✓ | ✓ | All authenticated users can view locations |
| **view()** | ✓ | ✓ | ✗ | TODO | ADMIN/DISPATCHER view all; CLIENT_REP view client's locations |
| **create()** | ✓ | ✓ | ✗ | ✗ | Only ADMIN and DISPATCHER |
| **update()** | ✓ | ✓ | ✗ | ✗ | Only ADMIN and DISPATCHER |
| **delete()** | ✓ | ✓ | ✗ | ✗ | Only ADMIN and DISPATCHER |

### 2.4 TRUCK POLICY

**File**: `app/Policies/TruckPolicy.php`

| Policy Method | Admin | Dispatcher | Driver | Client Rep | Notes |
|---|---|---|---|---|---|
| **viewAny()** | ✓ | ✓ | ✓ | ✓ | All authenticated users can view trucks |
| **view()** | ✓ | ✓ | ✓ | ✓ | All authenticated users can view individual trucks |
| **create()** | ✓ | ✗ | ✗ | ✗ | Only ADMIN can create trucks |
| **update()** | ✓ | ✓ | ✗ | ✗ | Only ADMIN and DISPATCHER can update |
| **delete()** | ✓ | ✗ | ✗ | ✗ | Only ADMIN can delete trucks |

---

## 3. POLICY IMPLEMENTATION IN CONTROLLERS

Policies are enforced using Laravel's `authorize()` helper in controllers.

**File**: `app/Modules/Orders/Http/Controllers/OrderController.php`

```php
// List orders - check if user can view any
public function index(Request $request): JsonResponse
{
    $this->authorize('viewAny', \App\Modules\Orders\Domain\Models\Order::class);
    // ...
}

// Create order - check if user can create
public function store(StoreOrderRequest $request): JsonResponse
{
    $this->authorize('create', \App\Modules\Orders\Domain\Models\Order::class);
    // ...
}

// View specific order
public function show(string $tenant, string $id): JsonResponse
{
    $this->authorize('view', $order);
    // ...
}

// Update order
public function update(UpdateOrderRequest $request, string $tenant, string $id): JsonResponse
{
    $this->authorize('update', $order);
    // ...
}

// Delete order
public function destroy(string $tenant, string $id): JsonResponse
{
    $this->authorize('delete', $order);
    // ...
}
```

**File**: `app/Modules/Orders/Http/Controllers/OrderActionsController.php`

```php
// Order action-specific authorization
$this->authorize('submit', $order);
$this->authorize('schedule', $order);
$this->authorize('dispatch', $order);
$this->authorize('deliver', $order);
$this->authorize('cancel', $order);
```

---

## 4. DATABASE STRUCTURE FOR ROLES AND USERS

### Users Table Migration

**File**: `database/migrations/0000_01_01_000001_create_users_table.php`

```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT FOREIGN KEY,
    name VARCHAR(255),
    email VARCHAR(255),
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255),
    role ENUM('ADMIN', 'DISPATCHER', 'DRIVER', 'CLIENT_REP') DEFAULT 'CLIENT_REP',
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE(email, tenant_id),     -- Email unique per tenant
    UNIQUE(id, tenant_id),         -- Composite FK support
    INDEX(tenant_id)
);
```

### User Model

**File**: `app/Models/User.php`

```php
class User extends Authenticatable
{
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',  // No casting - stored as string
    ];
}
```

---

## 5. ROLE ASSIGNMENT DURING SEEDING

Roles are assigned during database seeding via the `DemoMultiTenantSeeder`.

**File**: `database/seeders/DemoMultiTenantSeeder.php`

```php
// ADMIN user
$admin = User::firstOrCreate(
    ['email' => "admin@{$tenant->slug}.test"],
    [
        'name' => 'Admin User',
        'password' => Hash::make('password'),
        'tenant_id' => $tenant->id,
        'role' => 'ADMIN'
    ]
);

// DISPATCHER user
$dispatcher = User::firstOrCreate(
    ['email' => "dispatcher@{$tenant->slug}.test"],
    ['role' => 'DISPATCHER', ...]
);

// DRIVER user
$driver = User::firstOrCreate(
    ['email' => "driver@{$tenant->slug}.test"],
    ['role' => 'DRIVER', ...]
);

// CLIENT_REP user
$clientRep = User::firstOrCreate(
    ['email' => "clientrep@{$tenant->slug}.test"],
    ['role' => 'CLIENT_REP', ...]
);
```

---

## 6. AUTHORIZATION IN FORM REQUESTS

While form requests have `authorize()` methods, they currently return `true` (bypassed) with TODOs to implement policy-based authorization.

**File**: `app/Modules/Shared/Http/BaseFormRequest.php`

```php
public function authorize(): bool
{
    return true;  // Form-level authorization disabled
}
```

Individual request classes:

**File**: `app/Modules/Clients/Http/Requests/StoreClientRequest.php`

```php
public function authorize(): bool
{
    return true; // Add policy-based authorization later (TODO)
}
```

---

## 7. MANUAL AUTHORIZATION CHECKS

Some controllers implement manual role checks directly:

**File**: `app/Modules/ActivityLog/Http/Controllers/ActivityLogController.php`

```php
// ADMIN-only access (manual role check)
public function index(Request $request): JsonResponse
{
    // Authorization check - only ADMIN can view activity logs
    if ($request->user()->role !== 'ADMIN') {
        return ApiResponse::error('Unauthorized', 403);
    }
    // ...
}

public function getByModel(Request $request, string $tenant, string $modelType, string $modelId): JsonResponse
{
    if ($request->user()->role !== 'ADMIN') {
        return ApiResponse::error('Unauthorized', 403);
    }
    // ...
}
```

---

## 8. HOW ROLES INTERACT WITH ENTITIES

### Order Lifecycle with Role-Based Access

| User Role    | Can Create | Can Submit | Can Schedule | Can Dispatch | Can Deliver | Can Cancel |
|--------------|------------|------------|--------------|--------------|-------------|------------|
| ADMIN        | ✓          | ✓          | ✓            | ✓            | ✓ (any)     | ✓          |
| DISPATCHER   | ✓          | ✓          | ✓            | ✓            | ✗           | ✓          |
| DRIVER       | ✗          | ✗          | ✗            | ✗            | ✓ (own)     | ✗          |
| CLIENT_REP   | ✗          | ✗          | ✗            | ✗            | ✗           | ✗          |

### Client Management

- **View**: ADMIN/DISPATCHER can view all; DRIVER cannot view; CLIENT_REP view own (TODO implementation)
- **Create/Update**: ADMIN/DISPATCHER only
- **Delete**: ADMIN only

### Location Management

- **View**: ADMIN/DISPATCHER view all; DRIVER cannot view; CLIENT_REP view client's (TODO)
- **Create/Update/Delete**: ADMIN/DISPATCHER only

### Truck Management

- **View**: All authenticated users
- **Create**: ADMIN only
- **Update**: ADMIN/DISPATCHER
- **Delete**: ADMIN only
- **Toggle Active**: Not explicitly protected (TODO)

---

## 9. MULTI-TENANCY INTEGRATION WITH ROLES

The application uses **slug-based multi-tenancy** with tenant isolation:

**File**: `app/Modules/Shared/Tenancy/TenantMiddleware.php`

```php
public function handle(Request $request, Closure $next): Response
{
    $slug = $request->route('tenant');
    $tenant = Tenant::where('slug', $slug)->first();

    if (!$tenant) {
        abort(404, 'Tenant not found');
    }

    // Bind tenant to container for global access
    app()->instance('tenant', $tenant);

    return $next($request);
}
```

### Key Points

- Users are scoped to tenants via `tenant_id` on users table
- Emails are unique per tenant: `UNIQUE(email, tenant_id)`
- All models use `BelongsToTenant` trait for automatic tenant scoping
- Cross-tenant access is prevented at the database level

---

## 10. DATABASE RELATIONSHIPS FOR ROLE-BASED ACCESS

### Order Creation and Assignment

```php
// Order.php model relationships
public function creator(): BelongsTo  // created_by → user
{
    return $this->belongsTo(User::class, 'created_by');
}

public function driver(): BelongsTo   // driver_id → user
{
    return $this->belongsTo(User::class, 'driver_id');
}
```

### Policy checks use these relationships

```php
// Allow DRIVER to view only their assigned orders
if ($user->role === 'DRIVER' && $order->driver_id === $user->id) {
    return true;
}

// Allow DRIVER to deliver only their assigned orders
if ($user->role === 'DRIVER' && $order->driver_id === $user->id) {
    return true;
}
```

---

## 11. TODO ITEMS AND INCOMPLETE FEATURES

Several authorization features are marked as TODO:

### 1. CLIENT_REP Permissions

**File**: `app/Policies/OrderPolicy.php` (Lines 35-37)

```php
// TODO: Add client_id to users table to enable this check
// if ($user->role === 'CLIENT_REP' && $order->client_id === $user->client_id) {
//     return true;
// }
```

### 2. Form Request Authorization

Multiple request classes have authorization disabled:

```php
return true; // Add policy-based authorization later
```

### 3. Truck Toggle Active

No authorization check in `TruckController::toggleActive`

### 4. Location and Client View for CLIENT_REP

Similar TODOs in `LocationPolicy` and `ClientPolicy`

---

## 12. AUTHENTICATION INFRASTRUCTURE

### Login Flow

**File**: `app/Modules/Auth/Http/LoginController.php`

```php
public function login(Request $request): JsonResponse
{
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return ApiResponse::error('Invalid credentials', 401);
    }

    // Create token (Laravel Sanctum)
    $token = $user->createToken('auth-token')->plainTextToken;

    return ApiResponse::success([
        'user' => $user,
        'token' => $token,
    ], 'Login successful');
}
```

### User Model uses Laravel Sanctum

```php
class User extends Authenticatable
{
    use HasApiTokens;  // For token-based API authentication
}
```

---

## 13. KEY FILES SUMMARY

| File | Purpose |
|------|---------|
| `database/migrations/0000_01_01_000001_create_users_table.php` | Defines role enum and user schema |
| `app/Providers/AuthServiceProvider.php` | Maps models to policies |
| `app/Policies/OrderPolicy.php` | Order-related authorization |
| `app/Policies/ClientPolicy.php` | Client-related authorization |
| `app/Policies/LocationPolicy.php` | Location-related authorization |
| `app/Policies/TruckPolicy.php` | Truck-related authorization |
| `app/Models/User.php` | User model with role field |
| `database/seeders/DemoMultiTenantSeeder.php` | Role seeding |
| `app/Modules/Auth/Http/LoginController.php` | Authentication endpoint |
| `app/Modules/ActivityLog/Http/Controllers/ActivityLogController.php` | Manual role checks example |
| `app/Modules/Shared/Tenancy/TenantMiddleware.php` | Tenant resolution |

---

## 14. AUTHORIZATION FLOW DIAGRAM

```
Request with Token
        ↓
[Sanctum Middleware] - Resolves authenticated user
        ↓
[TenantMiddleware] - Resolves tenant from URL
        ↓
[Controller Method]
        ↓
$this->authorize('action', $model)
        ↓
[Policy Class] - Checks user->role
        ↓
Gate allows/denies
        ↓
Response or AuthorizationException
```

---

## 15. SUMMARY AND RECOMMENDATIONS

### Current State

- Implements **Policy-based RBAC** with 4 roles
- Uses **Laravel's built-in authorization** system
- Enforces **multi-tenant data isolation** at database level
- Uses **Sanctum for token-based API authentication**
- Implements **manual role checks** in some controllers

### Gaps and TODOs

1. CLIENT_REP role is partially implemented (needs `client_id` on users table)
2. Form request authorization is disabled (set to `return true`)
3. Some truck operations lack explicit authorization checks
4. Activity Log access is only manually checked (should use policy)

### Recommendations

1. **Implement CLIENT_REP permissions** by adding `client_id` column to users table
2. **Activate form request authorization** to enforce role checks at entry point
3. **Create an ActivityLog policy** instead of manual checks
4. **Add authorization to all remaining endpoints** (e.g., truck toggleActive)
5. **Consider creating role constants** to avoid hardcoded strings in policies
6. **Implement audit logging** with activity tracking (already has ActivityLog model)

---

## 16. TEST CREDENTIALS (From Seeder)

For testing purposes, the following credentials are available for each tenant:

| Role | Email Pattern | Password |
|------|---------------|----------|
| ADMIN | `admin@{tenant}.test` | `password` |
| DISPATCHER | `dispatcher@{tenant}.test` | `password` |
| DRIVER | `driver@{tenant}.test` | `password` |
| CLIENT_REP | `clientrep@{tenant}.test` | `password` |

**Example for "acme" tenant:**
- Admin: `admin@acme.test` / `password`
- Dispatcher: `dispatcher@acme.test` / `password`
- Driver: `driver@acme.test` / `password`
- Client Rep: `clientrep@acme.test` / `password`
