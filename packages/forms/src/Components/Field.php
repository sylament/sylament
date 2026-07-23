<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Sylament\Forms\Components\Concerns\CanBeAutofocused;
use Sylament\Forms\Components\Concerns\CanBeMarkedAsRequired;
use Sylament\Forms\Components\Concerns\HasHelperText;
use Sylament\Forms\Components\Concerns\HasHint;
use Sylament\Forms\Components\Concerns\HasPlaceholder;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\HasLabel;
use Sylament\Schemas\Components\Concerns\HasName;
use Twig\Markup;

use function Sylament\Support\filled;
use function Symfony\Component\String\u;

abstract class Field extends Component
{
    use CanBeAutofocused;
    use CanBeMarkedAsRequired;
    use HasHelperText;
    use HasHint;
    use HasLabel {
        getLabel as getBaseLabel;
    }
    use HasName;
    use HasPlaceholder;

    final public function __construct(?string $name = null)
    {
        $this->name($name);
        $this->statePath($name);
    }

    public static function make(?string $name = null): static
    {
        $field = new static($name);

        $field->configure();

        return $field;
    }

    public function getLabel(): string|Markup|null
    {
        if (filled($label = $this->getBaseLabel())) {
            return $label;
        }

        return $this->getDefaultLabel();
    }

    protected function getDefaultLabel(): ?string
    {
        $name = $this->getName();

        if ($name === null) {
            return null;
        }

        return (string) u($name)->afterLast('.')->kebab()->replace('-', ' ')->title();
    }

    /**
     * The validation error messages the field wrapper renders. Empty until the
     * validation surface (`CanBeValidated`) is ported; the wrapper renders its
     * error region only when this is non-empty.
     *
     * @return array<string>
     */
    public function getErrorMessages(): array
    {
        return [];
    }
}
