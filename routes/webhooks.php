<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use WeBRTeu\Payments\Http\Controllers\WebhookController;

Route::post('{gateway}', WebhookController::class)->name('payments.webhook');
