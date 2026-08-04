<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components;

use Sylament\Forms\Components\Concerns\CanBeCloned;
use Sylament\Forms\Components\Concerns\CanGenerateUuids;
use Sylament\Forms\Components\Concerns\CanLimitItemsLength;
use Sylament\Forms\Components\Field;

final class FixtureItemsField extends Field
{
    use CanBeCloned;
    use CanGenerateUuids;
    use CanLimitItemsLength;
}
