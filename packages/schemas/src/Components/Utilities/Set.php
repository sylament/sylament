<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Utilities;

use Sylament\Schemas\Components\Component;

final class Set
{
    private bool $shouldSkipComponentsChildContainersWhileSearching = true;

    public function __construct(
        private readonly Component $component,
    ) {
    }

    public function __invoke(string|Component $path, mixed $state, bool $isAbsolute = false, bool $shouldCallUpdatedHooks = false): mixed
    {
        $liveComponent = $this->component->getLiveComponent();

        $path = $this->component->resolveRelativeStatePath($path, $isAbsolute);

        $component = ($this->component->getStatePath() === $path)
            ? $this->component
            : $this->component->getRootContainer()->getComponentByStatePath(
                $path,
                $this->shouldSkipComponentsChildContainersWhileSearching ? [$this->component] : [],
            );

        $state = $this->component->evaluate($state);

        if ($component instanceof Component) {
            $component->state($state);

            if ($shouldCallUpdatedHooks) {
                $component->callAfterStateUpdatedHooks();
            }
        } else {
            $this->component->getPropertyAccessor()->set($liveComponent, $path, $state);
        }

        return $state;
    }

    public function skipComponentsChildContainersWhileSearching(bool $condition = true): static
    {
        $this->shouldSkipComponentsChildContainersWhileSearching = $condition;

        return $this;
    }
}
