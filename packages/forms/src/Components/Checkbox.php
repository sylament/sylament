<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Sylament\Forms\Components\Concerns\CanBeAccepted;
use Sylament\Forms\Components\Concerns\CanBeInline;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Symfony\Component\Validator\Constraints\Type;

class Checkbox extends Field
{
    use CanBeAccepted;
    use CanBeInline;
    use HasExtraInputAttributes;

    protected string|Closure|null $template = '@SylamentForms/partials/Checkbox/index.html.twig';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(false);

        $this->constraint(new Type('bool'));
    }

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

    /**
     * A checkbox never dehydrates null — its state cast is non-nullable and its
     * default is false — so a non-nullable `bool` target always accepts what it
     * produces. Deriving requirement from that target would silently mean "must
     * be ticked": `NotBlank` rejects false, and the template emits native
     * `required`. Only an explicit {@see required()} may say so.
     */
    protected function isRequiredByRecord(): bool
    {
        return false;
    }

    /**
     * False is this field's resting value — its non-nullable cast dehydrates an
     * untouched box to false — so the branch blank test must read it as blank,
     * or an optional branch containing a checkbox could never be discarded.
     */
    public function isBlankStateValue(mixed $value): bool
    {
        return $value === false || parent::isBlankStateValue($value);
    }
}
