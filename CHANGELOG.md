# Changelog

Toate modificările notabile ale acestui pachet sunt documentate aici.
Formatul urmează [Keep a Changelog](https://keepachangelog.com/ro/1.1.0/), iar versiunile [SemVer](https://semver.org/lang/ro/).

## [Unreleased]

### Adăugat
- Contractele `Gateway`, `Refundable`, `Capturable`, `ConfirmsWebhooks`.
- DTO-uri readonly pentru cereri de plată, clienți, produse, redirect, webhook, status, rambursare.
- `PaymentManager` + facade `Payments` (`gateway()`, `extend()`, `start()`).
- `Money`, `LengthPrefixedHmac` (EuPlatesc/LibraPay), `StatusTransition` (statusul nu coboară).
- Ruta de webhook `POST /webhooks/payments/{gateway}`, idempotentă, fără CSRF.
- Job-ul `ProcessPaymentWebhook`, evenimentul `PaymentStatusChanged`, comanda `payments:reconcile`.
- Componenta Blade `<x-payments::auto-submit-form>`.
