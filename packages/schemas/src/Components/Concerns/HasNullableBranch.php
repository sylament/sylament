<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

use function Sylament\Support\filled;

trait HasNullableBranch
{
    protected bool|Closure $isNulledWhenBlank = false;

    /**
     * Write null at this component's whole record path instead of its leaves,
     * when the branch should not exist. `true` uses the built-in test — every
     * mapped leaf beneath it dehydrates blank; a closure receives the branch's
     * dehydrated `$state` and returns whether to null it.
     */
    public function nullWhenBlank(bool|Closure $condition = true): static
    {
        $this->isNulledWhenBlank = $condition;

        return $this;
    }

    public function isNullableBranch(): bool
    {
        return $this->isNulledWhenBlank !== false;
    }

    /**
     * @param array<string, mixed> $state
     */
    public function shouldNullBranch(array $state): bool
    {
        if ($this->isNulledWhenBlank instanceof Closure) {
            return (bool) $this->evaluate($this->isNulledWhenBlank, ['state' => $state]);
        }

        if ($this->isNulledWhenBlank === false) {
            return false;
        }

        return $this->isBranchStateBlank($state);
    }

    /**
     * @param array<string, mixed> $state
     */
    private function isBranchStateBlank(array $state): bool
    {
        foreach ($state as $value) {
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                if (! $this->isBranchStateBlank($value)) {
                    return false;
                }

                continue;
            }

            if (filled($value)) {
                return false;
            }
        }

        return true;
    }
}
