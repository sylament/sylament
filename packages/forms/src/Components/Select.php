<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use LogicException;
use Sylament\Forms\Components\Concerns\CanDisableOptions;
use Sylament\Forms\Components\Concerns\CanSelectPlaceholder;
use Sylament\Forms\Components\Concerns\HasOptions;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\OptionStateCast;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;

use function Sylament\Support\blank;

class Select extends Field implements Contracts\CanDisableOptions
{
    use CanDisableOptions;
    use CanSelectPlaceholder;
    use HasOptions;

    protected string|Closure|null $template = '@SylamentForms/partials/Select/index.html.twig';

    protected function setUp(): void
    {
        parent::setUp();

        $this->placeholder('Select an option');
    }

    public function boolean(?string $trueLabel = null, ?string $falseLabel = null, ?string $placeholder = null): static
    {
        $this->options([
            1 => $trueLabel ?? 'Yes',
            0 => $falseLabel ?? 'No',
        ]);

        $this->placeholder($placeholder ?? '-');

        $this->stateCast(new BooleanStateCast(isStoredAsInt: true));

        return $this;
    }

    public function getOptionLabel(): ?string
    {
        $state = $this->getState();

        if (is_bool($state)) {
            $state = (int) $state;
        }

        if (! is_string($state) && ! is_int($state)) {
            return null;
        }

        foreach ($this->getOptions() as $value => $label) {
            $group = is_array($label) ? $label : [$value => $label];

            if (array_key_exists($state, $group)) {
                return $group[$state];
            }
        }

        return null;
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        if ($this->hasCustomStateCasts()) {
            return parent::getDefaultStateCasts();
        }

        return [new OptionStateCast(isNullable: true)];
    }

    /**
     * @return list<Constraint>
     */
    protected function buildValidationConstraints(): array
    {
        $constraints = parent::buildValidationConstraints();

        if (blank($this->getState())) {
            return $constraints;
        }

        if ($this->options === null) {
            throw new LogicException(
                "Sylament failed to validate the [{$this->getStatePath()}] select because it has no options() configuration. Call options() so Sylament knows which values are valid.",
            );
        }

        $constraints[] = new Choice(
            choices: $this->getEnabledOptionValues(),
            strict: true,
            message: $this->getValidationMessages()['in'] ?? null,
        );

        return $constraints;
    }

    /**
     * Every enabled option value, in both forms a request can legitimately leave in
     * the host's state: the form the field's state casts store — what `fill()` and
     * `state()` write — and the string the browser submits, which is always the
     * option's array key as the template renders it into the `value` attribute.
     * Validation runs against the raw state, and those two forms part company
     * whenever a cast leaves the array-key vocabulary.
     *
     * @return list<mixed>
     */
    protected function getEnabledOptionValues(): array
    {
        $stateCasts = array_reverse($this->getStateCasts());

        $values = [];

        foreach (array_keys($this->getEnabledOptions()) as $value) {
            $castValue = $value;

            foreach ($stateCasts as $stateCast) {
                $castValue = $stateCast->set($castValue);
            }

            $values[] = $castValue;

            $submittedValue = (string) $value;

            if ($submittedValue !== $castValue) {
                $values[] = $submittedValue;
            }
        }

        return $values;
    }
}
