<?php

declare(strict_types=1);
use Illuminate\Database\Eloquent\Model;
use WeBRTeu\Payments\Exceptions\PaymentException;

arch('nu folosește funcții de debug')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed();

arch('toate fișierele au strict types')
    ->expect('WeBRTeu\Payments')
    ->toUseStrictTypes();

arch('DTO-urile sunt final readonly')
    ->expect('WeBRTeu\Payments\Data')
    ->toBeFinal()
    ->toBeReadonly();

arch('contractele sunt interfețe')
    ->expect('WeBRTeu\Payments\Contracts')
    ->toBeInterfaces();

arch('enum-urile sunt backed enums')
    ->expect('WeBRTeu\Payments\Enums')
    ->toBeStringBackedEnums();

arch('modelele extind Eloquent Model')
    ->expect('WeBRTeu\Payments\Models')
    ->toExtend(Model::class);

arch('excepțiile extind PaymentException')
    ->expect('WeBRTeu\Payments\Exceptions')
    ->toExtend(PaymentException::class)
    ->ignoring(PaymentException::class);

arch('fără Guzzle direct')
    ->expect('WeBRTeu\Payments')
    ->not->toUse('GuzzleHttp');
