<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

/**
 * The hooks a host ran, in order, during the request whose container holds it: a test
 * reads it through `getContainer()` after the call.
 */
final class HookLog
{
    /**
     * @var list<string>
     */
    public private(set) array $hooks = [];

    public function record(string $hook): void
    {
        $this->hooks[] = $hook;
    }
}
