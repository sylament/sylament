<?php

declare(strict_types=1);

namespace Sylament\Support\Component\Concerns;

use Closure;
use Sylament\Support\Twig\ComponentAttributeBag;

trait HasExtraAttributes
{
    /**
     * @var array<array<mixed>|Closure():array<mixed>>
     */
    protected array $extraAttributes = [];

    /**
     * @param array<mixed>|callable():array<mixed> $attributes
     */
    public function extraAttributes(array|callable $attributes, bool $merge = false): static
    {
        if ($merge) {
            $this->extraAttributes[] = $this->evaluatable($attributes);
        } else {
            $this->extraAttributes = [$this->evaluatable($attributes)];
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraAttributes(): array
    {
        $attributeBag = new ComponentAttributeBag();

        foreach ($this->extraAttributes as $extraAttributes) {
            $attributeBag = $attributeBag->merge($this->evaluate($extraAttributes) ?: []);
        }

        return $attributeBag->getAttributes();
    }

    public function getExtraAttributeBag(): ComponentAttributeBag
    {
        return new ComponentAttributeBag($this->getExtraAttributes());
    }

    public function hasExtraAttributes(): bool
    {
        return $this->extraAttributes !== [];
    }
}
