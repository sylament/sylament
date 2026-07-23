<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Utilities;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\State\PropertyAccessor;

final readonly class Get
{
    public function __construct(
        private Component $component,
    ) {
        //
    }

    public function __invoke(string|Component $path = '', bool $isAbsolute = false): mixed
    {
        $path = $this->component->resolveRelativeStatePath($path, $isAbsolute);

        return PropertyAccessor::getInstance()->get($this->component->getLiveComponent(), $path);
    }
}
