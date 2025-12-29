# Setup Guide

Complete step-by-step guide for setting up the Personal Finance Web Application for development.

## Prerequisites

Before you begin, ensure you have the following installed:

### Required Software

1. **PHP 8.2 or higher**
   ```bash
   php --version
   # Should show PHP 8.2.x or higher
   ```

2. **Composer** (PHP package manager)
   ```bash
   composer --version
   # Should show Composer version 2.x
   ```

3. **PostgreSQL 14 or higher**
   ```bash
   psql --version
   # Should show PostgreSQL 14.x or higher
   ```

4. **Node.js 18 or higher** (for frontend assets)
   ```bash
   node --version
   # Should show v18.x or higher
   ```

5. **NPM or Yarn**
   ```bash
   npm --version
   # or
   yarn --version
   ```

### Optional but Recommended

- **Git** (version control)
- **Docker** (for containerized PostgreSQL)
- **Redis** (for caching and queues)
- **Mailhog** (for local email testing)

## Installation Steps

### 1. Clone the Repository

```bash
git clone https://github.com/kleviscipi/personal-finance.git
cd personal-finance
```

### 2. Install PHP Dependencies

```bash
composer install
```

This will install all Laravel dependencies including:
- Laravel Framework 11.x
- Inertia.js server-side adapter
- Ziggy (route helper for frontend)
- Development tools (PHPUnit, Pint, etc.)

### 3. Install JavaScript Dependencies

```bash
npm install
# or
yarn install
```

### 4. Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

### 5. Set Up PostgreSQL Database

#### Option A: Local PostgreSQL Installation

1. **Create Database:**
   ```bash
   # Login to PostgreSQL
   psql -U postgres
   
   # Create database
   CREATE DATABASE personal_finance;
   
   # Create user (optional)
   CREATE USER personal_finance_user WITH PASSWORD 'your_secure_password';
   
   # Grant privileges
   GRANT ALL PRIVILEGES ON DATABASE personal_finance TO personal_finance_user;
   
   # Exit
   \q
   ```

2. **Update .env file:**
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=personal_finance
   DB_USERNAME=postgres
   DB_PASSWORD=your_password
   ```

#### Option B: Docker PostgreSQL

1. **Using Docker Compose:**
   
   Create `docker-compose.yml`:
   ```yaml
   version: '3.8'
   services:
     postgres:
       image: postgres:16-alpine
       environment:
         POSTGRES_DB: personal_finance
         POSTGRES_USER: personal_finance_user
         POSTGRES_PASSWORD: secret
       ports:
         - "5432:5432"
       volumes:
         - postgres_data:/var/lib/postgresql/data
   
   volumes:
     postgres_data:
   ```

2. **Start PostgreSQL:**
   ```bash
   docker-compose up -d
   ```

3. **Update .env file:**
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=personal_finance
   DB_USERNAME=personal_finance_user
   DB_PASSWORD=secret
   ```

#### Option C: Laravel Sail (Recommended for beginners)

Laravel Sail provides a complete Docker development environment:

```bash
# Install Sail
composer require laravel/sail --dev

# Publish Sail configuration
php artisan sail:install

# Start Sail
./vendor/bin/sail up -d

# Update .env to use Sail's PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=personal_finance
DB_USERNAME=sail
DB_PASSWORD=password
```

### 6. Run Database Migrations

```bash
php artisan migrate
```

This will create all necessary tables:
- users
- accounts
- account_user
- categories
- subcategories
- transactions
- budgets
- transaction_histories
- account_settings
- cache, jobs, sessions (Laravel default)

### 7. Seed Default Categories (Optional but Recommended)

**Note:** Categories are seeded per account, so you'll need to create an account first. For initial setup, you can manually seed after creating your first account through the UI, or use a custom seeder.

```bash
php artisan db:seed --class=CategorySeeder
```

## Frontend Setup

### Option 1: Vue.js with Inertia.js (Recommended)

1. **Install Laravel Breeze with Vue:**
   ```bash
   composer require laravel/breeze --dev
   php artisan breeze:install vue
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Build assets:**
   ```bash
   # Development
   npm run dev
   
   # Production
   npm run build
   ```

### Option 2: React with Inertia.js

1. **Install Laravel Breeze with React:**
   ```bash
   composer require laravel/breeze --dev
   php artisan breeze:install react
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Build assets:**
   ```bash
   npm run dev
   ```

## Running the Application

### Development Server

**Option 1: PHP Built-in Server**
```bash
php artisan serve
```
Application will be available at: http://localhost:8000

**Option 2: Laravel Sail**
```bash
./vendor/bin/sail up
```
Application will be available at: http://localhost

### Building Frontend Assets

In a separate terminal, run:
```bash
npm run dev
```

This watches for changes and rebuilds automatically.

## Verify Installation

### Check Database Connection

```bash
php artisan migrate:status
```

Should show all migrations as "Ran".

### Run Tests

```bash
php artisan test
```

Should show all tests passing (when tests are implemented).

### Access the Application

1. Open http://localhost:8000 (or http://localhost for Sail)
2. You should see the Laravel welcome page
3. Register a new user account
4. Create your first account/workspace

## Optional Configuration

### Redis (for caching and queues)

1. **Install Redis:**
   ```bash
   # Ubuntu/Debian
   sudo apt-get install redis-server
   
   # macOS
   brew install redis
   
   # Docker
   docker run -d -p 6379:6379 redis:alpine
   ```

