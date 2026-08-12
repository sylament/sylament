<?php

declare(strict_types=1);

namespace Sylament\Panels\Navigation;

use Closure;
use Sylament\Support\Components\Component;
use Sylament\Support\Components\Concerns\HasIcon;

use function Sylament\Support\filled;

class NavigationGroup extends Component
{
    use HasIcon;

    protected bool|Closure $isCollapsed = false;

    protected bool|Closure|null $isCollapsible = null;

    /**
     * @var array<NavigationItem> | Closure
     */
    protected array|Closure $items = [];

    protected string|Closure|null $label = null;

    public static function make(string|Closure|null $label = null): static
    {
        $group = static::newInstance();

        if (filled($label)) {
            $group->label($label);
        }

        $group->configure();

        return $group;
    }

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function collapsed(bool|Closure $condition = true): static
    {
        $this->isCollapsed = $condition;

        return $this;
    }

    public function collapsible(bool|Closure|null $condition = true): static
    {
        $this->isCollapsible = $condition;

        return $this;
    }

    /**
     * @param  array<NavigationItem> | Closure  $items
     */
    public function items(array|Closure $items): static
    {
        $this->items = $items;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->evaluate($this->label);
    }

    /**
     * @return array<NavigationItem>
     */
    public function getItems(): array
    {
        return $this->evaluate($this->items);
    }

    public function isCollapsed(): bool
    {
        if (! $this->isCollapsible()) {
            return false;
        }

        return (bool) $this->evaluate($this->isCollapsed);
    }

    public function isCollapsible(): bool
    {
        return (bool) ($this->evaluate($this->isCollapsible) ?? true);
    }

    public function isActive(): bool
    {
        foreach ($this->getItems() as $item) {
            if ($item->isHidden()) {
                continue;
            }

            if ($item->isActive()) {
                return true;
            }
        }

        return false;
    }
}
