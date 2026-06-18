# Security and GDPR

Security and data protection are crucial when logging errors in applications handling sensitive personal information. Here is how Debug Notary helps you stay secure and comply with GDPR.

## 🛡️ Data Masking

Debug Notary has built-in support for masking sensitive fields. By default, fields like `password`, `token`, `api_key`, and `authorization` are masked.

You can configure which fields to mask in `config/debug-notary.php`:

```php
'masking' => [
    'enabled' => true,
    'fields' => [
        'password',
        'token',
        'credit_card_number',
        'social_security_number',
    ],
],
```

When data is masked, the value is replaced with `********` before being stored in the database.

## 🖍️ Screenshot Annotation (Blur/Pixelate)

When users report bugs manually via the Notary button, they can use the **Pixelate** tool in the built-in editor to blur sensitive information (names, addresses, prices) directly in the browser before the image is sent to the server.

We recommend instructing users to always blur personal data before submitting a report.

## 💾 Storage Security

Screenshots can be stored in two ways:

1. **Base64 (Default):** The image is stored directly in the database as a string. This is easy to set up but can make your database large.
2. **File:** The image is stored as a file on your `public` disk. Ensure your webserver is correctly configured so only authorized users have access to the `/storage/debug-notary` folder if you store sensitive data.

## 🧹 Automatic Pruning (Deletion)

To comply with the data minimization principle in GDPR, Debug Notary automatically deletes old errors after a configurable number of days.

```php
'prune_days' => [
    'system' => 7,      // Regular logs are deleted quickly
    'notary' => 90,     // Manual user reports are kept longer
    'javascript' => 14, // JS errors are kept for 2 weeks
],
```

Ensure you have `php artisan model:prune` running in your scheduler.

## 🚦 Access Control

Use the `access_gate` in the configuration to restrict who can see the Notary button and the dashboard. It should never be accessible to unauthorized users.
