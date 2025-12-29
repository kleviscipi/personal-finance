.PHONY: help setup up down restart logs shell artisan composer npm test migrate seed fresh

# Default target
help:
	@echo "Personal Finance - Docker Commands"
	@echo "===================================="
	@echo ""
	@echo "Setup & Management:"
	@echo "  make setup      - Initial setup (builds containers, installs deps, runs migrations)"
	@echo "  make up         - Start all containers"
	@echo "  make down       - Stop all containers"
	@echo "  make restart    - Restart all containers"
	@echo "  make logs       - View container logs"
	@echo ""
	@echo "Development:"
	@echo "  make shell      - Access application shell"
	@echo "  make artisan    - Run artisan command (e.g., make artisan migrate)"
	@echo "  make composer   - Run composer command (e.g., make composer install)"
	@echo "  make npm        - Run npm command (e.g., make npm run dev)"
	@echo "  make test       - Run tests"
	@echo ""
	@echo "Database:"
	@echo "  make migrate    - Run database migrations"
	@echo "  make seed       - Seed database"
	@echo "  make fresh      - Fresh migration with seed"
	@echo ""

# Initial setup
setup:
	@echo "🚀 Setting up Personal Finance..."
	@./setup.sh

# Start containers
up:
	@echo "🚀 Starting containers..."
	@docker-compose up -d

# Stop containers
down:
	@echo "🛑 Stopping containers..."
	@docker-compose down

# Restart containers
restart:
	@echo "🔄 Restarting containers..."
	@docker-compose restart

# View logs
logs:
	@docker-compose logs -f

# Access shell
shell:
	@docker-compose exec app sh

# Run artisan command
artisan:
	@docker-compose exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

# Run composer command
composer:
	@docker-compose exec app composer $(filter-out $@,$(MAKECMDGOALS))

# Run npm command
npm:
	@docker-compose exec app npm $(filter-out $@,$(MAKECMDGOALS))

# Run tests
test:
	@docker-compose exec app php artisan test

# Run migrations
migrate:
	@docker-compose exec app php artisan migrate

# Seed database
seed:
	@docker-compose exec app php artisan db:seed

# Fresh migration with seed
fresh:
	@docker-compose exec app php artisan migrate:fresh --seed

# Catch-all target to prevent "No rule to make target" errors
%:
	@:
