# 🐞 Debug Notary

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dennisbusk/debug-notary.svg?style=flat-square)](https://packagist.org/packages/dennisbusk/debug-notary)
[![Total Downloads](https://img.shields.io/packagist/dt/dennisbusk/debug-notary.svg?style=flat-square)](https://packagist.org/packages/dennisbusk/debug-notary)
[![License](https://img.shields.io/packagist/l/dennisbusk/debug-notary.svg?style=flat-square)](https://packagist.org/packages/dennisbusk/debug-notary)

`Debug Notary` is a powerful Laravel package designed to make debugging and user reporting effortless. It automatically captures system logs, tracks JavaScript errors, and provides a visual "Notary" button for users to report bugs with
screenshots, annotations, and browser data directly from your application.

---

### 🚀 Features

* **Automatic Log Collection:** Listens to Laravel's log events and stores them in your database.
* **JavaScript Error Tracking:** Automatically captures frontend errors (`window.onerror`, `window.onunhandledrejection`) and console errors with full stack traces.
* **Visual Notary Button:** A discrete button injected into your application, allowing users to submit bug reports with screenshots (using [marker.js](https://markerjs.com/)), notes, and metadata.
* **Intelligent Dashboard:** A comprehensive Livewire-powered dashboard with trend statistics, status management (Open, In Progress, Resolved), and advanced filtering.
* **Multi-Channel Notifications:** Get alerted via Slack or Email when new unique errors occur, featuring built-in rate limiting and queue support.
* **Screenshot Management:** Support for storing screenshots as files on disk (public disk) or as Base64 strings in the database.
* **Data Masking:** Automatically redacts sensitive information like passwords, tokens, and API keys for GDPR compliance and security.
* **User & Tenant Context:** Automatically associates logs with authenticated users and their roles (compatible with Spatie Permissions).
* **Markdown & LLM Export:** Generate structured reports optimized for GitHub issues or AI analysis with a single click.
* **Auto-Cleanup:** Automatically prunes old logs using Laravel's `Prunable` trait.
* **Localization:** Built-in support for English and Danish.

---

### 📋 Requirements

* **PHP:** ^8.2
* **Laravel:** ^10.0, ^11.0, or ^12.0
* **Livewire:** ^3.0 or ^4.0

---

### 📦 Installation

Install the package via composer:

```bash
composer require dennisbusk/debug-notary
```

#### 1. Run Migrations

The package requires a table to store recorded bugs:

```bash
php artisan migrate
```

#### 2. Publish Configuration (Optional)

To customize the default settings, publish the configuration file:

```bash
php artisan vendor:publish --tag="debug-notary-config"
```

#### 3. Middleware

The package automatically injects the Notary button via a global middleware. If you've disabled automatic loading or need manual control, add the middleware to your `web` group:

```php
// app/Http/Kernel.php or bootstrap/app.php (Laravel 11+)
'web' => [
    // ...
    \Dennisbusk\DebugNotary\Http\Middleware\InjectNotaryButton::class,
],
```

#### 4. Test Your Setup

Verify that notifications and basic logging are working correctly:

```bash
php artisan debug-notary:test
```

---

### ⚙️ Configuration

The configuration file `config/debug-notary.php` allows you to fine-tune the package:

* `enabled`: Master toggle for the package.
* `debug_level`: Minimum log level to capture (default: `error`).
* `system_log`: Capture standard Laravel logs.
* `notary_log`: Enable/disable the manual reporting button.
* `console_log`: Automatically capture JS console errors.
* `access_gate`: Define a Gate to control who sees the button and dashboard.
* `notifications`: Configure Slack webhooks, Email recipients, and rate limiting.
* `masking.fields`: List of fields to redact from logs and context.

#### Access Control

To restrict access to the Debug Notary dashboard or button, define a gate in your `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('view-debug-notary', function ($user) {
    return $user->is_admin;
});
```

Then, update your `config/debug-notary.php`:

```php
'access_gate' => 'view-debug-notary',
```

---

### 🛠 Usage

#### Manual Logging via Facade

Capture specific events manually in your PHP code:

```php
use Dennisbusk\DebugNotary\Facades\DebugNotary;

DebugNotary::warning('User attempted to access a locked resource', [
    'resource_id' => 123
]);
```

#### Dashboard

Access the dashboard at `/laravel-debug-notary`.

The dashboard uses **Tailwind CSS**. If you are using a custom layout, ensure Tailwind is available. The default layout loads Tailwind via CDN for convenience.

#### Localization

To customize the text or add new languages, publish the language files:

```bash
php artisan vendor:publish --tag="debug-notary-lang"
```

---

### 🖥 Advanced Route Registration

By default, routes are registered automatically. To manually control route registration (e.g., to apply specific middleware), disable automatic registration in `config/debug-notary.php`:

```php
'register_routes' => false,
```

Then, register them in your `routes/web.php`:

```php
use Dennisbusk\DebugNotary\DebugNotary;

Route::middleware(['auth', 'can:admin'])->group(function () {
    DebugNotary::routes();
});
```

---

### 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

### 🤝 Contributing

Suggestions for improvements or bug reports? Open an issue or submit a pull request!

---

*Created with ❤️ by [Dennis Busk](https://github.com/dennisbusk)*
