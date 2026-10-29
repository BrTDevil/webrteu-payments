<?php

declare(strict_types=1);

namespace WeBRTeu\Payments\Enums;

enum RedirectType: string
{
    /** GET pe URL. */
    case Redirect = 'redirect';

    /** POST cu câmpuri ascunse, trimis automat. */
    case Form = 'form';
}
