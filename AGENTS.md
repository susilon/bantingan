# Bantingan Framework

## Overview

Bantingan is a private PHP MVC framework (v3) designed for building web applications. It uses Composer for dependency management and is meant to be included as a dependency in PHP projects. The framework provides a lightweight MVC structure with ORM capabilities, routing, templating, and various utility functions.

## Framework Architecture

### Core Components

| File | Purpose |
|------|---------|
| `src/Bantingan.php` | Main application bootstrap, session management, request dispatch |
| `src/Controller.php` | Base controller with view rendering, PDF/Excel/CSV export, redirects |
| `src/Model.php` | ORM wrapper using RedBeanPHP for database operations |
| `src/AppRouter.php` | URL routing using Symfony Routing component |
| `src/PageGenerator.php` | Smarty template engine integration |
| `src/Settings.php` | YAML configuration loader with environment variable support |
| `src/Installer.php` | Composer post-install directory creation |
| `src/Psr4AutoloaderClass.php` | PSR-4 autoloader for controllers, models, and modules |

### Directory Structure

```
bantingan/
├── composer.json          # Package definition (susilon/bantingan)
├── LICENSE                # MIT License
├── README.md              # Basic documentation
├── src/                   # Framework core classes
│   ├── Bantingan.php      # Application entry point
│   ├── Controller.php     # Base controller class
│   ├── Model.php          # Base model with RedBeanPHP ORM
│   ├── AppRouter.php      # Routing handler
│   ├── PageGenerator.php  # View/templating engine
│   ├── Settings.php       # Configuration loader
│   ├── Installer.php      # Setup script
│   └── Psr4AutoloaderClass.php  # Autoloader
└── .git/                  # Version control
```

## Key Dependencies

- **symfony/routing** (^5.2) - URL routing and matching
- **smarty/smarty** (^4.2) - Template engine for views
- **gabordemooij/redbean** (dev-master) - ORM for database operations
- **dompdf/dompdf** (^3.0) - PDF generation
- **phpoffice/phpspreadsheet** (^1.16) - Excel file generation
- **phpmailer/phpmailer** (^6.2) - Email sending
- **gregwar/captcha** (^1.2) - CAPTCHA generation
- **symfony/yaml** (^5.2) - YAML configuration parsing

## Configuration

The framework expects a YAML configuration file that defines:

- `APPLICATION_SETTINGS` - Base URL, default controller, views folder, etc.
- `DATABASE_SETTINGS` - Database connections (supports MySQL, PostgreSQL, SQLite, SQL Server)
- `ROUTE_SETTINGS` - Custom route definitions
- Language settings and more

Configuration can be overridden via environment variables using the `BANTINGAN3_` prefix.

## Usage Patterns

### Controller Development

Controllers should:
- Extend `Bantingan\Controller`
- Use `$this->view()` to render Smarty templates
- Use `$this->page()` to return HTML as string
- Use `$this->flash()` for session flash messages
- Extend namespace support via `$this->namespace`

### Model Development

Models should:
- Extend `Bantingan\Model`
- Use inherited methods: `load()`, `create()`, `find()`, `findAll()`, `save()`, `trash()`
- Support multiple database connections
- Use `getAll()`, `getRow()`, `getCell()` for raw SQL queries

### Routing

Routes are configured via YAML and support:
- Static routes with predefined controllers/actions
- Dynamic routes with wildcards
- Namespace-based routing for module organization
- REST-style parameter passing

## Code Style Guidelines

- **PHP Version**: Requires PHP ^7.2 (PHP 8 supported)
- **Namespace**: All core classes use `Bantingan` namespace
- **Error Handling**: Uses try-catch with custom error handler
- **Session Management**: Auto-starts sessions, supports database-backed sessions
- **Database**: Uses RedBeanPHP ORM with lazy loading pattern

## Important Notes

1. **Private Repository**: This is not publicly available on Packagist
2. **Project Integration**: Include via Composer from this repository
3. **Autoloading**: PSR-4 autoloading configured for `Bantingan\` and `Modules\` namespaces
4. **Template Engine**: Views use Smarty with `.html` extension
5. **Controller Naming**: Controllers must end with `Controller.php` suffix
6. **Model Naming**: Models automatically infer table names from class names

## Common Tasks

### Creating a New Controller

```php
<?php
namespace Controllers;

use Bantingan\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $this->viewBag->message = "Welcome";
        $this->view("home/index.html");
    }
}
```

### Creating a New Model

```php
<?php
namespace Models;

use Bantingan\Model;

class UserModel extends Model
{
    // Table name auto-inferred as 'user'
    // Use inherited ORM methods
}
```

### Adding a Route

In route.config YAML:
```yaml
myroute:
  path: /custom/path
  controller: home
  action: customaction
  wildcard: true  # Enable parameter passing
```

## Testing

No testing framework is currently configured. Consider adding PHPUnit for unit testing controllers and models.

## Build/Deployment

- No build process required (PHP framework)
- Install via: `composer install`
- Post-install script creates required directories (app, assets, config, modules)
