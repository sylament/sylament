<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Sylament\Schemas\Components\Concerns\BelongsToContainer;
use Sylament\Schemas\Components\Concerns\HasChildComponents;
use Sylament\Schemas\Concerns\HasColumns;
use Sylament\Schemas\Concerns\HasGap;
use Sylament\Schemas\Components\Concerns\HasHeadings;
use Sylament\Schemas\Components\Concerns\HasId;
use Sylament\Schemas\Components\Concerns\HasKey;
use Sylament\Support\Component\Concerns\CanGrow;
use Sylament\Support\Component\Concerns\CanOrderColumns;
use Sylament\Support\Component\Concerns\CanSpanColumns;
use Sylament\Support\Component\Concerns\HasExtraAttributes;
use Sylament\Support\Component\ViewComponent;

class Component extends ViewComponent
{
    use BelongsToContainer;
    use CanGrow;
    use CanOrderColumns;
    use CanSpanColumns;
    use HasChildComponents;
    use HasColumns;
    use HasExtraAttributes;
    use HasGap;
    use HasHeadings;
    use HasId;
    use HasKey;

    protected string $evaluationIdentifier = 'component';
}
