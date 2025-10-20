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

## Code Quality & Testing

### Git Hooks Integration

The project includes automated Git hooks to ensure code quality:

#### Pre-Commit Hook
**Runs before every commit**
- PHP syntax validation using `php -l`
- Code style checking/fixing with Laravel Pint
- Debug statement detection (`dd()`, `dump()`, `var_dump()`, etc.)
- Merge conflict marker detection
- TODO/FIXME comment warnings

**Files Checked**: All staged `.php` files

#### Pre-Push Hook
**Runs before every push**
- PHPUnit test suite execution (parallel mode)
- Sensitive data detection (API keys, passwords, tokens)
- Large file detection (>1MB warning)
- Working directory status check

**Can be skipped**: `SKIP_TESTS=1 git push`

#### Commit-Msg Hook
**Validates commit messages**
- Minimum/maximum length validation (10-72 characters)
- Conventional Commits format support
- Imperative mood checking
- Proper capitalization validation

**Supported Formats**:
```
# Conventional Commits (recommended)
feat(orders): add order cancellation feature
fix(auth): resolve token expiration issue
docs(readme): update installation instructions

# Simple Format
Add order cancellation feature
Fix authentication bug
```

**Installation**:
```bash
make hooks-install      # Install all hooks
make hooks-uninstall    # Remove hooks
make hooks-info         # Check status
```

**Documentation**: See `.githooks/README.md` for complete documentation.

---

### Code Style (Laravel Pint)

The project uses Laravel Pint for consistent code style:

```bash
make pint              # Fix code style issues
make pint-test         # Check without fixing
```

**Configuration**: Uses Laravel preset with PSR-12 standards

**Automated**: Pre-commit hook automatically runs and fixes code style

---

### Testing Strategy

#### Unit Tests (`tests/Unit/`)
- Service layer business logic
- Repository query methods
- Model relationships and scopes
- Utility functions
- Validation rules

#### Feature Tests (`tests/Feature/`)
- API endpoint integration
- Multi-tenant isolation
- Order lifecycle workflows
- Authentication/Authorization
- Database constraints

#### Test Coverage Goals
- Minimum 80% code coverage
- 100% coverage for critical paths (orders, auth, tenancy)
- All public methods tested
- Edge cases documented and tested

#### Running Tests
```bash
make test              # Run all tests
make test-coverage     # With coverage report
docker compose exec php php artisan test --parallel
```

**Example Test**:
```php
public function test_user_cannot_access_other_tenant_orders()
{
    $tenant1 = Tenant::factory()->create(['slug' => 'acme']);
    $tenant2 = Tenant::factory()->create(['slug' => 'globex']);

    $user = User::factory()->for($tenant1)->create();
    $order = Order::factory()->for($tenant2)->create();

    $response = $this->actingAs($user)
        ->getJson("/api/acme/orders/{$order->id}")
        ->assertNotFound();
}
```

---

## Seeded Demo Data

The project includes comprehensive seed data for development and testing:

### Tenants
- **Acme Fuel** (`acme`) - Primary demo tenant with diverse clients
- **Globex Energy** (`globex`) - Secondary tenant with simpler setup

### ACME Tenant Data

#### Users (4 per tenant)
- `admin@acme.test` - Full system access
- `dispatcher@acme.test` - Order and fleet management
- `driver@acme.test` - View and deliver assigned orders
- `clientrep@acme.test` - Client-specific order viewing

#### Clients (7 total)
1. **North Site** (Original demo client)
2. **South Site** (Original demo client)
3. **BuildCo Construction** - Construction industry
4. **GreenFields Agriculture** - Agricultural operations
5. **RockSolid Mining Co** - Mining operations
6. **FastTrack Logistics** - Logistics & warehousing
7. **Metro Transport Services** - Metro transportation

#### Locations (10 total)
- Multiple locations per major client
- Realistic addresses with geocoding
- Distributed across service areas

#### Delivery Trucks (2)
- ACME100 - 20,000L capacity
- ACME200 - 30,000L capacity

#### Orders (7 with various statuses)
- 2 SUBMITTED - Awaiting scheduling
- 2 SCHEDULED - Truck assigned
- 1 EN_ROUTE - Driver dispatched
- 3 DELIVERED - Completed deliveries

### Seeder Location
`database/seeders/DemoMultiTenantSeeder.php`

**Run Seeds**:
```bash
make seed              # Seed database
make fresh             # Fresh install with seeds
```

---

## Frontend Architecture

### Technology Stack
- **React 18** - UI library with hooks
- **TypeScript 5** - Type-safe JavaScript
- **Vite 5** - Fast build tool and dev server
- **Tailwind CSS 3** - Utility-first styling
- **React Router 6** - Client-side routing
- **Recharts 2** - Data visualization
- **date-fns 3** - Date manipulation

