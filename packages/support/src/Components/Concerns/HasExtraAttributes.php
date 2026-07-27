<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Sylament\Support\Twig\ComponentAttributeBag;

trait HasExtraAttributes
{
    /**
     * @var array<array<mixed>|Closure>
     */
    protected array $extraAttributes = [];

    /**
     * @param array<mixed>|Closure $attributes
     */
    public function extraAttributes(array|Closure $attributes, bool $merge = false): static
    {
        if ($merge) {
            $this->extraAttributes[] = $attributes;
        } else {
            $this->extraAttributes = [$attributes];
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
