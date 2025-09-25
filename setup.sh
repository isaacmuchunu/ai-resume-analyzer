#!/bin/bash

# AI Resume Analyzer - Setup Script
# This script sets up the application for development or production

set -e

echo "🚀 Setting up AI Resume Analyzer..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if environment argument is provided
ENVIRONMENT=${1:-development}

if [ "$ENVIRONMENT" != "development" ] && [ "$ENVIRONMENT" != "production" ]; then
    print_error "Invalid environment. Use 'development' or 'production'"
    exit 1
fi

print_status "Setting up for $ENVIRONMENT environment..."

# Check if Docker is installed and running
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

if ! docker info &> /dev/null; then
    print_error "Docker is not running. Please start Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create environment file
if [ ! -f .env ]; then
    print_status "Creating environment file..."
    cp .env.example .env
    
    # Generate application key
    if [ "$ENVIRONMENT" == "development" ]; then
        print_status "Generating application key..."
        docker-compose run --rm app php artisan key:generate
    fi
    
    print_warning "Please update .env file with your configuration (database, API keys, etc.)"
else
    print_status "Environment file already exists"
fi

# Build and start containers
print_status "Building Docker containers..."
docker-compose build

print_status "Starting containers..."
docker-compose up -d

# Wait for database to be ready
print_status "Waiting for database to be ready..."
sleep 30

# Install PHP dependencies
print_status "Installing PHP dependencies..."
docker-compose exec app composer install

# Install Node.js dependencies and build assets
print_status "Installing Node.js dependencies..."
docker-compose exec app npm install

if [ "$ENVIRONMENT" == "production" ]; then
    print_status "Building production assets..."
    docker-compose exec app npm run build
else
    print_status "Building development assets..."
    docker-compose exec app npm run dev &
fi

# Run database migrations
print_status "Running database migrations..."
docker-compose exec app php artisan migrate --force

# Create storage link
print_status "Creating storage link..."
docker-compose exec app php artisan storage:link

# Set up demo tenant for development
if [ "$ENVIRONMENT" == "development" ]; then
    print_status "Setting up demo tenant..."
    docker-compose exec app php artisan tenant:setup demo-corp --create-admin --email=admin@demo.com --password=password
    
    print_status "Seeding demo data..."
    docker-compose exec app php artisan db:seed --class=DemoUserSeeder
fi

# Clear and cache configuration
print_status "Optimizing application..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

if [ "$ENVIRONMENT" == "production" ]; then
    docker-compose exec app php artisan config:cache
    docker-compose exec app php artisan route:cache
    docker-compose exec app php artisan view:cache
fi

# Set proper permissions
print_status "Setting file permissions..."
docker-compose exec app chown -R www-data:www-data /var/www/storage
docker-compose exec app chown -R www-data:www-data /var/www/bootstrap/cache

print_success "Setup complete!"

echo ""
echo "🎉 AI Resume Analyzer is now running!"
echo ""
echo "📝 Next steps:"
echo "1. Update your .env file with:"
echo "   - ANTHROPIC_API_KEY (required for AI analysis)"
echo "   - STRIPE_* keys (required for payments)"
echo "   - Mail configuration"
echo ""
echo "2. Access the application:"
if [ "$ENVIRONMENT" == "development" ]; then
    echo "   - Main app: http://localhost:8000"
    echo "   - Demo tenant: http://localhost:8000?tenant=demo-corp"
    echo "   - Admin login: admin@demo.com / password"
else
    echo "   - Configure your domain to point to this server"
    echo "   - Set up SSL/TLS certificates"
    echo "   - Configure environment variables for production"
fi
echo ""
echo "3. Monitor the application:"
echo "   - docker-compose logs -f"
echo "   - docker-compose ps"
echo ""

if [ "$ENVIRONMENT" == "development" ]; then
    echo "🛠️  Development commands:"
    echo "   - Run tests: docker-compose exec app php artisan test"
    echo "   - Queue worker: docker-compose exec app php artisan queue:work"
    echo "   - Tinker: docker-compose exec app php artisan tinker"
    echo ""
fi

echo "📚 Documentation: Check README.md for more details"
echo "🐛 Issues: Report problems on GitHub"
echo ""
print_success "Happy coding! 🚀"