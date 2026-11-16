<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

enum StringBackedLetter: string
{
    case A = 'a';
    case B = 'b';
    case C = 'c';
}
