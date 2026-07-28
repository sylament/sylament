<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Utilities;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\State\PropertyAccessor;

final class Get
{
    private bool $shouldSkipComponentsChildContainersWhileSearching = true;

    public function __construct(
        private readonly Component $component,
    ) {
        //
    }

    public function __invoke(string|Component $path = '', bool $isAbsolute = false): mixed
    {
        $liveComponent = $this->component->getLiveComponent();

        $path = $this->component->resolveRelativeStatePath($path, $isAbsolute);

        $component = ($this->component->getStatePath() === $path)
            ? $this->component
            : $this->component->getRootContainer()->getComponentByStatePath(
                $path,
                $this->shouldSkipComponentsChildContainersWhileSearching ? [$this->component] : [],
            );

        if (!$component instanceof Component) {
            return PropertyAccessor::getInstance()->get($liveComponent, $path);
        }

        return $component->getState();
    }

    public function skipComponentsChildContainersWhileSearching(bool $condition = true): static
    {
        $this->shouldSkipComponentsChildContainersWhileSearching = $condition;

        return $this;
    }
}
