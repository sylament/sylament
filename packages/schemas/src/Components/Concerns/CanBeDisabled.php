<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Support\Enums\Operation;

trait CanBeDisabled
{
    protected bool|Closure $isDisabled = false;

    public function disabled(bool|Closure $condition = true): static
    {
        $this->isDisabled = $condition;
        $this->saved(fn (Component $component): bool => ! $component->evaluate($condition));

        return $this;
    }

    /**
     * @param string|Operation|array<string|Operation> $operations
     */
    public function disabledOn(string|Operation|array $operations): static
    {
        $this->disabled(static function (InteractsWithSchemas $liveComponent, string $operation) use ($operations): bool {
            foreach (is_array($operations) ? $operations : [$operations] as $disabledOperation) {
                if ($disabledOperation instanceof Operation) {
                    $disabledOperation = $disabledOperation->value;
                }

                if ($disabledOperation === $operation || $liveComponent instanceof $disabledOperation) {
                    return true;
                }
            }

            return false;
        });

        return $this;
    }

    public function isDisabled(): bool
    {
        if ($this->evaluate($this->isDisabled)) {
            return true;
        }

        return (bool) $this->getContainer()->isDisabled();
    }

    public function isEnabled(): bool
    {
        return ! $this->isDisabled();
    }
}
