<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Utilities;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\State\PropertyAccessor;

final readonly class Set
{
    public function __construct(
        private Component $component,
    ) {
        //
    }

    public function __invoke(string|Component $path, mixed $state, bool $isAbsolute = false, bool $shouldCallUpdatedHooks = false): mixed
    {
        $liveComponent = $this->component->getLiveComponent();

        $path = $this->component->resolveRelativeStatePath($path, $isAbsolute);

        $component = ($this->component->getStatePath() === $path)
            ? $this->component
            : $this->component->getRootContainer()->getComponentByStatePath($path, [$this->component]);

        $state = $this->component->evaluate($state);

        if ($component instanceof Component) {
            $component->state($state);

            if ($shouldCallUpdatedHooks) {
                $component->callAfterStateUpdatedHooks();
            }
        } else {
            PropertyAccessor::getInstance()->set($liveComponent, $path, $state);
        }

        return $state;
    }
}
