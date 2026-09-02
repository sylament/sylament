<?php

declare(strict_types=1);

namespace Sylament\Tables\Columns;

use Closure;
use Sylament\Forms\Components\Concerns\CanDisableOptions;
use Sylament\Forms\Components\Concerns\CanSelectPlaceholder;
use Sylament\Forms\Components\Concerns\HasEnum;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Forms\Components\Concerns\HasOptions;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Tables\Columns\Concerns\CanBeValidated;
use Sylament\Tables\Columns\Concerns\CanUpdateState;
use Sylament\Tables\Columns\Contracts\Editable;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;
use Twig\Markup;

class SelectColumn extends Column implements Editable
{
    use CanBeValidated {
        buildValidationConstraints as buildBaseValidationConstraints;
    }
    use CanDisableOptions;
    use CanSelectPlaceholder;
    use CanUpdateState;
    use HasEnum;
    use HasExtraInputAttributes;
    use HasOptions {
        getOptions as getBaseOptions;
    }

    protected string|Closure|null $template = '@SylamentTables/partials/SelectColumn/index.html.twig';

    protected bool|Closure $areOptionsRemembered = false;

    /**
     * @var array<string|int, string|Markup|array<string|int, string|Markup>>|null
     */
    protected ?array $rememberedOptions = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disabledClick();

        $this->placeholder('Select an option');
    }

    public function rememberOptions(bool|Closure $condition = true): static
    {
        $this->areOptionsRemembered = $condition;

        return $this;
    }

    public function areOptionsRemembered(): bool
    {
        return (bool) $this->evaluate($this->areOptionsRemembered);
    }

    /**
     * @return array<string|int, string|Markup|array<string|int, string|Markup>>
     */
    public function getOptions(): array
    {
        if (!$this->areOptionsRemembered()) {
            return $this->getBaseOptions();
        }

        return $this->rememberedOptions ??= $this->getBaseOptions();
    }

    /**
     * A column carries no state casts — the option keys the template renders are
     * the vocabulary the browser posts back, with nothing between them and the
     * record. The stub is what keeps forms'
     * {@see CanDisableOptions::getEnabledOptionValues()} usable here.
     *
     * @return array<StateCast>
     */
    public function getStateCasts(): array
    {
        return [];
    }

    /**
     * The enabled option keys are string-cast, because a `<select>` posts strings
     * and `Choice` compares strictly — an integer-keyed option map would
     * otherwise refuse every selection. Rebuilt per validation so a closure's
     * option map is read at the moment the input is judged.
     *
     * @return list<Constraint>
     */
    protected function buildValidationConstraints(): array
    {
        return [
            ...$this->buildBaseValidationConstraints(),
            new Choice(choices: $this->getEnabledOptionValues()),
        ];
    }
}
