### Debug Notary - Laravel Debugging & User Reporting

`Debug Notary` is a powerful Laravel package designed to make debugging and user reporting effortless. The package automatically captures your system logs and provides your users (or yourself) with a visual "Notary" button to manually
report bugs with screenshots and browser data directly from the interface.

---

### 🚀 Features

* **Automatic Log Collection:** Listens to Laravel's log events and stores them in the database.
* **JavaScript Error Tracking:** Automatically captures frontend errors (`window.onerror` and `window.onunhandledrejection`) and console errors, logging them with full stack traces.
* **Visual Notary Button:** A discrete button injected into the bottom of your application, allowing users to submit bug reports manually with screenshots, notes, and browser metadata.
* **Intelligent Dashboard:** Full overview of errors with trend statistics (CSS bars), status management (Open, In Progress, Resolved), and advanced filtering by severity, type, and status.
* **Notifications:** Get notified via Slack or Email when new unique errors are recorded, with built-in rate limiting and queue support.
* **Screenshot Management:** Choose between storing screenshots as files on disk or as Base64 strings directly in the database.
* **Flexible Layouts:** Use the built-in dashboard layout or specify your own to match your application's design.
* **Data Masking:** Automatically masks sensitive information like passwords, tokens, and API keys for GDPR compliance and security.
* **User & Tenant Context:** Automatically associates errors with the authenticated user and tenant ID if available.
* **Markdown Export:** Generate structured reports optimized for LLMs or GitHub issues with one click.
* **Auto-Cleanup:** Automatically prunes old logs after a configurable amount of days using Laravel's Prunable trait.

---

### 📦 Installation

You can install the package via composer:

```bash
composer require dennisbusk/debug-notary
```

#### 1. Run Migrations

The package requires a table to store the recorded bugs:

```bash
php artisan migrate
```

#### 2. Publish Configuration (Optional)

If you want to customize the default settings, you can publish the configuration file:

```bash
php artisan vendor:publish --tag="debug-notary-config"
```

#### 3. Middleware

The package automatically attempts to inject the Notary button via a global middleware. If you have disabled automatic loading or need manual control, you can add `Dennisbusk\DebugNotary\Http\Middleware\InjectNotaryButton` to your `web`
middleware group in `app/Http/Kernel.php`.

#### 4. Test Configuration

Verify your Slack and Email notifications are working correctly:

```bash
php artisan debug-notary:test
```

---

### ⚙️ Configuration

After publishing, you will find the configuration in `config/debug-notary.php`. Key settings include:

* `enabled`: Enable or disable the entire package.
* `debug_level`: The minimum log level to record (e.g., `error`, `critical`, `warning`).
* `system_log`: Should standard Laravel logs be captured automatically?
* `notary_log`: Should the manual Notary button be shown?
* `console_log`: Automatically capture JavaScript console errors?
* `screenshot_storage`: How to store screenshots (`base64`, `file`, or `both`).
* `layout`: Custom blade layout for the dashboard (defaults to package layout).
* `register_routes`: Toggle automatic route registration.
* `access_gate`: Define which Gate controls access to the dashboard and button.
* `notifications.enabled`: Toggle notifications on/off.
* `notifications.slack_webhook`: Your Slack Incoming Webhook URL.
* `notifications.mail_to`: Destination email address for bug reports.
* `notifications.queue`: Set to `true` to send notifications via your background queue.
* `notifications.rate_limit`: Minutes to wait before sending a notification for the same error again.
* `prune_days`: How many days to keep logs before auto-deleting.
* `masking.fields`: List of fields to redact from logs and context.

---

### 🛠 Usage

#### Automatic Logging

Once active, no extra steps are required. Standard Laravel logging will be captured automatically:

```php
use Illuminate\Support\Facades\Log;

Log::error('Something went wrong in the payment flow!');
```

#### JavaScript Error Tracking

The package automatically listens for JavaScript errors. No configuration is needed if the Notary button is injected. It captures:

- Uncaught Exceptions (`window.onerror`)
- Unhandled Promise Rejections (`window.onunhandledrejection`)

#### Manual Logging via Facade

You can also use the `DebugNotary` facade directly to store specific notarizations:

```php
use Dennisbusk\DebugNotary\Facades\DebugNotary;

DebugNotary::warning('User attempted to access a locked resource', [
    'extra_info' => 'This is additional context'
]);
```

#### Dashboard

You can access your Debug Notary dashboard at the following route:
`your-app.test/laravel-debug-notary`

From the dashboard, you can:

* **Trend View:** See CSS-bar graphs of error frequency over the last 7 days.
* **Status Management:** Track progress by marking errors as Open, In Progress, or Resolved.
* **Smart Search:** Search through error messages, files, and user notes.
* **Advanced Filtering:** Filter by Severity, Log Type (System, Notary, JS), and Status.
* **Markdown Export:** Copy a full error report to your clipboard for use in GitHub or AI tools.
* **Details:** View screenshots, browser context, and full stack traces.
* **Clean-up:** Delete errors individually or in bulk.

---

### 🖥 Advanced Route Registration

If you want full control over where the routes are placed (e.g., behind specific authentication middleware), you can disable automatic route loading and call the following in your `RouteServiceProvider` or `web.php`:

```php
use Dennisbusk\DebugNotary\DebugNotary;

Route::middleware(['auth', 'can:admin'])->group(function () {
    DebugNotary::routes();
});
```

---

### 📄 License

This package is open-source software licensed under the MIT license. See the `LICENSE` file for more details.

---

### 🤝 Contributing

Suggestions for improvements or bug reports? Open an issue or submit a pull request on GitHub!

---

*Created with ❤️ by Dennis Busk*
