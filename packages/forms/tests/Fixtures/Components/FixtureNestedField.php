<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components;

use Sylament\Forms\Components\Concerns\HasNestedRecursiveValidationConstraints;
use Sylament\Forms\Components\Contracts;
use Sylament\Forms\Components\Field;

final class FixtureNestedField extends Field implements Contracts\HasNestedRecursiveValidationConstraints
{
    use HasNestedRecursiveValidationConstraints;
}
