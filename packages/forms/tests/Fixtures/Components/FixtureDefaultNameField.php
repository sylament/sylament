<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components;

use Sylament\Forms\Components\Field;

/**
 * A field whose class supplies its own name, so `make()` can be called bare.
 */
final class FixtureDefaultNameField extends Field
{
    public static function getDefaultName(): string
    {
        return 'defaultNamed';
    }
}
