<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use Sylament\Schemas\Components\Component;

final class Set
{
    private bool $shouldSkipComponentsChildSchemasWhileSearching = true;

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
                $this->shouldSkipComponentsChildSchemasWhileSearching ? [$this->component] : [],
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

    public function skipComponentsChildSchemasWhileSearching(bool $condition = true): static
    {
        $this->shouldSkipComponentsChildSchemasWhileSearching = $condition;

        return $this;
    }
}
