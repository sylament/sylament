<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Sylament\Support\Twig\ComponentAttributeBag;

trait HasExtraFieldWrapperAttributes
{
    /**
     * @var array<array<mixed>|Closure>
     */
    protected array $extraFieldWrapperAttributes = [];

    /**
     * @param array<mixed>|Closure $attributes
     */
    public function extraFieldWrapperAttributes(array|Closure $attributes, bool $merge = false): static
    {
        if ($merge) {
            $this->extraFieldWrapperAttributes[] = $attributes;
        } else {
            $this->extraFieldWrapperAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraFieldWrapperAttributes(): array
    {
        $attributeBag = new ComponentAttributeBag();

        foreach ($this->extraFieldWrapperAttributes as $extraFieldWrapperAttributes) {
            $attributeBag = $attributeBag->merge($this->evaluate($extraFieldWrapperAttributes) ?: []);
        }

        return $attributeBag->getAttributes();
    }

    public function getExtraFieldWrapperAttributeBag(): ComponentAttributeBag
    {
        return new ComponentAttributeBag($this->getExtraFieldWrapperAttributes());
    }
}
