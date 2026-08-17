<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components;

use Sylament\Forms\Components\Concerns\CanAllowHtml;
use Sylament\Forms\Components\Concerns\CanBeSearchable;
use Sylament\Forms\Components\Concerns\CanDisableOptions;
use Sylament\Forms\Components\Concerns\CanSelectPlaceholder;
use Sylament\Forms\Components\Concerns\HasColors;
use Sylament\Forms\Components\Concerns\HasDescriptions;
use Sylament\Forms\Components\Concerns\HasGridDirection;
use Sylament\Forms\Components\Concerns\HasIcons;
use Sylament\Forms\Components\Concerns\HasOptions;
use Sylament\Forms\Components\Concerns\HasTooltips;
use Sylament\Forms\Components\Contracts;
use Sylament\Forms\Components\Field;

final class FixtureOptionsField extends Field implements Contracts\CanDisableOptions
{
    use CanAllowHtml;
    use CanBeSearchable;
    use CanDisableOptions;
    use CanSelectPlaceholder;
    use HasColors;
    use HasDescriptions;
    use HasGridDirection;
    use HasIcons;
    use HasOptions;
    use HasTooltips;
}