2. **Update .env:**
   ```env
   CACHE_STORE=redis
   SESSION_DRIVER=redis
   QUEUE_CONNECTION=redis
   
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

3. **Install PHP Redis extension:**
   ```bash
   pecl install redis
   ```

### Mail Configuration (for notifications)

**Development: Mailhog**

1. **Start Mailhog:**
   ```bash
   # Docker
   docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog
   ```

2. **Update .env:**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=127.0.0.1
   MAIL_PORT=1025
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   MAIL_ENCRYPTION=null
   MAIL_FROM_ADDRESS="noreply@personalfinance.app"
   MAIL_FROM_NAME="${APP_NAME}"
   ```

3. **Access Mailhog UI:** http://localhost:8025

**Production: Use a service like:**
- Mailgun
- SendGrid
- Amazon SES
- Postmark

### Queue Worker (for background jobs)

```bash
php artisan queue:work
```

Or with Supervisor for production (see Laravel docs).

## IDE Setup

### VS Code

Recommended extensions:
- Laravel Extension Pack
- PHP Intelephense
- Vetur (for Vue.js)
- ESLint
- Prettier

**Settings (.vscode/settings.json):**
```json
{
  "editor.formatOnSave": true,
  "php.suggest.basic": false,
  "intelephense.environment.phpVersion": "8.3",
  "[php]": {
    "editor.defaultFormatter": "bmewburn.vscode-intelephense-client"
  }
}
```

### PHPStorm

1. Enable Laravel plugin
2. Configure PHP interpreter (8.2+)
3. Set up database connection
4. Enable Blade syntax highlighting
5. Configure code style (PSR-12)

## Development Workflow

### 1. Starting Development

```bash
# Terminal 1: Start application
php artisan serve

# Terminal 2: Watch frontend assets
npm run dev

# Terminal 3 (optional): Queue worker
php artisan queue:work

# Terminal 4 (optional): Redis
redis-server
```

### 2. Making Database Changes

```bash
# Create new migration
php artisan make:migration create_something_table

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh install (drops all tables)
php artisan migrate:fresh

# Fresh install with seeding
php artisan migrate:fresh --seed
```

### 3. Code Style

```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Check for issues without fixing
./vendor/bin/pint --test
```

### 4. Creating New Components

```bash
# Model
php artisan make:model ModelName

# Controller
php artisan make:controller ControllerName

# Migration
php artisan make:migration create_table_name

# Seeder
php artisan make:seeder SeederName

# Policy
php artisan make:policy PolicyName --model=ModelName

# Request
php artisan make:request StoreRequestName

# Service (manual creation in app/Services/)
```

## Troubleshooting

### Common Issues

#### "Database connection refused"
- Ensure PostgreSQL is running
- Check DB credentials in .env
- Verify port (5432) is not blocked

```bash
# Check PostgreSQL status
sudo service postgresql status

# or with Docker
docker ps | grep postgres
```

#### "Class not found" errors
```bash
# Clear and rebuild autoload
composer dump-autoload
php artisan clear-compiled
php artisan config:clear
```

#### "Permission denied" on storage
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

#### Frontend assets not loading
```bash
# Clear all caches
php artisan optimize:clear

# Rebuild assets
npm run build
```

#### Migration errors
```bash
# Check migration status
php artisan migrate:status

# Rollback and retry
php artisan migrate:rollback
php artisan migrate
```

### Getting Help

1. Check the [Laravel Documentation](https://laravel.com/docs)
2. Review [ARCHITECTURE.md](ARCHITECTURE.md) for system design
3. Check [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) for database structure
4. Open an issue on GitHub
5. Laravel community on Discord/Forums

## Production Deployment

### Pre-deployment Checklist

- [ ] Set `APP_ENV=production` in .env
- [ ] Set `APP_DEBUG=false` in .env
- [ ] Change `APP_KEY` to a new secure key
- [ ] Configure production database
- [ ] Set up Redis for caching
- [ ] Configure queue workers
- [ ] Set up scheduled tasks (cron)
- [ ] Configure mail service
- [ ] Set up file storage (S3)
- [ ] Configure backups
- [ ] Set up monitoring (Sentry, etc.)
- [ ] SSL certificate configured

### Deployment Commands

```bash
# Pull latest code
git pull origin main

# Install dependencies (production only)
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build assets
npm ci
npm run build

# Restart queue workers
php artisan queue:restart
```

### Performance Optimization

```bash
# Enable OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000

# Use Redis for cache and sessions
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## Security Checklist

- [ ] `.env` file not in version control
- [ ] Database credentials are secure
- [ ] `APP_KEY` is generated and secure
- [ ] HTTPS/SSL configured in production
- [ ] CSRF protection enabled (Laravel default)
- [ ] SQL injection prevention (use Eloquent/Query Builder)
- [ ] XSS prevention (use Blade's `{{ }}` escaping)
- [ ] Rate limiting configured
- [ ] Regular dependency updates (`composer update`)
- [ ] Security headers configured

## Next Steps

1. **Complete the setup** following this guide
2. **Register your first user** through the web interface
3. **Create an account** (workspace)
4. **Seed categories** for your account
5. **Start building the frontend** components
6. **Implement controller methods**
7. **Write tests** for critical functionality

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Vue.js Documentation](https://vuejs.org/)
- [TailwindCSS Documentation](https://tailwindcss.com/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

## Support

For issues and questions:
- GitHub Issues: https://github.com/kleviscipi/personal-finance/issues
- Project Documentation: See `/docs` directory
- Laravel Discord: https://discord.gg/laravel

---

Happy coding! 🚀
