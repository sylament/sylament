<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Closure;
use Sylament\Actions\Concerns\BelongsToLiveComponent;
use Sylament\Actions\Concerns\HasAction;
use Sylament\Actions\Concerns\HasName;
use Sylament\Support\Component\ViewComponent;

class Action extends ViewComponent
{
    use BelongsToLiveComponent;
    use HasAction;
    use HasName;

    protected string $evaluationIdentifier = 'action';

    protected string|Closure|null $template = '@SylamentActions/components/button-action.html.twig';

    protected bool|Closure $isConfirmationRequired = false;

    final public function __construct(?string $name)
    {
        $this->name($name);
    }

    public static function make(?string $name = null): static
    {
        $action = new static($name);

        $action->configure();

        return $action;
    }

    public function requiresConfirmation(bool|Closure $condition = true): static
    {
        $this->isConfirmationRequired = $condition;

        return $this;
    }

    public function isConfirmationRequired(): bool
    {
        return $this->evaluate($this->isConfirmationRequired);
    }

    /**
     * A placeholder until the full `HasLabel` concern lands (AC3); the trigger
     * template falls back to the name when the label is null.
     */
    public function getLabel(): ?string
    {
        return null;
    }
}
