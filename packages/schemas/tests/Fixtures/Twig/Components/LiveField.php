<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Twig\Components;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Support\Components\Attributes\ExposedLiveComponentMethod;

/**
 * A minimal live field fixture: binds to a state path and renders an `<input>`
 * carrying `getDataModel()`. Stands in for the real form `Field` (the `forms`
 * package is not yet ported) so T2's reactive loop has a binding surface.
 */
class LiveField extends Component
{
    protected string|Closure|null $template = '@SchemasFixtures/components/LiveField.html.twig';

    public static function make(string $name): static
    {
        return new static()->statePath($name);
    }

    /**
     * @return list<string>
     */
    #[ExposedLiveComponentMethod]
    public function echoForJs(string $value, int $times = 1): array
    {
        return array_fill(0, $times, $value);
    }

    public function notExposed(): string
    {
        return 'not exposed';
    }
}
