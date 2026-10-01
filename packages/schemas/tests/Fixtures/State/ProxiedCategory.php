<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Category;

/**
 * Stands in for the proxy class Doctrine generates when native lazy objects are
 * off: a subclass of the entity carrying no mapping attribute of its own.
 */
final class ProxiedCategory extends Category
{
}
