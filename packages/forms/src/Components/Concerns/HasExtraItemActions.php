<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Sylament\Actions\Action;
use Sylament\Support\Enums\Size;

use function Sylament\Support\wrap;

trait HasExtraItemActions
{
    /**
     * @var array<Action|Closure>
     */
    protected array $extraItemActions = [];

    /**
     * @var array<string, Action>|null
     */
    protected ?array $cachedExtraItemActions = null;

    /**
     * @param array<Action|Closure> $actions
     */
    public function extraItemActions(array $actions): static
    {
        $this->extraItemActions = [...$this->extraItemActions, ...$actions];

        return $this;
    }

    /**
     * @return array<string, Action>
     */
    public function getExtraItemActions(): array
    {
        return $this->cachedExtraItemActions ??= $this->cacheExtraItemActions();
    }

    /**
     * Name-keyed, because the action cache splices this map into its own and PHP
     * would renumber integer keys through the spread.
     *
     * @return array<string, Action>
     */
    public function cacheExtraItemActions(): array
    {
        $this->cachedExtraItemActions = [];

        foreach ($this->extraItemActions as $extraItemAction) {
            foreach (wrap($this->evaluate($extraItemAction)) as $action) {
                $this->cachedExtraItemActions[$action->getName()] = $this->prepareAction(
                    $action
                        ->defaultColor('gray')
                        ->defaultSize(Size::Small)
                        ->defaultTemplate(Action::ICON_BUTTON_TEMPLATE),
                );
            }
        }

        return $this->cachedExtraItemActions;
    }
}
