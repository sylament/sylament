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

    public function __invoke(string|Component $path, mixed $state, bool $isAbsolute = false): mixed
    {
        $path = $this->component->resolveRelativeStatePath($path, $isAbsolute);

        PropertyAccessor::getInstance()->set(
            $this->component->getLiveComponent(),
            $path,
            $this->component->evaluate($state),
        );

        return $state;
    }
}
