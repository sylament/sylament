<?php

declare(strict_types=1);

namespace Sylament\Support\Enum;

enum Alignment: string
{
    case Start = 'start';

    case Left = 'left';

    case Center = 'center';

    case End = 'end';

    case Right = 'right';

    case Justify = 'justify';

    case Between = 'between';
}
