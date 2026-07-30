<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components;

use Sylament\Forms\Components\Concerns\CanBeAccepted;
use Sylament\Forms\Components\Concerns\CanBeInline;
use Sylament\Forms\Components\Field;

final class FixtureBooleanField extends Field
{
    use CanBeAccepted;
    use CanBeInline;
}
