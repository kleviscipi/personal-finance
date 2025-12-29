# Quick Start with Docker

Run the automated setup script:

```bash
./setup.sh
```

Or follow manual steps below:

## Manual Setup

1. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

2. **Build and start containers**
   ```bash
   docker-compose up -d --build
   ```

3. **Install dependencies**
   ```bash
   docker-compose exec app composer install
   docker-compose exec app npm install
   ```

4. **Generate application key**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

5. **Run migrations**
   ```bash
   docker-compose exec app php artisan migrate
   ```

6. **Build frontend assets**
   ```bash
   docker-compose exec app npm run build
   ```

## Access the Application

- **Web Application**: http://localhost
- **Mailpit (Email Testing)**: http://localhost:8025

## Docker Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose stop

# View logs
docker-compose logs -f

# Access application shell
docker-compose exec app sh

# Run artisan commands
docker-compose exec app php artisan <command>

# Run npm commands
docker-compose exec app npm <command>
```

## Development Workflow

For frontend development with hot reload:
```bash
docker-compose exec app npm run dev
```

Access at: http://localhost:5173
