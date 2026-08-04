<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Symfony\Component\Uid\Uuid;

trait CanGenerateUuids
{
    protected string|Closure|null $generateUuidUsing = null;

    protected bool $generatesUuids = true;

    public function generateUuidUsing(Closure|bool|null $callback): static
    {
        if (is_bool($callback)) {
            $this->generatesUuids = $callback;
            $this->generateUuidUsing = null;

            return $this;
        }

        $this->generatesUuids = true;
        $this->generateUuidUsing = $callback;

        return $this;
    }

    public function generateUuid(): ?string
    {
        if (!$this->generatesUuids) {
            return null;
        }

        if ($this->generateUuidUsing === null) {
            return Uuid::v4()->toRfc4122();
        }

        return $this->evaluate($this->generateUuidUsing);
    }

    /**
     * Turn uuid generation off for every instance of this component, so items stay
     * positionally keyed — the test idiom. Returns the closure that restores it.
     */
    public static function fake(): Closure
    {
        /** @var Closure $forget */
        $forget = static::configureUsing(static function (self $component): void {
            $component->generateUuidUsing(false);
        });

        return $forget;
    }
}
