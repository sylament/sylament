<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Sylament\Support\Twig\ComponentAttributeBag;

trait HasExtraModalWindowAttributes
{
    /**
     * @var array<array<mixed>|Closure>
     */
    protected array $extraModalWindowAttributes = [];

    /**
     * @param array<mixed>|Closure $attributes
     */
    public function extraModalWindowAttributes(array|Closure $attributes, bool $merge = false): static
    {
        if ($merge) {
            $this->extraModalWindowAttributes[] = $attributes;
        } else {
            $this->extraModalWindowAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraModalWindowAttributes(): array
    {
        $attributeBag = new ComponentAttributeBag();

        foreach ($this->extraModalWindowAttributes as $extraModalWindowAttributes) {
            $attributeBag = $attributeBag->merge($this->evaluate($extraModalWindowAttributes) ?: []);
        }

        return $attributeBag->getAttributes();
    }

    public function getExtraModalWindowAttributeBag(): ComponentAttributeBag
    {
        return new ComponentAttributeBag($this->getExtraModalWindowAttributes());
    }
}
