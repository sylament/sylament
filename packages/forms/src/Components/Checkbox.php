<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Sylament\Forms\Components\Concerns\CanBeAccepted;
use Sylament\Forms\Components\Concerns\CanBeInline;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Symfony\Component\Validator\Constraints\Type;

class Checkbox extends Field
{
    use CanBeAccepted;
    use CanBeInline;

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
}
