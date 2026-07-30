<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Operation;

use function Sylament\Support\wrap;

trait CanBeHidden
{
    protected bool|Closure $isHidden = false;

    protected bool|Closure $isVisible = true;

    public function hidden(bool|Closure $condition = true): static
    {
        $this->isHidden = $condition;

        return $this;
    }

    /**
     * @param string|Operation|array<string|Operation> $operations
     */
    public function hiddenOn(string|Operation|array $operations): static
    {
        $this->hidden(static function (InteractsWithSchemas $liveComponent, string $operation) use ($operations): bool {
            foreach (wrap($operations) as $hiddenOperation) {
                if ($hiddenOperation instanceof Operation) {
                    $hiddenOperation = $hiddenOperation->value;
                }

                if ($hiddenOperation === $operation || $liveComponent instanceof $hiddenOperation) {
                    return true;
                }
            }

            return false;
        });

        return $this;
    }

    public function hiddenWhenAllChildComponentsHidden(): static
    {
        $this->hidden(static fn (Component $component): bool => array_all($component->getChildSchemas(), fn (Schema $childSchema): bool => $childSchema->getComponents(withHidden: false) === []));

        return $this;
    }

    public function visible(bool|Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    /**
     * @param string|Operation|array<string|Operation> $operations
     */
    public function visibleOn(string|Operation|array $operations): static
    {
        $this->visible(static function (InteractsWithSchemas $liveComponent, string $operation) use ($operations): bool {
            foreach (wrap($operations) as $visibleOperation) {
                if ($visibleOperation instanceof Operation) {
                    $visibleOperation = $visibleOperation->value;
                }

                if ($visibleOperation === $operation || $liveComponent instanceof $visibleOperation) {
                    return true;
                }
            }

            return false;
        });

        return $this;
    }

    public function isHidden(): bool
    {
        if ($this->evaluate($this->isHidden)) {
            return true;
        }

        return ! $this->evaluate($this->isVisible);
    }

    public function isVisible(): bool
    {
        return ! $this->isHidden();
    }
}
