<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\TextInput;

/**
 * A text input that describes its requirement to a screen reader, as a field overriding
 * `getRequiredDescription()` does.
 */
class RequiredDescribedField extends TextInput
{
    public function getRequiredDescription(): ?string
    {
        return 'Pick at least one.';
    }
}
