<?php

declare(strict_types=1);

namespace Sylament\Schemas\Validation\Contracts;

/**
 * Marks a constraint that must still run when the value under validation is a
 * blank string — a constraint about presence itself rather than about content.
 */
interface ImplicitConstraint
{
}
