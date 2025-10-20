.PHONY: help install setup up down restart logs shell clean fresh test migrate seed dev dev-bg wait-mysql

# Colors for terminal output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[0;33m
RED := \033[0;31m
NC := \033[0m # No Color

help: ## Show this help message
	@echo '$(BLUE)Manage Petro - Available Commands:$(NC)'
	@echo ''
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ''

# ============================================
# Setup & Installation
# ============================================

install: ## Complete first-time setup (Docker + dependencies + DB)
	@echo '$(BLUE)Starting complete installation...$(NC)'
	@make setup
	@make migrate-seed
	@echo '$(GREEN)✓ Installation complete!$(NC)'
	@echo ''
	@echo '$(YELLOW)To start the Vite dev server (optional):$(NC)'
	@echo '  make dev'
	@echo ''
	@echo '$(YELLOW)Access the application:$(NC)'
	@echo '  Acme:   http://localhost:8000/acme/login'
	@echo '  Globex: http://localhost:8000/globex/login'
	@echo ''
	@echo '$(YELLOW)Login credentials (both tenants):$(NC)'
	@echo '  admin@{tenant}.test / password'
	@echo '  dispatcher@{tenant}.test / password'
	@echo '  driver@{tenant}.test / password'
	@echo '  clientrep@{tenant}.test / password'

setup: ## Set up environment and install dependencies
	@echo '$(BLUE)Setting up environment...$(NC)'
	@if [ ! -f .env ]; then cp .env.example .env; echo '$(GREEN)✓ Created .env file$(NC)'; fi
	@make up
	@echo '$(BLUE)Waiting for containers to be ready...$(NC)'
	@sleep 2
	@echo '$(BLUE)Installing backend dependencies...$(NC)'
	@docker compose exec php composer install
	@docker compose exec php php artisan key:generate
	@echo '$(GREEN)✓ Backend dependencies installed$(NC)'
	@echo '$(BLUE)Installing frontend dependencies...$(NC)'
	@docker compose exec node npm install
	@echo '$(GREEN)✓ Frontend dependencies installed$(NC)'

# ============================================
# Docker Management
# ============================================

up: ## Start all Docker containers
	@echo '$(BLUE)Starting Docker containers...$(NC)'
	@docker compose up -d --build
	@echo '$(GREEN)✓ Containers started$(NC)'

down: ## Stop all Docker containers
	@echo '$(BLUE)Stopping Docker containers...$(NC)'
	@docker compose down
	@echo '$(GREEN)✓ Containers stopped$(NC)'

restart: ## Restart all Docker containers
	@echo '$(BLUE)Restarting Docker containers...$(NC)'
	@docker compose restart
	@echo '$(GREEN)✓ Containers restarted$(NC)'

ps: ## Show running containers
	@docker compose ps

# ============================================
# Database Management
# ============================================

wait-mysql: ## Wait for MySQL to be ready
	@echo '$(BLUE)Waiting for MySQL to be ready...$(NC)'
	@until docker compose exec mysql mysqladmin ping -h localhost --silent 2>/dev/null; do \
		echo '$(YELLOW)MySQL is starting up...$(NC)'; \
		sleep 2; \
	done
	@echo '$(GREEN)✓ MySQL is ready$(NC)'

migrate: ## Run database migrations
	@echo '$(BLUE)Running migrations...$(NC)'
	@docker compose exec php php artisan migrate
	@echo '$(GREEN)✓ Migrations complete$(NC)'

migrate-fresh: ## Drop all tables and re-run migrations
	@echo '$(RED)WARNING: This will delete all data!$(NC)'
	@echo 'Press Ctrl+C to cancel, or Enter to continue...'
	@read confirm
	@docker compose exec php php artisan migrate:fresh
	@echo '$(GREEN)✓ Fresh migrations complete$(NC)'

seed: ## Seed the database with demo data
	@echo '$(BLUE)Seeding database...$(NC)'
	@docker compose exec php php artisan db:seed
	@echo '$(GREEN)✓ Database seeded$(NC)'

migrate-seed: ## Run migrations and seed database
	@make wait-mysql
	@make migrate
	@make seed

fresh: ## Fresh install (drop DB, migrate, seed)
	@echo '$(RED)WARNING: This will delete all data!$(NC)'
	@echo 'Press Ctrl+C to cancel, or Enter to continue...'
	@read confirm
	@make migrate-fresh
	@make seed
	@echo '$(GREEN)✓ Fresh database ready$(NC)'

db-shell: ## Access MySQL shell
	@docker compose exec mysql mysql -u mp -pmp manage_petro

# ============================================
# Development
# ============================================

logs: ## Show Docker container logs (all)
	@docker compose logs -f

logs-php: ## Show PHP/Laravel logs
	@docker compose logs -f php

logs-nginx: ## Show Nginx logs
	@docker compose logs -f nginx

logs-node: ## Show Node/Vite logs
	@docker compose logs -f node

shell-php: ## Access PHP container shell
	@docker compose exec php sh

shell-node: ## Access Node container shell
	@docker compose exec node sh

shell-mysql: ## Access MySQL container shell
	@docker compose exec mysql sh

tinker: ## Open Laravel Tinker
	@docker compose exec php php artisan tinker

# ============================================
# Frontend Development
# ============================================

dev: ## Start Vite dev server (hot reload) - runs in foreground
	@echo '$(BLUE)Starting Vite dev server...$(NC)'
	@echo '$(YELLOW)Press Ctrl+C to stop$(NC)'
	@docker compose exec node npm run dev

dev-bg: ## Start Vite dev server in background
	@echo '$(BLUE)Starting Vite dev server in background...$(NC)'
	@docker compose exec -d node npm run dev
	@echo '$(GREEN)✓ Vite dev server started$(NC)'
	@echo '$(YELLOW)View logs: make logs-node$(NC)'

build: ## Build frontend for production
	@docker compose exec node npm run build

npm: ## Run npm command (usage: make npm CMD="install axios")
	@docker compose exec node npm $(CMD)

# ============================================
# Backend Development
# ============================================

composer: ## Run composer command (usage: make composer CMD="require package")
	@docker compose exec php composer $(CMD)

artisan: ## Run artisan command (usage: make artisan CMD="route:list")
	@docker compose exec php php artisan $(CMD)

routes: ## Show all routes
	@docker compose exec php php artisan route:list

cache-clear: ## Clear all caches
	@echo '$(BLUE)Clearing caches...$(NC)'
	@docker compose exec php php artisan cache:clear
	@docker compose exec php php artisan config:clear
	@docker compose exec php php artisan route:clear
	@docker compose exec php php artisan view:clear
	@echo '$(GREEN)✓ Caches cleared$(NC)'

# ============================================
# Testing & Code Quality
# ============================================

test: ## Run all tests
	@docker compose exec php php artisan test

test-coverage: ## Run tests with coverage
	@docker compose exec php php artisan test --coverage

pint: ## Run Laravel Pint (code style fixer)
	@echo '$(BLUE)Running Laravel Pint...$(NC)'
	@docker compose exec php ./vendor/bin/pint
	@echo '$(GREEN)✓ Code style fixed$(NC)'

pint-test: ## Check code style without fixing
	@docker compose exec php ./vendor/bin/pint --test

# ============================================
# Git Hooks
# ============================================

hooks-install: ## Install Git hooks for automated testing
	@./.githooks/install.sh

hooks-uninstall: ## Uninstall Git hooks
	@./.githooks/uninstall.sh

hooks-info: ## Show Git hooks information
	@echo '$(BLUE)Git Hooks Status:$(NC)'
	@echo ''
	@if [ -f .git/hooks/pre-commit ]; then \
		echo '$(GREEN)✓ pre-commit$(NC)   - Code quality checks'; \
	else \
		echo '$(RED)✗ pre-commit$(NC)   - Not installed'; \
	fi
	@if [ -f .git/hooks/pre-push ]; then \
		echo '$(GREEN)✓ pre-push$(NC)     - Run tests before push'; \
	else \
		echo '$(RED)✗ pre-push$(NC)     - Not installed'; \
	fi
	@if [ -f .git/hooks/commit-msg ]; then \
		echo '$(GREEN)✓ commit-msg$(NC)   - Validate commit messages'; \
	else \
		echo '$(RED)✗ commit-msg$(NC)   - Not installed'; \
	fi
	@echo ''
	@echo '$(YELLOW)To install hooks: make hooks-install$(NC)'
	@echo '$(YELLOW)Documentation: .githooks/README.md$(NC)'

# ============================================
# Cleanup
# ============================================

clean: ## Remove all containers, volumes, and dependencies
	@echo '$(RED)WARNING: This will remove all containers and volumes!$(NC)'
	@echo 'Press Ctrl+C to cancel, or Enter to continue...'
	@read confirm
	@docker compose down -v
	@rm -rf vendor node_modules
	@echo '$(GREEN)✓ Cleanup complete$(NC)'

clean-logs: ## Clear Laravel logs
	@docker compose exec php sh -c "rm -f storage/logs/*.log"
	@echo '$(GREEN)✓ Logs cleared$(NC)'

# ============================================
# Quick Access URLs
# ============================================

open: ## Open application in browser
	@echo 'Opening Manage Petro...'
	@open http://localhost:8000/acme/login || xdg-open http://localhost:8000/acme/login || echo 'Visit: http://localhost:8000/acme/login'

open-acme: ## Open Acme tenant
	@open http://localhost:8000/acme/login || xdg-open http://localhost:8000/acme/login || echo 'Visit: http://localhost:8000/acme/login'

open-globex: ## Open Globex tenant
	@open http://localhost:8000/globex/login || xdg-open http://localhost:8000/globex/login || echo 'Visit: http://localhost:8000/globex/login'

# ============================================
# Default target
# ============================================

.DEFAULT_GOAL := help
