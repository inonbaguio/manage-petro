# Manage Petro - Architecture Documentation

## Overview
Manage Petro is a **multi-tenant fuel delivery management system** built with Laravel (backend) and React (frontend). It follows a **modular monolith architecture** with proper separation of concerns.

---

## Architecture Layers

### 1. **Repository Layer**
Handles all database interactions and queries. Each module has its own repository.

**Location**: `app/Modules/{Module}/Repositories/`

**Responsibilities**:
- Database queries
- Data retrieval and persistence
- Query optimization
- Relationship loading

**Example**:
```php
app/Modules/Clients/Repositories/ClientRepository.php
app/Modules/Orders/Repositories/OrderRepository.php
```

**Base Repository**: `app/Modules/Shared/Repositories/BaseRepository.php`
- Provides common CRUD operations
- Pagination support
- Relationship loading helpers

---

### 2. **Service Layer**
Contains all business logic and orchestrates operations between repositories.

**Location**: `app/Modules/{Module}/Services/`

**Responsibilities**:
- Business logic implementation
- Validation beyond form requests
- Transaction management
- Orchestrating multiple repositories
- State management (e.g., order lifecycle)

**Example**:
```php
app/Modules/Orders/Services/OrderService.php
app/Modules/Trucks/Services/TruckService.php
```

**Key Features**:
- Slim controllers delegate to services
- Services handle complex business rules
- Order lifecycle management (DRAFT → SUBMITTED → SCHEDULED → EN_ROUTE → DELIVERED)
- Truck availability checks
- Client-location validation

---

### 3. **Controller Layer**
Thin controllers that handle HTTP requests/responses and delegate to services.

**Location**: `app/Modules/{Module}/Http/Controllers/`

**Responsibilities**:
- HTTP request handling
- Delegating to services
- Returning API responses
- Exception handling

**Example**:
```php
app/Modules/Clients/Http/Controllers/ClientController.php
app/Modules/Orders/Http/Controllers/OrderController.php
app/Modules/Orders/Http/Controllers/OrderActionsController.php
```

**Pattern**:
```php
public function store(StoreClientRequest $request): JsonResponse
{
    try {
        $client = $this->service->createClient($request->validated());
        return ApiResponse::success(new ClientResource($client), 'Client created', 201);
    } catch (\Exception $e) {
        return ApiResponse::error($e->getMessage(), 422);
    }
}
```

---

### 4. **Request Layer (Validation)**
Form Request classes for input validation.

**Location**: `app/Modules/{Module}/Http/Requests/`

**Responsibilities**:
- Input validation
- Authorization (policy-based)
- Data sanitization

**Example**:
```php
app/Modules/Clients/Http/Requests/StoreClientRequest.php
app/Modules/Orders/Http/Requests/ScheduleOrderRequest.php
```

---

### 5. **Resource Layer (Transformation)**
API Resources for consistent JSON responses.

**Location**: `app/Modules/{Module}/Http/Resources/`

**Responsibilities**:
- Data transformation
- Conditional field inclusion
- Relationship formatting
- ISO date formatting

**Example**:
```php
app/Modules/Clients/Http/Resources/ClientResource.php
app/Modules/Orders/Http/Resources/OrderResource.php
```

---

### 6. **Domain Layer (Models)**
Eloquent models representing database entities.

**Location**: `app/Modules/{Module}/Domain/Models/`

**Responsibilities**:
- Database representation
- Relationships
- Attribute casting
- Scopes (global tenant scope via BelongsToTenant trait)

**Example**:
```php
app/Modules/Clients/Domain/Models/Client.php
app/Modules/Orders/Domain/Models/Order.php
```

---

## Module Structure

Each module follows this structure:

```
app/Modules/{ModuleName}/
├── Domain/
│   └── Models/          # Eloquent models
├── Http/
│   ├── Controllers/     # HTTP controllers (thin)
│   ├── Requests/        # Form validation
│   └── Resources/       # JSON transformation
├── Repositories/        # Database queries
└── Services/            # Business logic
```

