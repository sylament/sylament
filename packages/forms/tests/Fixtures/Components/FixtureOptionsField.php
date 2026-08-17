<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components;

use Sylament\Forms\Components\Concerns\CanAllowHtml;
use Sylament\Forms\Components\Concerns\CanDisableOptions;
use Sylament\Forms\Components\Concerns\CanSelectPlaceholder;
use Sylament\Forms\Components\Concerns\HasDescriptions;
use Sylament\Forms\Components\Concerns\HasGridDirection;
use Sylament\Forms\Components\Concerns\HasOptions;
use Sylament\Forms\Components\Contracts;
use Sylament\Forms\Components\Field;

final class FixtureOptionsField extends Field implements Contracts\CanDisableOptions
{
    use CanAllowHtml;
    use CanDisableOptions;
    use CanSelectPlaceholder;
    use HasDescriptions;
    use HasGridDirection;
    use HasOptions;
}
