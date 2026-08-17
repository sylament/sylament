<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State\Casts;

enum CastStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