---

## Modules

### 1. **Shared Module**
Cross-cutting concerns used by all modules.

**Components**:
- `TenantMiddleware` - Resolves tenant from URL slug
- `BelongsToTenant` trait - Automatic tenant scoping
- `BaseRepository` - Common repository methods
- `BaseFormRequest` - Base validation class
- `ApiResponse` - Standardized JSON responses

---

### 2. **Auth Module**
User authentication and authorization.

**Components**:
- `LoginController` - Login/logout endpoints
- `MeController` - Current user info

---

### 3. **Clients Module**
Client management with locations.

**Features**:
- CRUD operations
- Search functionality
- Location count tracking
- Relationship validation

**Endpoints**:
- `GET /api/{tenant}/clients` - List clients
- `POST /api/{tenant}/clients` - Create client
- `GET /api/{tenant}/clients/{id}` - Get client
- `PUT /api/{tenant}/clients/{id}` - Update client
- `DELETE /api/{tenant}/clients/{id}` - Delete client

---

### 4. **Locations Module**
Delivery locations for clients.

**Features**:
- CRUD operations
- Client association
- Geocoding (lat/lng)
- Address search

**Endpoints**:
- `GET /api/{tenant}/locations?client_id={id}` - List locations
- `POST /api/{tenant}/locations` - Create location
- `GET /api/{tenant}/locations/{id}` - Get location
- `PUT /api/{tenant}/locations/{id}` - Update location
- `DELETE /api/{tenant}/locations/{id}` - Delete location

---

### 5. **Trucks Module**
Delivery truck management.

**Features**:
- CRUD operations
- Capacity tracking
- Active/inactive status
- Availability checking
- Unique plate numbers per tenant

**Endpoints**:
- `GET /api/{tenant}/trucks?active_only=1` - List trucks
- `POST /api/{tenant}/trucks` - Create truck
- `GET /api/{tenant}/trucks/{id}` - Get truck
- `PUT /api/{tenant}/trucks/{id}` - Update truck
- `DELETE /api/{tenant}/trucks/{id}` - Delete truck
- `POST /api/{tenant}/trucks/{id}/toggle-active` - Toggle status

---

### 6. **Orders Module**
Fuel delivery order management with full lifecycle.

**Features**:
- CRUD operations (DRAFT only)
- Full lifecycle management
- Status transitions with validation
- Truck capacity checks
- Schedule conflict detection
- Client-location validation

**Order Statuses**:
1. **DRAFT** - Initial creation
2. **SUBMITTED** - Ready for scheduling
3. **SCHEDULED** - Truck assigned
4. **EN_ROUTE** - Driver dispatched
5. **DELIVERED** - Delivery completed
6. **CANCELLED** - Order cancelled

**State Transitions**:
```
DRAFT → submit() → SUBMITTED
SUBMITTED → schedule(truck_id) → SCHEDULED
SCHEDULED → dispatch(driver_id) → EN_ROUTE
EN_ROUTE → deliver(delivered_liters) → DELIVERED
Any (except DELIVERED) → cancel(reason) → CANCELLED
```

**Endpoints**:
- `GET /api/{tenant}/orders?status={status}&client_id={id}` - List orders
- `POST /api/{tenant}/orders` - Create order (DRAFT)
- `GET /api/{tenant}/orders/{id}` - Get order
- `PUT /api/{tenant}/orders/{id}` - Update order (DRAFT only)
- `DELETE /api/{tenant}/orders/{id}` - Delete order (DRAFT only)
- `POST /api/{tenant}/orders/{id}/submit` - Submit order
- `POST /api/{tenant}/orders/{id}/schedule` - Schedule with truck
- `POST /api/{tenant}/orders/{id}/dispatch` - Dispatch with driver
- `POST /api/{tenant}/orders/{id}/deliver` - Mark as delivered
- `POST /api/{tenant}/orders/{id}/cancel` - Cancel order

