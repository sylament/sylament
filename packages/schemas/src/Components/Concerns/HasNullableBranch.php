<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

trait HasNullableBranch
{
    protected bool|Closure $isNulledWhenBlank = false;

    /**
     * Write null at this component's whole record path instead of its leaves,
     * when the branch should not exist. `true` uses the built-in test — every
     * mapped leaf beneath it dehydrates blank, each leaf judging its own value
     * through {@see isBlankStateValue()}, except on a component that
     * {@see HasChildComponents::ownsRecordSubtree()}, which judges its own whole
     * slice instead; a closure receives the branch's dehydrated `$state` and
     * returns whether to null it.
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
     * Whether $value is blank by this component's own state semantics. The
     * branch blank test asks each declared leaf, so a field whose cast never
     * dehydrates null (an untouched checkbox rests at false) overrides this to
     * keep its resting value from holding an optional branch alive.
     */
    public function isBlankStateValue(mixed $value): bool
    {
        return blank($value);
    }

    /**
     * @param array<string, mixed> $state
     */
    private function isBranchStateBlank(array $state): bool
    {
        if ($this->ownsRecordSubtree()) {
            return $this->isBlankStateValue($state);
        }

        if (! isset($this->container)) {
            return $this->isValueSetBlank($state);
        }

        if (! filled($this->getStatePath(isAbsolute: false))) {
            return $this->isValueSetBlank($state);
        }

        $prefix = $this->getStatePath();

        if (! filled($prefix)) {
            return $this->isValueSetBlank($state);
        }

        $accessor = $this->getPropertyAccessor();
        $leafPrefix = $prefix . '.';

        $flatComponents = $this->getRootContainer()->getFlatComponents(withActions: false);
        $ownerPrefixes = [];

        foreach ($flatComponents as $leaf) {
            if (! $leaf->ownsRecordSubtree()) {
                continue;
            }

            $ownerPath = $leaf->hasStatePath() ? $leaf->getStatePath() : null;

            if (! filled($ownerPath)) {
                continue;
            }

            $ownerPrefixes[] = $ownerPath . '.';
        }

        foreach ($flatComponents as $leaf) {
            if (! $leaf->hasStatePath()) {
                continue;
            }

            if ($leaf->hasPathBearingDescendants() && ! $leaf->ownsRecordSubtree()) {
                continue;
            }

            $leafPath = $leaf->getStatePath();

            if ($leafPath === null) {
                continue;
            }

            if (! str_starts_with($leafPath, $leafPrefix)) {
                continue;
            }

            foreach ($ownerPrefixes as $ownerPrefix) {
                if (str_starts_with($leafPath, $ownerPrefix)) {
                    continue 2;
                }
            }

            $relativePath = mb_substr($leafPath, mb_strlen($leafPrefix));

            if (! $accessor->hasInArray($state, $relativePath)) {
                continue;
            }

            if (! $leaf->isBlankStateValue($accessor->getFromArray($state, $relativePath))) {
                return false;
            }
        }

        return true;
    }

    /**
     * The detached arm: a component no schema wired (or one without a path of
     * its own) cannot enumerate leaves, so blankness falls back to the values
     * themselves.
     *
     * @param array<string, mixed> $state
     */
    private function isValueSetBlank(array $state): bool
    {
        foreach ($state as $value) {
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                if (! $this->isValueSetBlank($value)) {
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
