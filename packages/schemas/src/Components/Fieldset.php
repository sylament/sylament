<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use Sylament\Schemas\Components\Concerns\CanBeMarkedAsRequired;
use Sylament\Schemas\Components\Concerns\HasLabel;
use Sylament\Support\Components\Concerns\CanBeContained;
use Twig\Markup;

class Fieldset extends Component
{
    use CanBeContained;
    use CanBeMarkedAsRequired;
    use HasLabel;

    protected string|Closure|null $template = '@SylamentSchemas/partials/Fieldset/index.html.twig';

    public static function make(string|Markup|Closure|null $label = null): static
    {
        $fieldset = static::newInstance();

        $fieldset->label($label);

        $fieldset->configure();

        return $fieldset;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->columns(2);
    }

    public function isRequired(): bool
    {
        return false;
    }
}
