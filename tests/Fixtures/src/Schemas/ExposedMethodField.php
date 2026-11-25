<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\Field;
use Sylament\Support\Components\Attributes\Exposed;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * A field whose methods each stand on one side of the browser-callable gate: exposed and
 * public, public but unexposed, exposed but protected, and exposed with a result JSON
 * cannot carry. Each records its call in the request's {@see HookLog}.
 */
class ExposedMethodField extends Field
{
    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    #[Exposed]
    public function greet(string $name, string $greeting = 'Hello'): string
    {
        $this->hookLog->record("greet: {$name}");

        return "{$greeting}, {$name}";
    }

    public function unexposed(): string
    {
        $this->hookLog->record('unexposed');

        return 'unexposed';
    }

    #[Exposed]
    public function rawBytes(): string
    {
        $this->hookLog->record('rawBytes');

        return "\xB1\x31";
    }

    #[Exposed]
    protected function guarded(): string
    {
        $this->hookLog->record('guarded');

        return 'guarded';
    }
}
