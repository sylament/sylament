<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Sylament\Support\Twig\ComponentAttributeBag;

trait HasExtraInputAttributes
{
    /**
     * @var array<array<mixed>|Closure>
     */
    protected array $extraInputAttributes = [];

    /**
     * @param array<mixed>|Closure $attributes
     */
    public function extraInputAttributes(array|Closure $attributes, bool $merge = false): static
    {
        if ($merge) {
            $this->extraInputAttributes[] = $attributes;
        } else {
            $this->extraInputAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraInputAttributes(): array
    {
        $attributeBag = new ComponentAttributeBag();

        foreach ($this->extraInputAttributes as $extraInputAttributes) {
            $attributeBag = $attributeBag->merge($this->evaluate($extraInputAttributes) ?: []);
        }

        return $attributeBag->getAttributes();
    }

    public function getExtraInputAttributeBag(): ComponentAttributeBag
    {
        return new ComponentAttributeBag($this->getExtraInputAttributes());
    }
}
