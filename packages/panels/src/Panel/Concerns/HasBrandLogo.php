<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use Closure;
use Twig\Markup;

trait HasBrandLogo
{
    protected string|Markup|Closure|null $brandLogo = null;

    protected string|Closure|null $brandLogoHeight = null;

    protected string|Markup|Closure|null $darkModeBrandLogo = null;

    public function brandLogo(string|Markup|Closure|null $logo): static
    {
        $this->brandLogo = $logo;

        return $this;
    }

    public function brandLogoHeight(string|Closure|null $height): static
    {
        $this->brandLogoHeight = $height;

        return $this;
    }

    public function darkModeBrandLogo(string|Markup|Closure|null $logo): static
    {
        $this->darkModeBrandLogo = $logo;

        return $this;
    }

    public function getBrandLogo(): string|Markup|null
    {
        return $this->evaluate($this->brandLogo);
    }

    public function getBrandLogoHeight(): ?string
    {
        return $this->evaluate($this->brandLogoHeight);
    }

    public function getDarkModeBrandLogo(): string|Markup|null
    {
        return $this->evaluate($this->darkModeBrandLogo);
    }
}
