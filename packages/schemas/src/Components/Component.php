<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Sylament\Schemas\Components\Concerns\BelongsToContainer;
use Sylament\Schemas\Components\Concerns\HasChildComponents;
use Sylament\Schemas\Components\Concerns\HasColumns;
use Sylament\Schemas\Components\Concerns\HasGap;
use Sylament\Support\Component\ViewComponent;

class Component extends ViewComponent
{
    use BelongsToContainer;
    use HasChildComponents;
    use HasColumns;
    use HasGap;

    protected string $evaluationIdentifier = 'component';
}
