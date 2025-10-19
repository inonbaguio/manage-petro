# Manage Petro — Multi-Tenant Fuel Delivery Management MVP

A complete multi-tenant Laravel + React application for managing fuel delivery operations with slug-based tenancy, modular architecture, and composite foreign key constraints for database-level tenant isolation.

## Architecture

- **Backend**: Laravel 11 with modular structure under `app/Modules/`
- **Frontend**: React SPA with React Router (Vite)
- **Tenancy**: Slug-in-path (`/{tenant}/...`) for both SPA and API
- **Database**: MySQL 8 with composite foreign keys enforcing tenant isolation
- **Infrastructure**: Docker (Nginx, PHP-FPM, MySQL, Redis, Node/Vite)

## Features Implemented

✅ Multi-tenant architecture with slug-based routing
✅ Database-level tenant isolation via composite foreign keys
✅ Auth module with Sanctum (login/logout/me)
✅ Models: Tenant, User, Client, Location, DeliveryTruck, Order
✅ Two demo tenants: `acme` and `globex` with seed data
✅ React SPA with tenant-aware routing
✅ API authentication with bearer tokens

## Quick Start

### Option 1: Using Makefile (Recommended)

**Complete installation:**
```bash
make install
```

That's it! The command will:
- Copy `.env.example` to `.env`
- Start Docker containers
- Install backend & frontend dependencies
- Generate application key
- Run migrations and seed database with demo data

**View all available commands:**
```bash
make help
```

### Option 2: Manual Setup

**1. Copy Environment File:**
```bash
cp .env.example .env
```

**2. Start Docker Containers:**
```bash
docker compose up -d --build
```

**3. Install Dependencies:**

Backend:
```bash
docker compose exec php composer install
docker compose exec php php artisan key:generate
```

Frontend:
```bash
docker compose exec node npm install
```

**4. Run Migrations & Seed Database:**
```bash
docker compose exec php php artisan migrate --seed
```

This creates two tenants (`acme` and `globex`) with:
- 4 users per tenant (admin, dispatcher, driver, client rep)
- 2 clients with locations
- 2 delivery trucks
- 4 orders in different statuses

### 5. Access the Application

- **Acme Tenant**: http://localhost:8000/acme/login
- **Globex Tenant**: http://localhost:8000/globex/login

## Test Credentials

For each tenant (replace `{tenant}` with `acme` or `globex`):

- **Admin**: `admin@{tenant}.test` / `password`
- **Dispatcher**: `dispatcher@{tenant}.test` / `password`
- **Driver**: `driver@{tenant}.test` / `password`
- **Client Rep**: `clientrep@{tenant}.test` / `password`

## Makefile Commands

The project includes a comprehensive Makefile for easy development. Here are the most useful commands:

### Setup & Installation
```bash
make install          # Complete first-time setup
make setup            # Install dependencies only
make fresh            # Drop database, migrate, and seed
```

### Docker Management
```bash
make up               # Start containers
make down             # Stop containers
make restart          # Restart containers
make ps               # Show running containers
```

### Database
```bash
make migrate          # Run migrations
make seed             # Seed database
make migrate-seed     # Migrate and seed
make db-shell         # Open MySQL shell
```

### Development
```bash
make logs             # Show all logs
make logs-php         # PHP/Laravel logs only
make logs-nginx       # Nginx logs only
make logs-node        # Vite logs only
make shell-php        # Access PHP container
make tinker           # Open Laravel Tinker
```

### Frontend
```bash
make dev              # Start Vite dev server
make build            # Build for production
make npm CMD="..."    # Run npm command
```

### Backend
```bash
make artisan CMD="..."    # Run artisan command
make composer CMD="..."   # Run composer command
make routes               # List all routes
make cache-clear          # Clear all caches
```

### Testing
```bash
make test             # Run tests
make test-coverage    # Run tests with coverage
```

### Quick Access
```bash
make open             # Open app in browser (Acme)
make open-acme        # Open Acme tenant
make open-globex      # Open Globex tenant
```

### View All Commands
```bash
make help             # Display all available commands
```

## Database Structure

### Composite Foreign Keys

The system uses composite foreign keys to enforce tenant isolation at the database level:

