<?php

declare(strict_types=1);

namespace Sylament\Support\Exceptions;

use Exception;

/**
 * Thrown by an action closure to stop the lifecycle cleanly while keeping the
 * modal mounted (no failure notification). Filament places this in support
 * because multiple packages throw it; the DB-transaction rollback control lands
 * with Doctrine.
 */
class Halt extends Exception
{
}
