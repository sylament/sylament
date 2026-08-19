<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\CanConcealComponents;

trait CanBeConcealed
{
    /**
     * The memoised answer: the concealing ancestor, or `false` once the walk has
     * proven there is none.
     */
    protected Component|false|null $cachedConcealingComponent = null;

    /**
     * The nearest ancestor that may hide this component from view — a parent
     * whose {@see CanConcealComponents::canConcealComponents()} is true, or that
     * ancestor's own concealer. Null when nothing conceals it.
     */
    public function getConcealingComponent(): ?Component
    {
        if ($this->cachedConcealingComponent !== null) {
            return $this->cachedConcealingComponent instanceof Component ? $this->cachedConcealingComponent : null;
        }

        $parentComponent = $this->getContainer()->getParentComponent();

        if ($parentComponent === null) {
            $this->cachedConcealingComponent = false;
        } elseif ($parentComponent instanceof CanConcealComponents && $parentComponent->canConcealComponents()) {
            $this->cachedConcealingComponent = $parentComponent;
        } else {
            $this->cachedConcealingComponent = $parentComponent->getConcealingComponent() ?? false;
        }

        return $this->cachedConcealingComponent instanceof Component ? $this->cachedConcealingComponent : null;
    }

    public function isConcealed(): bool
    {
        return $this->getConcealingComponent() !== null;
    }
}
