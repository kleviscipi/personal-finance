#!/bin/bash

# Personal Finance Setup Script
# This script sets up the complete Docker environment

set -e

echo "🚀 Personal Finance - Docker Setup"
echo "=================================="
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

echo "✅ Docker and Docker Compose are installed"
echo ""

# Copy .env.example to .env if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    echo "✅ .env file created"
else
    echo "ℹ️  .env file already exists"
fi

echo ""
echo "🏗️  Building Docker containers..."
docker-compose build

echo ""
echo "🚀 Starting Docker containers..."
docker-compose up -d

echo ""
echo "⏳ Waiting for database to be ready..."
sleep 10

echo ""
echo "📦 Installing Composer dependencies..."
docker-compose exec -T app composer install

echo ""
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate

echo ""
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force

echo ""
echo "🌱 Seeding database with default categories..."
docker-compose exec -T app php artisan db:seed --class=CategorySeeder || echo "ℹ️  No accounts to seed yet"

echo ""
echo "📦 Installing NPM dependencies..."
docker-compose exec -T app npm install

echo ""
echo "🎨 Building frontend assets..."
docker-compose exec -T app npm run build

echo ""
echo "✨ Setup complete!"
echo ""
echo "🌐 Application URLs:"
echo "   - Web Application: http://localhost"
echo "   - Mailpit Dashboard: http://localhost:8025"
echo ""
echo "📊 Database Connection:"
echo "   - Host: localhost"
echo "   - Port: 5432"
echo "   - Database: personal_finance"
echo "   - Username: sail"
echo "   - Password: secret"
echo ""
echo "🔧 Useful commands:"
echo "   - Stop containers: docker-compose stop"
echo "   - Start containers: docker-compose start"
echo "   - View logs: docker-compose logs -f"
echo "   - Restart: docker-compose restart"
echo "   - Shell access: docker-compose exec app sh"
echo "   - Run artisan: docker-compose exec app php artisan <command>"
echo ""
