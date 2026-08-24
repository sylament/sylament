<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

enum ValidationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
