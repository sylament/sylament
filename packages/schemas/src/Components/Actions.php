<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Concerns\HasLabel;
use Sylament\Support\Components\Concerns\HasAlignment;

class Actions extends Component
{
    use HasAlignment;
    use HasLabel;

    protected string|Closure|null $template = '@SylamentSchemas/components/Actions.html.twig';

    protected bool|Closure $isFullWidth = false;

    /**
     * @param array<Action|ActionGroup>|Closure $actions
     */
    final public function __construct(array|Closure $actions)
    {
        $this->actions($actions);
    }

    /**
     * @param array<Action|ActionGroup>|Closure $actions
     */
    public static function make(array|Closure $actions): static
    {
        $actions = new static($actions);

        $actions->configure();

        return $actions;
    }

    /**
     * @param array<Action|ActionGroup>|Closure $actions
     */
    public function actions(array|Closure $actions): static
    {
        $this->components($actions);

        return $this;
    }

    public function isHidden(): bool
    {
        if (parent::isHidden()) {
            return true;
        }

        return array_all($this->getChildSchema()?->getComponents() ?? [], fn ($component): bool => !$component->isVisible());
    }

    public function fullWidth(bool|Closure $isFullWidth = true): static
    {
        $this->isFullWidth = $isFullWidth;

        return $this;
    }

    public function isFullWidth(): bool
    {
        return $this->evaluate($this->isFullWidth);
    }
}
