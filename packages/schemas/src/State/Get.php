<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use Sylament\Schemas\Components\Component;

final class Get
{
    private bool $shouldSkipComponentsChildSchemasWhileSearching = true;

    public function __construct(
        private readonly Component $component,
    ) {
    }

    public function __invoke(string|Component $path = '', bool $isAbsolute = false): mixed
    {
        $liveComponent = $this->component->getLiveComponent();

        $path = $this->component->resolveRelativeStatePath($path, $isAbsolute);

        $component = ($this->component->getStatePath() === $path)
            ? $this->component
            : $this->component->getRootContainer()->getComponentByStatePath(
                $path,
                $this->shouldSkipComponentsChildSchemasWhileSearching ? [$this->component] : [],
            );

        if (!$component instanceof Component) {
            return $this->component->getPropertyAccessor()->get($liveComponent, $path);
        }

        return $component->getState();
    }

    public function skipComponentsChildSchemasWhileSearching(bool $condition = true): static
    {
        $this->shouldSkipComponentsChildSchemasWhileSearching = $condition;

        return $this;
    }
}
