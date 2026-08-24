<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\CanStripCharactersFromState;
use Sylament\Schemas\Components\Concerns\CanTrimState;

/**
 * A state-bearing leaf composing both mutation concerns, standing in for the
 * `TextInput` forms ships them on: it applies them in the same order that field
 * does and exposes the two protected mutators so a unit test can drive them
 * without a live host.
 */
class MutatingField extends Component
{
    use CanStripCharactersFromState;
    use CanTrimState;

    public static function make(string $name): static
    {
        return new static()->statePath($name);
    }

    public function mutateStateForValidation(mixed $state): mixed
    {
        return parent::mutateStateForValidation($this->trimState($this->stripCharactersFromState($state)));
    }

    public function mutatesStateForValidation(): bool
    {
        return parent::mutatesStateForValidation() || $this->hasStripCharacters() || $this->isTrimmed();
    }

    public function exposeTrimState(mixed $state): mixed
    {
        return $this->trimState($state);
    }

    public function exposeStripCharactersFromState(mixed $state): mixed
    {
        return $this->stripCharactersFromState($state);
    }
}
