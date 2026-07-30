<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Sylament\Forms\Components\Concerns\CanBeAccepted;
use Sylament\Forms\Components\Concerns\CanBeInline;
use Sylament\Forms\Components\Concerns\HasToggleIcons;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Symfony\Component\Validator\Constraints\Type;

class Toggle extends Field
{
    use CanBeAccepted;
    use CanBeInline;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(false);

        $this->constraint(fn (): Type => new Type('bool', message: $this->getValidationMessages()['boolean'] ?? null));
    }
}
