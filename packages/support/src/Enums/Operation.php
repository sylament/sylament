<?php

declare(strict_types=1);

namespace Sylament\Support\Enums;

enum Operation: string
{
    case Create = 'create';

    case Edit = 'edit';

    case View = 'view';
}
