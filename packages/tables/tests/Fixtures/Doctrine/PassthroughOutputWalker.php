<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine;

use Doctrine\ORM\Query\SqlOutputWalker;

/**
 * An output walker an application sets and this toolkit must not refuse — the
 * shape `Configuration::setDefaultQueryHints()` installs for Gedmo Translatable,
 * standing in here for anything a consumer hangs on
 * {@see \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER}. It emits Doctrine's own
 * SQL unchanged, so a query carrying it answers exactly the rows it would
 * without it, which is what lets a test tell a working read from a refused one.
 *
 * Extends `SqlOutputWalker` rather than `SqlWalker`, because Doctrine 3.6
 * deprecates an output walker that does not implement
 * {@see \Doctrine\ORM\Query\OutputWalker} (`Query.php:679`).
 */
final class PassthroughOutputWalker extends SqlOutputWalker
{
}
