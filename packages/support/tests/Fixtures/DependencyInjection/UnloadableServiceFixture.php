<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\DependencyInjection;

use Sylament\Missing\OptionalDependency\AbstractMissingBase;

/**
 * Stands in for a bundle service whose class extends an optional dependency that
 * is not installed: autoloading it throws because the parent is absent. The
 * static-instance pass must skip it rather than fatal.
 */
class UnloadableServiceFixture extends AbstractMissingBase
{
}
