<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture\Trail;

/**
 * The port trail's status vocabulary, as docs/progress/README.md defines it.
 */
enum Status: string
{
    case Todo = 'todo';
    case InProgress = 'in progress';
    case Blocked = 'blocked';
    case Done = 'done';
    case Skipped = 'skipped';
}
