<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Support\Enums\Operation;

use function Sylament\Support\wrap;

trait CanBeReadOnly
{
    protected bool|Closure $isReadOnly = false;

    public function readOnly(bool|Closure $condition = true): static
    {
        $this->isReadOnly = $condition;

        return $this;
    }

    /**
     * @param string|Operation|array<string|Operation> $operations
     */
    public function readOnlyOn(string|Operation|array $operations): static
    {
        $this->readOnly(static function (InteractsWithSchemas $liveComponent, string $operation) use ($operations): bool {
            foreach (wrap($operations) as $readOnlyOperation) {
                if ($readOnlyOperation instanceof Operation) {
                    $readOnlyOperation = $readOnlyOperation->value;
                }

                if ($readOnlyOperation === $operation || $liveComponent instanceof $readOnlyOperation) {
                    return true;
                }
            }

            return false;
        });

        return $this;
    }

    public function isReadOnly(): bool
    {
        return (bool) $this->evaluate($this->isReadOnly);
    }
}
