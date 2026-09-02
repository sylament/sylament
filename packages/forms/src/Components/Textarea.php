<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Sylament\Forms\Components\Concerns\CanBeAutocompleted;
use Sylament\Forms\Components\Concerns\CanBeLengthConstrained;
use Sylament\Forms\Components\Concerns\CanBeReadOnly;
use Sylament\Forms\Components\Concerns\CanDisableGrammarly;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Schemas\Components\Concerns\CanStripCharactersFromState;
use Sylament\Schemas\Components\Concerns\CanTrimState;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\StripCharactersStateCast;

class Textarea extends Field implements Contracts\CanBeLengthConstrained
{
    use CanBeAutocompleted;
    use CanBeLengthConstrained;
    use CanBeReadOnly;
    use CanDisableGrammarly;
    use CanStripCharactersFromState;
    use CanTrimState;
    use HasExtraInputAttributes;

    protected string|Closure|null $template = '@SylamentForms/partials/Textarea/index.html.twig';

    protected int|Closure|null $rows = null;

    protected int|Closure|null $cols = null;

    protected bool|Closure $shouldAutosize = false;

    public function rows(int|Closure|null $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function cols(int|Closure|null $cols): static
    {
        $this->cols = $cols;

        return $this;
    }

    public function autosize(bool|Closure $condition = true): static
    {
        $this->shouldAutosize = $condition;

        return $this;
    }

    public function getRows(): ?int
    {
        return $this->evaluate($this->rows);
    }

    public function getCols(): ?int
    {
        return $this->evaluate($this->cols);
    }

    public function shouldAutosize(): bool
    {
        return (bool) $this->evaluate($this->shouldAutosize);
    }

    public function mutateStateForValidation(mixed $state): mixed
    {
        return parent::mutateStateForValidation($this->trimState($this->stripCharactersFromState($state)));
    }

    public function mutatesStateForValidation(): bool
    {
        return parent::mutatesStateForValidation() || $this->hasStripCharacters() || $this->isTrimmed();
    }

    /**
     * Filament's asymmetry, kept: `trim()` reaches the saved value from here, while
     * `stripCharacters()` reaches it through the state cast below — and neither
     * reaches the raw bag the browser wrote.
     */
    public function mutateDehydratedState(mixed $state): mixed
    {
        return parent::mutateDehydratedState($this->trimState($state));
    }

    public function mutatesDehydratedState(): bool
    {
        return parent::mutatesDehydratedState() || $this->isTrimmed();
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            ...($this->hasStripCharacters() ? [new StripCharactersStateCast(array_values($this->getStripCharacters()))] : []),
        ];
    }
}
