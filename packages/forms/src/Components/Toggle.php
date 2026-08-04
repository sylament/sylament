<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Sylament\Forms\Components\Concerns\CanBeAccepted;
use Sylament\Forms\Components\Concerns\CanBeInline;
use Sylament\Forms\Components\Concerns\HasToggleColors;
use Sylament\Forms\Components\Concerns\HasToggleIcons;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

class Toggle extends Field
{
    use CanBeAccepted;
    use CanBeInline;
    use HasToggleColors;
    use HasToggleIcons;

    protected string|Closure|null $template = '@SylamentForms/partials/Toggle/index.html.twig';

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            new BooleanStateCast(isNullable: false),
        ];
    }

    protected function getRequiredConstraint(): Constraint
    {
        return new NotNull();
    }

    /**
     * A toggle never dehydrates null — its state cast is non-nullable and its
     * default is false — so a non-nullable `bool` target always accepts what it
     * produces. Deriving requirement from that target would mark a field that
     * always has a value with the label's required asterisk.
     */
    protected function isRequiredByRecord(): bool
    {
        return false;
    }

    /**
     * False is this field's resting value — its non-nullable cast dehydrates an
     * untouched toggle to false — so the branch blank test must read it as blank,
     * or an optional branch containing a toggle could never be discarded.
     */
    public function isBlankStateValue(mixed $value): bool
    {
        return $value === false || parent::isBlankStateValue($value);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(false);

        $this->constraint(new Type('bool'));
    }
}
