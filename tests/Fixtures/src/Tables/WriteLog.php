<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * The records a table host logged during the request whose container holds it: the
 * editable-cell hosts receive it in place of the shared `logger`, and a test reads it
 * through `getContainer()` after the call.
 */
final class WriteLog extends AbstractLogger
{
    /**
     * @var list<array{level: mixed, message: string|Stringable, context: array<mixed>}>
     */
    public private(set) array $records = [];

    /**
     * @param array<mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }
}
