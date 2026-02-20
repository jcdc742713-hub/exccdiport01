# ExcCDI Portal - Workflow Management System

## Features

### Student Management
- Complete student enrollment workflow
- Automated document verification
- Academic review process
- Payment tracking
- Status management (pending, active, suspended, graduated)

### Accounting Management
- Transaction approval workflow
- Multi-level approval system
- Invoice and payment tracking
- Refund processing
- Financial reporting

### Workflow Engine
- Flexible workflow definition
- Multi-step approval processes
- Automatic notifications
- Audit trail and history tracking
- Polymorphic entity support

## Technology Stack

- **Backend**: Laravel 11.x
- **Frontend**: Vue 3 + TypeScript
- **Build Tool**: Vite
- **Database**: MySQL 8.0
- **Authentication**: Laravel Sanctum
- **Notifications**: Laravel Notifications

## Quick Start

See [DEPLOYMENT.md](DEPLOYMENT.md) for complete installation instructions.
```bash
# Install dependencies
composer install && npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Build frontend
npm run dev
```

## Documentation

- [API Documentation](docs/api.md)
- [Workflow Guide](docs/workflows.md)
- [Deployment Guide](DEPLOYMENT.md)

## License

MIT License
