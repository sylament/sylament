<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use LogicException;
use Symfony\Component\Uid\Uuid;

trait CanGenerateUuids
{
    protected Closure|bool|null $generateUuidUsing = null;

    public function generateUuidUsing(Closure|bool|null $callback): static
    {
        $this->generateUuidUsing = $callback;

        return $this;
    }

    public function generateUuid(): ?string
    {
        if ($this->generateUuidUsing instanceof Closure) {
            $uuid = $this->evaluate($this->generateUuidUsing);

            if ($uuid === null || is_string($uuid)) {
                return $uuid;
            }

            throw new LogicException(sprintf(
                '%s: the generateUuidUsing() closure must return a string or null, %s given.',
                static::class,
                get_debug_type($uuid),
            ));
        }

        if ($this->generateUuidUsing === false) {
            return null;
        }

        return Uuid::v4()->toRfc4122();
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
