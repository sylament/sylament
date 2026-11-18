<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Filters\Filter;

/**
 * A checkbox filter that names itself: made with no name, it is `sealed`, reads "Sealed jars"
 * and keeps the jars under a seal.
 */
class SealedJarFilter extends Filter
{
    public static function getDefaultName(): string
    {
        return 'sealed';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Sealed jars');

        $this->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.seal IS NOT NULL'));
    }
}
