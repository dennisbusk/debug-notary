# 🐞 Debug Notary

![Debug Notary Demo](https://raw.githubusercontent.com/dennisbusk/debug-notary/main/art/demo.gif)

> **Point-and-click bug reporting:** See how users can annotate screenshots and submit bugs directly from your application.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dennisbusk/debug-notary.svg?style=flat-square)](https://packagist.org/packages/dennisbusk/debug-notary)
[![Total Downloads](https://img.shields.io/packagist/dt/dennisbusk/debug-notary.svg?style=flat-square)](https://packagist.org/packages/dennisbusk/debug-notary)
[![License](https://img.shields.io/packagist/l/dennisbusk/debug-notary.svg?style=flat-square)](https://packagist.org/packages/dennisbusk/debug-notary)

`Debug Notary` is a powerful Laravel package designed to make debugging and user reporting effortless. It automatically captures system logs, tracks JavaScript errors, and provides a visual "Notary" button for users to report bugs with
screenshots, annotations, and browser data directly from your application.

---

### ❓ Why Debug Notary?

While tools like **Laravel Pulse** focus on *Application Performance Monitoring (APM)* (server health, slow queries, CPU usage), **Debug Notary** is a *Bug Reporting & Error Tracking* tool. It bridges the gap between the user's visual
experience and the developer's technical requirements.

 Feature                 | Debug Notary                | Laravel Pulse       |
 -------------------------|-----------------------------|---------------------|
 **User Interaction**    | ✅ Point-and-click reporting | ❌ No manual reports |
 **Visual Context**      | ✅ Annotated Screenshots     | ❌ No visuals        |
 **Frontend Monitoring** | ✅ JS Errors & Console Logs  | ❌ No                |
 **AI Integration**      | ✅ LLM-optimized exports     | ❌ No                |
 **System Metrics**      | ❌ (CPU/RAM/SQL)             | ✅ Yes               |

---

### 🚀 Features

* **Automatic Log Collection:** Listens to Laravel's log events and stores them in your database.
* **JavaScript Error Tracking:** Automatically captures frontend errors (`window.onerror`, `window.onunhandledrejection`) and console errors with full stack traces.
* **Smart De-duplication:** Automatically groups similar errors by normalizing messages (redacting IDs, UUIDs, etc.) before hashing.
* **Visual Notary Button:** A discrete button injected into your application, allowing users to submit bug reports with screenshots (using [marker.js](https://marker.js.com/)), notes, and attachments.
* **Intelligent Dashboard:** A comprehensive Livewire-powered dashboard with dark mode support, trend statistics, status management (Open, In Progress, Resolved), user assignment, and advanced filtering.
* **Customizable List View:** Tailor the bug overview by toggling visibility of specific columns (Type, Status, Severity, User, Role, etc.) via configuration.
* **GDPR Ready:** Built-in "Pixelate" tool for screenshots to blur sensitive data, plus advanced data masking for context logs.
* **Multi-Channel Notifications:** Get alerted via Slack or Email when new unique errors occur, featuring built-in rate limiting and queue support.
* **Screenshot & File Management:** Support for storing screenshots and attachments as files on disk or as Base64 strings.
* **User & Tenant Context:** Automatically associates logs with authenticated users and their roles (compatible with Spatie Permissions).
* **Markdown & LLM Export:** Generate structured reports optimized for GitHub issues or AI analysis with a single click.
* **Auto-Cleanup:** Automatically prunes old logs using Laravel's `Prunable` trait, with configurable retention periods per log type.
* **Localization:** Built-in support for English and Danish.

---

### 📖 Recipes & Best Practices

#### Log Cart Contents during Error

When an error occurs in your checkout flow, it's invaluable to see what was in the user's cart.

```php
use Dennisbusk\DebugNotary\Facades\DebugNotary;

try {
    // Some logic that might fail
} catch (\Exception $e) {
    DebugNotary::error($e->getMessage(), [
        'cart' => $cart->toArray(),
        'checkout_step' => 'payment',
        'coupon_code' => $session->get('coupon')
    ]);
    throw $e;
}
```

#### Attach Debug Logs manually

If you have a log file or a JSON export of the current state, users can attach it directly via the Notary button in the frontend.

#### Capture Auth State

Capture the current user's permissions to see if a bug is related to access rights.

```php
DebugNotary::info('User accessed premium feature', [
    'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
    'active_plan' => $user->plan_id
]);
```

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
* `route_prefix`: The URL path for the dashboard (default: `laravel-debug-notary`).
* `debug_level`: Minimum log level to capture (default: `error`).
* `system_log`: Capture standard Laravel logs.
* `notary_log`: Enable/disable the manual reporting button.
* `console_log`: Automatically capture JS console errors.
* `layout`: Custom blade layout for the dashboard (optional).
* `screenshot_storage`: Storage method for screenshots (`file`, `base64`, or `both`).
* `register_routes`: Toggle automatic route registration.
* `access_gate`: Define a Gate to control who sees the button and dashboard.
* `prune_days`: Number of days to keep logs before auto-deletion (per log type).
* `notifications`: Configure Slack webhooks, Email recipients, and rate limiting.
* `impersonate`: Configuration for the "Log in as user" feature.
* `list_view.columns`: Toggle visibility of specific columns in the bug list view (Type, Status, Severity, User, etc.).
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

### 🔒 Security & GDPR

Debug Notary is built with data protection in mind. For a full overview, please see our [SECURITY.md](SECURITY.md) file.

* **Pixelate/Blur Tool:** Users can manually blur sensitive information on screenshots before submitting.
* **Data Masking:** Sensitive fields (passwords, tokens, etc.) are automatically redacted.
* **Auto-Pruning:** Configurable retention periods ensure you don't keep data longer than necessary.

---

### 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

### 🤝 Contributing

Suggestions for improvements or bug reports? Open an issue or submit a pull request!

---

*Created with ❤️ by [Dennis Busk](https://github.com/dennisbusk)*
