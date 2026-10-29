# webrteu/payments

[![PHP](https://img.shields.io/badge/PHP-%5E8.2-777bb4)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-11%E2%80%9313-ff2d20)](https://laravel.com/)
[![Licență MIT](https://img.shields.io/badge/licen%C8%9B%C4%83-MIT-green)](LICENSE.md)
[![Packagist](https://img.shields.io/packagist/v/webrteu/payments)](https://packagist.org/packages/webrteu/payments)

Nucleul comun pentru procesatorii de plăți din România în Laravel: contract unic (`Gateway`), tabelă de plăți,
rută de webhook idempotentă, statusuri care nu coboară niciodată și reconciliere pentru notificările pierdute.
Procesatorii vin în pachete separate (`webrteu/netopia`, `webrteu/payu`, `webrteu/euplatesc`, `webrteu/btipay`,
`webrteu/librapay`, `webrteu/revolut`).

Nu este un pachet oficial al vreunui procesator de plăți.

## Cerințe

- PHP `^8.2`
- Laravel 11, 12 sau 13
- o coadă (queue) configurată pentru procesarea webhook-urilor (`sync` merge, dar nu e recomandat în producție)
- cel puțin un pachet de procesator instalat

## Instalare

```bash
composer require webrteu/payments
php artisan vendor:publish --tag=payments-config
php artisan vendor:publish --tag=payments-migrations
php artisan migrate
```

Opțional, pentru personalizarea paginii de redirecționare: `php artisan vendor:publish --tag=payments-views`.

## Configurare

```dotenv
# Procesatorul implicit pentru Payments::gateway() fără argument
PAYMENTS_GATEWAY=netopia
# Prefixul rutei de webhook: POST /webhooks/payments/{gateway}
PAYMENTS_WEBHOOK_PREFIX=webhooks/payments
# Coada pentru ProcessPaymentWebhook (gol = coada implicită)
PAYMENTS_QUEUE=
```

## Utilizare

```php
use WeBRTeu\Payments\Data\CustomerData;
use WeBRTeu\Payments\Data\PaymentRequestData;
use WeBRTeu\Payments\Facades\Payments;

$redirect = Payments::start('netopia', new PaymentRequestData(
    orderId: $order->number.'-'.$attempt,   // unic per încercare
    amount: 14170,                           // 141,70 lei, mereu în bani
    description: 'Comanda '.$order->number,
    customer: new CustomerData('ion@example.com', 'Ion', 'Popescu', phone: '0721000000'),
    returnUrl: route('checkout.return', $order),
), payable: $order);

return $redirect->toResponse(); // redirect GET sau formular POST trimis automat
```

`start()` creează rândul `payments` cu status `Pending`, apelează procesatorul și salvează `provider_payment_id`.

Pentru procesatorii cu formular POST poți folosi și componenta direct în propriul view:

```blade
<x-payments::auto-submit-form :redirect="$redirect" />
```

Metode pe orice procesator (`Payments::gateway('netopia')`): `createPayment()`, `parseWebhook()`,
`webhookResponse()`, `status($providerPaymentId)`. Opțional, după API: `Refundable::refund($id, $bani)`,
`Capturable::capture($id, $bani)` / `void($id)`, `ConfirmsWebhooks::confirm($webhook)`.

**Pagina de return nu marchează niciodată comanda ca plătită.** Afișează „plata se procesează”. Sursa adevărului e webhook-ul.

### Statusuri

`Pending`, `Authorized`, `Paid`, `Failed`, `Cancelled`, `PartiallyRefunded`, `Refunded`, `Unknown`.
Statusul nu coboară: `Paid` nu mai devine `Pending`, chiar dacă notificările vin în altă ordine. `Failed` poate deveni
`Paid` (a doua încercare reușită), iar `Unknown` nu suprascrie nimic.

## Webhook

URL-ul de dat procesatorului: `https://site-ul-tau.ro/webhooks/payments/<gateway>` (ex. `/webhooks/payments/netopia`).

Ruta e înregistrată în afara grupului `web`, deci CSRF nu se aplică. Dacă aplicația ta pune totuși toate rutele sub `web`:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['webhooks/payments/*']);
})
```

Fluxul: semnătura se verifică pe corpul brut → notificarea se salvează în `payment_events` (o singură dată per
`event_id`) → `ProcessPaymentWebhook` pe coadă → verificare sumă → tranziție de status → `PaymentStatusChanged`.
Header-ele `Authorization` și `Cookie` nu se salvează.

```php
use WeBRTeu\Payments\Events\PaymentStatusChanged;

Event::listen(function (PaymentStatusChanged $event) {
    if ($event->to->isSuccessful() && ! $event->from->isSuccessful()) {
        $event->payment->payable?->markAsPaid();   // + emiterea facturii, ex. cu webrteu/invoicing
    }
});
```

## Comenzi Artisan

| Comandă | Ce face |
|---|---|
| `payments:reconcile` | interoghează `status()` pentru plățile `Pending`/`Authorized` mai vechi de 15 minute și mai noi de 48 de ore |
| `payments:reconcile --gateway=netopia --older-than=30` | filtrare pe procesator / vechime |

```php
// routes/console.php
Schedule::command('payments:reconcile')->everyTenMinutes()->withoutOverlapping();
```

## Ce nu face pachetul

- nu vorbește direct cu niciun procesator (asta fac pachetele de provider)
- nu face retry pe crearea plăților (risc de dublă încasare)
- nu marchează comenzile ca plătite. Asta face aplicația ta, ascultând `PaymentStatusChanged`

## Testare

```bash
composer test
```

Rulează lint (pint + rector), spelling (peck), type-coverage 100%, phpstan nivel 8, teste de arhitectură și Pest
cu coverage 100% (are nevoie de Xdebug sau PCOV).

## Changelog

Vezi [CHANGELOG.md](CHANGELOG.md).

## Autor

[WeBRTeu](https://webrteu.eu)

## Licență

MIT. Vezi [LICENSE.md](LICENSE.md).
