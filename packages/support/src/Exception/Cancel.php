<?php

declare(strict_types=1);

namespace Sylament\Support\Exception;

use Exception;

/**
 * Thrown by an action closure to abort the lifecycle and unmount the action
 * (closing the modal), without a failure notification.
 */
class Cancel extends Exception
{
}
