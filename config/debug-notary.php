<?php

return [
    'enabled' => env('DEBUG_NOTARY_ENABLED', true),

    /*
     * Minimum debug level til at registrere bugs.
     * Muligheder: debug, info, notice, warning, error, critical, alert, emergency
     */
    'debug_level' => env('DEBUG_NOTARY_LEVEL', 'error'),

    /*
     * Skal almindelige Laravel system logs gemmes i Debug Notary?
     */
    'system_log' => env('DEBUG_NOTARY_SYSTEM_LOG', true),

    /*
     * Skal Notary knappen vises og manuelle logs gemmes?
     */
    'notary_log' => env('DEBUG_NOTARY_LOG', true),

    /*
     * Hvilket layout skal bruges til Debug Notary oversigten?
     * Hvis den efterlades som null, bruges pakkens eget standard layout.
     */
    'layout' => env('DEBUG_NOTARY_LAYOUT', null),

    /*
     * Hvordan skal skærmbilleder gemmes?
     * 'file': Gemmes som fil på public disk.
     * 'base64': Gemmes direkte i databasen som base64 streng.
     * 'both': Gemmer både fil og i database.
     */
    'screenshot_storage' => env('DEBUG_NOTARY_SCREENSHOT_STORAGE', 'base64'),

    /*
     * Skal ruter registreres automatisk?
     * Hvis sat til false, skal man selv kalde DebugNotary::routes() i f.eks. web.php.
     */
    'register_routes' => env('DEBUG_NOTARY_REGISTER_ROUTES', true),

    /*
     * Gate-navn eller callback til at kontrollere hvem der kan se Notary-knappen.
     * Hvis den efterlades som null, vises den for alle hvis 'enabled' er true.
     */
    'access_gate' => env('DEBUG_NOTARY_ACCESS_GATE', null),

    /*
     * Hvor mange dage skal logs gemmes før de slettes via prunable trait?
     */
    'prune_days' => env('DEBUG_NOTARY_PRUNE_DAYS', 30),

    /*
     * Notifikationsindstillinger
     */
    'notifications' => [
        'enabled' => env('DEBUG_NOTARY_NOTIFICATIONS', false),
        'slack_webhook' => env('DEBUG_NOTARY_SLACK_WEBHOOK'),
        'mail_to' => env('DEBUG_NOTARY_MAIL_TO'),

        /*
         * Skal notifikationer sendes asynkront via køen (queue)?
         */
        'queue' => env('DEBUG_NOTARY_NOTIFICATIONS_QUEUE', false),

        /*
         * Hvor mange minutter skal der gå mellem notifikationer for den samme unikke fejl?
         * Sæt til 0 for at sende hver gang.
         */
        'rate_limit' => env('DEBUG_NOTARY_NOTIFICATIONS_RATE_LIMIT', 60),
    ],

    /*
     * Data Masking (Sikkerhed & GDPR)
     * Her kan du definere felter der skal maskeres i context/browser_data.
     */
    'masking' => [
        'enabled' => env('DEBUG_NOTARY_MASKING_ENABLED', true),
        'fields' => [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'cookie',
            'authorization',
            'php-auth-pw',
            'surfer_token',
        ],
    ],
];
