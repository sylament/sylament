<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;

use function Sylament\Support\wrap;

trait HasHeaderActions
{
    /**
     * @var array<Action|ActionGroup|Closure>
     */
    protected array $headerActions = [];

    /**
     * @param array<Action|ActionGroup|Closure> $actions
     */
    public function headerActions(array $actions): static
    {
        $this->headerActions = [
            ...$this->headerActions,
            ...$actions,
        ];

        return $this;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getHeaderActions(): array
    {
        $actions = [];

        foreach ($this->headerActions as $headerAction) {
            foreach (wrap($this->evaluate($headerAction)) as $action) {
                $actions[] = ($action instanceof ActionGroup)
                    ? $this->prepareActionGroup($action)
                    : $this->prepareAction($action);
            }
        }

        return $actions;
    }
}
