<?php

declare(strict_types=1);

namespace Sylament\Panels;

use Closure;
use Sylament\Panels\Panel\Concerns\HasBrandLogo;
use Sylament\Panels\Panel\Concerns\HasBrandName;
use Sylament\Panels\Panel\Concerns\HasComponents;
use Sylament\Panels\Panel\Concerns\HasGlobalSearch;
use Sylament\Panels\Panel\Concerns\HasId;
use Sylament\Panels\Panel\Concerns\HasNavigation;
use Sylament\Panels\Panel\Concerns\HasRoutes;
use Sylament\Panels\Panel\Concerns\HasSidebar;
use Sylament\Support\Components\Component;

class Panel extends Component
{
    use HasBrandLogo;
    use HasBrandName;
    use HasComponents;
    use HasGlobalSearch;
    use HasId;
    use HasNavigation;
    use HasRoutes;
    use HasSidebar;

    protected bool|Closure $isDefault = false;

    public static function make(): static
    {
        $panel = static::newInstance();

        $panel->configure();

        return $panel;
    }

    public function default(bool|Closure $condition = true): static
    {
        $this->isDefault = $condition;

        return $this;
    }

    public function isDefault(): bool
    {
        return (bool) $this->evaluate($this->isDefault);
    }
}
