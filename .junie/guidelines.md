### Project Overview
This is a Laravel 10 application utilizing Laravel Nova 5 for its administration panel. It includes custom Nova components and integrates with Laravel Passport and Sanctum for authentication.

### Build & Configuration Instructions

#### Environment Setup
1. **Prerequisites**: PHP 8.2+, Composer, Node.js & NPM, and Docker (optional, for Laravel Sail).
2. **Clone & Install**:
   ```bash
   composer install
   npm install
   ```
3. **Environment Variables**:
   Copy `.env.example` to `.env` and configure your database and other services.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Database Migrations**:
   ```bash
   php artisan migrate
   ```
5. **Assets**:
   Build the frontend assets using Vite:
   ```bash
   npm run build
   # or for development:
   npm run dev
   ```

#### Laravel Sail (Docker)
The project is pre-configured with Laravel Sail. To start the development environment:
```bash
./vendor/bin/sail up -d
```

#### Production Update
A utility script for production updates is located at `bin/update.sh`. It handles git pulling, dependency installation (using a specific PHP path), migrations, and asset building.

### Testing Information

#### Configuration
Tests are configured via `phpunit.xml`. The project uses PHPUnit 10. Database testing should use a dedicated testing database, which is automatically handled if using Laravel Sail.

#### Running Tests
- **Local PHP**:
  ```bash
  vendor/bin/phpunit
  ```
- **Laravel Sail**:
  ```bash
  ./vendor/bin/sail test
  ```
- **Targeted Test**:
  ```bash
  vendor/bin/phpunit tests/Unit/ExampleTest.php
  ```

#### Adding New Tests
- **Unit Tests**: Place in `tests/Unit`. These should test isolated logic without database access where possible.
- **Feature Tests**: Place in `tests/Feature`. These test larger portions of the application, including HTTP requests and database interactions.

**Example Unit Test**:
```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public function test_basic_logic()
    {
        $this->assertTrue(true);
    }
}
```

### Additional Development Information

#### Code Style
- **Standard**: Follow PSR-12 coding standards.
- **Tools**: Laravel Pint is included for code style enforcement. Run it using:
  ```bash
  vendor/bin/pint
  ```
- **Nova Components**: Custom components are located in `nova-components/`. When modifying these, ensure you build them according to Nova's documentation.

#### Service Layer
The application uses a Service layer for complex business logic, located in `app/Services`. For example, `AttachmentService` handles file uploads and zip extractions for `TobidotElement`.

#### Helper Classes
- `App\Helpers\AppHelper`: A wrapper for Laravel's `resolve()` helper, providing better IDE support via generics.

#### File Storage
The project uses the `public` disk for many attachments, specifically for `tobidot-elements`. Ensure the storage link is created in development:
```bash
php artisan storage:link
```

### How To: Update Dependencies

#### PHP Dependencies
To update Composer dependencies to their latest allowed versions (according to `composer.json`):
```bash
composer update
```

#### JavaScript Dependencies
To update NPM dependencies:
```bash
npm update
```

#### Documentation
When releasing a new version, copy the latest documentation folder in `resources/docs/` (e.g., from `1.0` to `1.1`) and update the content as needed.
