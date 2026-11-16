<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\TextInput;

/**
 * A text input that names itself when `make()` is given no name.
 */
class DefaultNamedField extends TextInput
{
    public static function getDefaultName(): ?string
    {
        return 'nickname';
    }
}