---

## Multi-Tenancy

### Slug-Based Tenancy
All routes are prefixed with `{tenant}` slug:
- API: `/api/{tenant}/...`
- SPA: `/{tenant}/...`

### TenantMiddleware
Resolves tenant from URL and stores in container:
```php
$tenant = Tenant::where('slug', $slug)->firstOrFail();
app()->instance('tenant', $tenant);
```

### BelongsToTenant Trait
Automatic tenant scoping for models:
- Auto-fills `tenant_id` on creation
- Adds global scope filtering by `tenant_id`
- Ensures data isolation

### Tenant Isolation
- All database queries automatically scoped to current tenant
- Composite foreign keys enforce tenant boundaries
- Cross-tenant access returns 404/403

---

## Database Schema

### Tenants Table
```sql
- id
- name
- slug (unique)
- timestamps
```

### Users Table
```sql
- id
- tenant_id
- name
- email (unique with tenant_id)
- password
- role (ADMIN, DISPATCHER, DRIVER, CLIENT_REP)
- timestamps
```

### Clients Table
```sql
- id
- tenant_id
- name
- contact_person
- contact_phone
- contact_email
- timestamps
- UNIQUE(tenant_id, id)
```

### Locations Table
```sql
- id
- tenant_id
- client_id
- address
- lat, lng
- timestamps
- FK: (client_id, tenant_id) → clients(id, tenant_id)
```

### Delivery Trucks Table
```sql
- id
- tenant_id
- plate_no (unique with tenant_id)
- tank_capacity_l
- active
- timestamps
```

### Orders Table
```sql
- id
- tenant_id
- client_id
- location_id
- truck_id (nullable)
- created_by
- driver_id (nullable)
- fuel_liters
- status (enum)
- window_start
- window_end
- delivered_liters (nullable)
- delivered_at (nullable)
- cancellation_reason (nullable)
- timestamps
- Composite FKs to enforce tenant boundaries
```

---

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

---

## Testing Strategy

### Unit Tests
- Service layer logic
- Repository queries
- Model relationships
- Validation rules

### Feature Tests
- API endpoint testing
- Tenant isolation
- Order lifecycle
- Authorization

**Example**:
```php
public function test_creates_order_scoped_to_tenant()
{
    $tenant = Tenant::factory()->create(['slug' => 'acme']);
    $user = User::factory()->for($tenant)->create();

    $response = $this->actingAs($user)
        ->postJson("/api/acme/orders", $payload)
        ->assertCreated()
        ->assertJsonPath('data.tenant_id', $tenant->id);
}
```

---

## Key Design Decisions

1. **Modular Monolith** - Easier to develop/deploy than microservices while maintaining modularity
2. **Repository Pattern** - Abstracts data access, easier to test and swap implementations
3. **Service Layer** - Keeps controllers thin, centralizes business logic
4. **Slug-based Tenancy** - Simple, no subdomain configuration needed
5. **Global Scopes** - Automatic tenant filtering, prevents data leaks
6. **Composite FKs** - Database-level tenant isolation enforcement
7. **Status Enum** - Type-safe order lifecycle management
8. **API Resources** - Consistent JSON formatting across all endpoints

---

## Development Workflow

1. **Create Migration** - Database schema
2. **Create Model** - Eloquent representation
3. **Create Factory** - Test data generation
4. **Create Repository** - Database queries
5. **Create Service** - Business logic
6. **Create Requests** - Validation rules
7. **Create Resource** - JSON transformation
8. **Create Controller** - HTTP handling
9. **Register Routes** - API endpoints
10. **Write Tests** - Feature/Unit tests

---

## Future Enhancements

- Policy-based authorization
- Event/Listener architecture
- Job queues for notifications
- Audit logging
- Real-time updates (WebSockets)
- Mobile driver app
- GPS tracking integration
- Reporting/Analytics module