### Directory Structure
```
resources/js/
├── app.tsx              # React entry point
├── router.tsx           # Route configuration
├── components/          # Reusable UI components
│   ├── Layout/
│   │   ├── Header.tsx
│   │   ├── Sidebar.tsx
│   │   └── Layout.tsx
│   ├── Orders/
│   │   ├── OrderCard.tsx
│   │   ├── OrderList.tsx
│   │   └── OrderStatusBadge.tsx
│   └── Shared/
│       ├── Button.tsx
│       ├── Input.tsx
│       └── Modal.tsx
├── lib/                 # Utilities and hooks
│   ├── api.ts          # Axios client with interceptors
│   ├── useTenant.ts    # Tenant context hook
│   ├── useAuth.ts      # Authentication hook
│   └── utils.ts        # Helper functions
├── pages/              # Page components
│   ├── Login.tsx
│   ├── Dashboard.tsx
│   ├── Orders/
│   │   ├── OrderIndex.tsx
│   │   ├── OrderCreate.tsx
│   │   └── OrderDetails.tsx
│   ├── Clients/
│   ├── Locations/
│   └── Trucks/
└── types/              # TypeScript type definitions
    ├── api.ts
    ├── models.ts
    └── index.ts
```

### Key Frontend Features

#### Tenant-Aware Routing
```typescript
// All routes include tenant slug
<Route path="/:tenant/dashboard" element={<Dashboard />} />
<Route path="/:tenant/orders" element={<OrderIndex />} />

// Extract tenant from URL
const { tenant } = useParams();
```

#### API Client
```typescript
// Configured with tenant context
const api = axios.create({
  baseURL: `/api/${tenant}`,
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
```

#### State Management
- React Context for auth and tenant
- React Query for server state (optional enhancement)
- Local state with useState/useReducer

#### Hot Module Replacement (HMR)
```bash
make dev              # Start Vite dev server
# Instant feedback on code changes
# No page refresh needed
```

---

## Infrastructure & Docker

### Container Architecture

```
┌─────────────────────────────────────────────────────┐
│                   Docker Host                        │
│                                                      │
│  ┌─────────────┐  ┌─────────────┐  ┌────────────┐ │
│  │   Nginx     │  │   PHP-FPM   │  │    Node    │ │
│  │  (Port 8000)│→ │  (Laravel)  │  │  (Vite)    │ │
│  └─────────────┘  └─────────────┘  └────────────┘ │
│         ↓                ↓                 ↓        │
│  ┌─────────────┐  ┌─────────────┐  ┌────────────┐ │
│  │   Volume    │  │    MySQL    │  │   Redis    │ │
│  │  (Storage)  │  │  (Port 3306)│  │ (Port 6379)│ │
│  └─────────────┘  └─────────────┘  └────────────┘ │
└─────────────────────────────────────────────────────┘
```

### Container Details

#### Nginx Container
- **Purpose**: Web server and reverse proxy
- **Port**: 8000 (host) → 80 (container)
- **Config**: `docker/nginx/default.conf`
- **Features**:
  - Serves static assets
  - Proxies PHP requests to PHP-FPM
  - SPA routing support (fallback to index.php)

#### PHP-FPM Container
- **Purpose**: Laravel application runtime
- **Image**: Custom PHP 8.2 with extensions
- **Extensions**: PDO, MySQL, Redis, GD, Zip, BCMath
- **Volume**: `./:/var/www/html`
- **Features**:
  - Composer installed
  - Opcache enabled (production)
  - Xdebug available (development)

#### MySQL Container
- **Purpose**: Primary database
- **Version**: 8.0
- **Port**: 3306 (host) → 3306 (container)
- **Volume**: `mysql_data` (persistent)
- **Config**:
  - Database: `manage_petro`
  - User: `mp`
  - Password: `mp`

#### Redis Container
- **Purpose**: Cache and session storage
- **Version**: 7-alpine
- **Port**: 6379
- **Use Cases**:
  - Session storage
  - Cache driver
  - Queue driver (optional)

#### Node Container
- **Purpose**: Frontend build tools
- **Version**: 20-alpine
- **Features**:
  - Vite dev server (port 5173)
  - Hot module replacement
  - Production builds

### Volume Management

```bash
# List volumes
docker volume ls

# Inspect volume
docker volume inspect manage-petro_mysql_data

# Clean volumes (WARNING: deletes data)
docker compose down -v
```

### Environment Configuration

**Development** (`.env`):
```env
APP_ENV=local
APP_DEBUG=true
VITE_DEV_SERVER_URL=http://localhost:5173

DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=manage_petro

REDIS_HOST=redis
```

**Docker Network**:
- All containers on same network: `manage-petro_default`
- Internal DNS resolution (e.g., `mysql`, `redis`)
- Isolated from host network

---

## Performance Considerations

### Database Optimization

#### Indexes
```sql
-- Tenant + status queries (most common)
INDEX idx_orders_tenant_status (tenant_id, status)

-- Client filtering
INDEX idx_clients_tenant (tenant_id)

-- Truck availability
INDEX idx_trucks_tenant_active (tenant_id, active)
```

#### Query Optimization
- Eager loading relationships (`with()`)
- Select only needed columns
- Pagination for large datasets
- Composite foreign key indexes

### Caching Strategy

#### Cache Drivers
- **Development**: File cache
- **Production**: Redis cache

