# CRM Hub - Multi-Brand Contact Management System

## Overview

CRM Hub is a centralized contact management platform designed for multi-brand media companies. It provides a unified system to manage customer inquiries, track communications, and deliver excellent support across all brand channels.

## Features

### 🎯 **Multi-Brand Support**
- **BigFM** - Radio broadcasting
- **RPR1** - Radio broadcasting  
- **Regenbogen** - Radio broadcasting
- **RockFM** - Radio broadcasting
- **BigKarriere** - Career services

### 📋 **Contact Management**
- Separate API endpoints for each brand
- Flexible JSON data storage for future compatibility
- IP address tracking for security
- Real-time form submission handling

### 👥 **User Management & Roles**
- **Superadmin**: Full system access + user management
- **Admin**: Dashboard access + contact management
- **User**: Contact message viewing and management

### 📊 **Advanced Features**
- **Read/Unread Tracking**: Per-user read status with timestamps
- **Advanced Filtering**: Search by name, email, description, category, and status
- **Real-time Updates**: See who has read messages and when
- **Responsive Design**: Modern UI with dark mode support
- **Role-based Navigation**: Dynamic menus based on user permissions

## Technology Stack

### Backend
- **Laravel 12** - PHP framework
- **MySQL** - Database
- **Laravel Fortify** - Authentication
- **Spatie Laravel Permission** - Role & permission management

### Frontend  
- **Vue 3** (Composition API)
- **TypeScript** - Type safety
- **Inertia.js** - SPA experience with server-side routing
- **Tailwind CSS** - Utility-first styling
- **shadcn-vue** - Component library
- **Laravel Wayfinder** - TypeScript route generation

### Development Tools
- **Vite** - Fast build tool and dev server
- **Laravel Pint** - PHP code style
- **ESLint** - JavaScript/TypeScript linting

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd forms
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database configuration**
   - Update `.env` with your database credentials
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Start development servers**
   ```bash
   # Terminal 1 - Laravel
   php artisan serve
   
   # Terminal 2 - Vite
   npm run dev
   ```

## API Endpoints

### Public Contact Forms
Each brand has its own dedicated endpoint:

- `POST /contact/bigfm` - BigFM submissions
- `POST /contact/rpr1` - RPR1 submissions  
- `POST /contact/regenbogen` - Regenbogen submissions
- `POST /contact/rockfm` - RockFM submissions
- `POST /contact/bigkarriere` - BigKarriere submissions

### Required Fields
```json
{
  "name": "John Doe",
  "email": "john@example.com", 
  "description": "Message content"
}
```

### Response Format
```json
{
  "success": true,
  "message": "Contact form submitted successfully",
  "submission_id": 123
}
```

## Default Accounts

After running `php artisan db:seed`, these accounts are available:

| Role | Email | Password |
|------|-------|----------|
| Superadmin | superadmin@example.com | password |
| Admin | admin@example.com | password |
| User | user@example.com | password |

## Key URLs

- **Homepage**: `http://localhost:8000/` - CRM welcome page
- **Public Contact Form**: `http://localhost:8000/contact` - Test form
- **Staff Login**: `http://localhost:8000/login` - Staff access
- **Contact Dashboard**: `http://localhost:8000/contact-messages` - Message management

## User Management (Superadmin Only)

Superadmins can manage system users through:
- **Create Users**: Add new staff members
- **Edit Users**: Update user information and roles
- **Deactivate Users**: Temporarily disable accounts (soft delete)
- **Restore Users**: Reactivate deactivated accounts
- **Permanent Delete**: Complete user removal
- **CLI Management**: `php artisan user:status {email} {activate|deactivate}`

## Security Features

- **CSRF Protection**: All forms protected
- **Role-based Access**: Middleware-enforced permissions
- **User Deactivation**: Automatic logout for deactivated users
- **Session Management**: Secure authentication flow
- **Registration Disabled**: No public account creation

## Development

### Code Style
```bash
# PHP (Laravel Pint)
./vendor/bin/pint

# TypeScript/Vue (ESLint)
npm run lint
```

### Testing
```bash
# PHP Tests
php artisan test

# Frontend Tests
npm run test
```

### Route Generation
```bash
# Generate TypeScript route helpers
php artisan wayfinder:generate
```

## License

This CRM system is proprietary software. All rights reserved.

## Support

For technical support or feature requests, please contact the development team.