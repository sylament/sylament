<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components;

use Sylament\Forms\Components\Concerns\CanBeLengthConstrained;
use Sylament\Forms\Components\Contracts;
use Sylament\Forms\Components\Field;

final class FixtureLengthField extends Field implements Contracts\CanBeLengthConstrained
{
    use CanBeLengthConstrained;
}