#### Cached Data
- Configuration (`config:cache`)
- Routes (`route:cache`)
- Views (`view:cache`)
- User sessions (Redis)

#### Cache Keys
```php
// Tenant-scoped cache keys
$cacheKey = "tenant.{$tenantId}.clients.all";
Cache::remember($cacheKey, 3600, fn() => $this->repository->getAll());
```

### Frontend Performance

#### Code Splitting
```typescript
// Lazy load pages
const OrderIndex = lazy(() => import('./pages/Orders/OrderIndex'));
const OrderDetails = lazy(() => import('./pages/Orders/OrderDetails'));
```

#### Asset Optimization
- Vite automatic code splitting
- Tree shaking for unused code
- CSS purging with Tailwind
- Image optimization

#### Build Process
```bash
make build              # Production build
# Output: public/build/
# Includes: Minification, chunking, hashing
```

---

## Security Considerations

### Authentication
- Laravel Sanctum for API tokens
- Token-based authentication
- Secure password hashing (bcrypt)
- Token expiration configured

### Authorization
- Policy-based access control
- Role-based permissions (ADMIN, DISPATCHER, DRIVER, CLIENT_REP)
- Gate checks in controllers
- Middleware protection on routes

### Tenant Isolation
- Global query scopes
- Composite foreign key constraints
- Middleware validation
- 404 for cross-tenant access attempts

### Input Validation
- Form Request classes
- Type-safe validation rules
- SQL injection prevention (Eloquent ORM)
- XSS protection (React escaping)

### Environment Security
- `.env` file gitignored
- Secrets not in version control
- Environment-specific configuration
- Production debug mode disabled

---

## Development Tools

### Makefile Commands

The project includes 30+ make commands organized by category:

#### Setup
- `make install` - Complete first-time setup
- `make setup` - Dependencies only
- `make fresh` - Fresh database

#### Development
- `make dev` - Start Vite dev server
- `make logs` - View all logs
- `make tinker` - Laravel Tinker REPL
- `make shell-php` - PHP container shell

#### Database
- `make migrate` - Run migrations
- `make seed` - Seed database
- `make db-shell` - MySQL shell

#### Testing & Quality
- `make test` - Run tests
- `make pint` - Fix code style
- `make hooks-install` - Install Git hooks

See `make help` for complete list.

---

## Monitoring & Logging

### Laravel Logs
- **Location**: `storage/logs/laravel.log`
- **Driver**: Daily rotation
- **Channels**: Stack (multiple outputs)

**View Logs**:
```bash
make logs-php           # Container logs
docker compose exec php tail -f storage/logs/laravel.log
```

### Error Tracking
- **Development**: Detailed error pages
- **Production**: Generic error pages + logging
- **Recommended**: Sentry integration for production

### Activity Logging
- Module: `app/Modules/ActivityLog/`
- Tracks: User actions, order changes, system events
- Storage: `activity_logs` table

---

## Future Enhancements

### Planned Features
- ✅ Policy-based authorization (Completed)
- 🔄 Event/Listener architecture
- 🔄 Job queues for notifications
- 🔄 Enhanced audit logging
- 🔄 Real-time updates (WebSockets)
- 🔄 Mobile driver app
- 🔄 GPS tracking integration
- 🔄 Reporting/Analytics module
- 🔄 Multi-language support (i18n)
- 🔄 Advanced scheduling algorithms
- 🔄 Predictive fuel demand
- 🔄 Route optimization

### Technical Improvements
- React Query for server state
- GraphQL API option
- CI/CD pipeline (GitHub Actions)
- Docker production optimization
- Kubernetes deployment configs
- Automated database backups
- Performance monitoring (New Relic)
- CDN integration for assets

---

## Troubleshooting

### Common Issues

#### Containers won't start
```bash
# Check Docker daemon
docker ps

# View logs
make logs

# Rebuild containers
docker compose down
docker compose up -d --build
```

#### Database connection errors
```bash
# Check MySQL container
docker compose ps mysql

# Test connection
docker compose exec php php artisan tinker
>>> DB::connection()->getPdo();
```

#### Vite not hot reloading
```bash
# Ensure dev server is running
make dev

# Check .env
VITE_DEV_SERVER_URL=http://localhost:5173

# Clear cache
make cache-clear
docker compose restart php
```

#### Permission errors
```bash
# Fix storage permissions
docker compose exec php chmod -R 775 storage bootstrap/cache
docker compose exec php chown -R www-data:www-data storage bootstrap/cache
```

#### Git hooks not running
```bash
# Check if installed
ls -l .git/hooks/

# Reinstall
make hooks-install

# Check permissions
chmod +x .git/hooks/pre-commit
chmod +x .git/hooks/pre-push
```

---

## Additional Resources

- **Main README**: [`README.md`](README.md) - Quick start and overview
- **Git Hooks**: [`.githooks/README.md`](.githooks/README.md) - Hooks documentation
- **Database Diagrams**: [`docs/`](docs/) - ERD, relationships, status flow
- **Laravel Docs**: https://laravel.com/docs
- **React Docs**: https://react.dev
- **Docker Docs**: https://docs.docker.com
