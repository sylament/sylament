<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Sylament\Support\Twig\ComponentAttributeBag;

trait HasExtraModalOverlayAttributes
{
    /**
     * @var array<array<mixed>|Closure>
     */
    protected array $extraModalOverlayAttributes = [];

    /**
     * @param array<mixed>|Closure $attributes
     */
    public function extraModalOverlayAttributes(array|Closure $attributes, bool $merge = false): static
    {
        if ($merge) {
            $this->extraModalOverlayAttributes[] = $attributes;
        } else {
            $this->extraModalOverlayAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraModalOverlayAttributes(): array
    {
        $attributeBag = new ComponentAttributeBag();

        foreach ($this->extraModalOverlayAttributes as $extraModalOverlayAttributes) {
            $attributeBag = $attributeBag->merge($this->evaluate($extraModalOverlayAttributes) ?: []);
        }

        return $attributeBag->getAttributes();
    }

    public function getExtraModalOverlayAttributeBag(): ComponentAttributeBag
    {
        return new ComponentAttributeBag($this->getExtraModalOverlayAttributes());
    }
}
