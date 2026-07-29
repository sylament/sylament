<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

trait HasAction
{
    protected Closure|string|null $action = null;

    public function action(Closure|string|null $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getAction(): Closure|string|null
    {
        return $this->action;
    }

    public function getClientClickHandler(): ?string
    {
        return is_string($this->action) ? $this->action : null;
    }
}