```sql
-- Example: Orders table
FOREIGN KEY (client_id, tenant_id)
  REFERENCES clients(id, tenant_id)
  ON DELETE RESTRICT

FOREIGN KEY (location_id, tenant_id)
  REFERENCES locations(id, tenant_id)
  ON DELETE RESTRICT
```

This prevents cross-tenant data references even with malicious queries.

### Migrations
1. `tenants` - Base tenant table
2. `users` - With tenant FK and role enum
3. `clients` - Business clients
4. `delivery_trucks` - Fleet management
5. `locations` - Client delivery locations (composite FK to clients)
6. `orders` - Delivery orders (multiple composite FKs)

## API Endpoints

All API routes are prefixed with `/api/{tenant}/`

### Authentication
- `POST /api/{tenant}/auth/login` - Login (email, password)
- `POST /api/{tenant}/auth/logout` - Logout (requires auth)
- `GET /api/{tenant}/auth/me` - Get current user (requires auth)

### Resources (Coming Soon)
- Clients, Locations, Trucks, Orders CRUD endpoints
- Order lifecycle actions (submit/schedule/dispatch/deliver/cancel)

## Development

The Makefile provides all necessary development commands. Here are some common workflows:

### Running Tests
```bash
make test              # Run all tests
make test-coverage     # With coverage report
```

### Database Access
```bash
make db-shell         # MySQL shell
make tinker           # Laravel Tinker
```

### Viewing Logs
```bash
make logs             # All containers
make logs-php         # PHP/Laravel only
make logs-nginx       # Nginx only
make logs-node        # Vite only
```

### Alternative: Raw Docker Commands
```bash
docker compose exec php php artisan test
docker compose exec mysql mysql -u mp -pmp manage_petro
docker compose logs -f php
```

## Project Structure

```
manage-petro/
├── app/
│   ├── Models/
│   │   ├── Tenant.php
│   │   └── User.php
│   └── Modules/
│       ├── Shared/
│       │   ├── Concerns/
│       │   │   └── BelongsToTenant.php
│       │   ├── Http/
│       │   │   ├── ApiResponse.php
│       │   │   └── BaseFormRequest.php
│       │   └── Tenancy/
│       │       └── TenantMiddleware.php
│       ├── Auth/
│       │   └── Http/
│       │       ├── LoginController.php
│       │       └── MeController.php
│       ├── Clients/
│       │   └── Domain/Models/Client.php
│       ├── Locations/
│       │   └── Domain/Models/Location.php
│       ├── Trucks/
│       │   └── Domain/Models/DeliveryTruck.php
│       └── Orders/
│           └── Domain/Models/Order.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│       └── DemoMultiTenantSeeder.php
├── resources/
│   ├── js/
│   │   ├── app.tsx
│   │   ├── router.tsx
│   │   ├── lib/
│   │   │   ├── api.ts
│   │   │   └── useTenant.ts
│   │   └── pages/
│   │       ├── Login.tsx
│   │       └── Dashboard.tsx
│   └── views/
│       └── app.blade.php
├── routes/
│   ├── api.php
│   └── web.php
├── docker-compose.yml
├── Dockerfile
├── Makefile
├── vite.config.ts
└── README.md
```

## Technical Highlights

1. **Slug Tenancy**: Every route includes tenant slug, loaded by middleware
2. **BelongsToTenant Trait**: Auto-fills `tenant_id` + applies global scope
3. **Composite Foreign Keys**: Database-level enforcement of tenant boundaries
4. **React Router**: Tenant-aware routing with `:tenant` parameter
5. **Sanctum Auth**: Token-based authentication with tenant context

## Next Steps

To extend this MVP:

1. Add CRUD controllers for Clients, Locations, Trucks
2. Implement order lifecycle service with status transitions
3. Add overlap and capacity validation for scheduling
4. Build frontend pages for resource management
5. Add authorization policies per role
6. Implement real-time updates for order tracking

## License

MIT

## Contributing

This is an MVP demonstration project. For production use, add:
- Comprehensive test coverage
- API rate limiting
- Enhanced error handling
- Logging and monitoring
- Production-grade Docker configuration
