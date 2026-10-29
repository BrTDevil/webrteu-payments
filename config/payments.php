<?php

declare(strict_types=1);

return [

    /*
    | Procesatorul folosit de Payments::gateway() fără argument.
    */
    'default' => env('PAYMENTS_GATEWAY', 'netopia'),

    /*
    | Ruta de webhook: POST /{prefix}/{gateway}, în afara grupului `web` (fără CSRF).
    */
    'route' => [
        'enabled' => true,
        'prefix' => env('PAYMENTS_WEBHOOK_PREFIX', 'webhooks/payments'),
        'middleware' => [],
    ],

    /*
    | Coada pentru ProcessPaymentWebhook (null = coada implicită).
    */
    'queue' => env('PAYMENTS_QUEUE'),

    'reconcile' => [
        'older_than_minutes' => 15,
        'max_age_hours' => 48,
    ],

    /*
    | Salvează corpul brut al notificărilor în payment_events (util la depanare).
    */
    'store_raw_body' => true,

    'tables' => [
        'payments' => 'payments',
        'payment_events' => 'payment_events',
    ],

];
