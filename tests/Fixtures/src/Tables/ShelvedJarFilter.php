<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Filters\Filter;

/**
 * A checkbox filter that names itself: made with no name, it is `shelved` and keeps the jars
 * standing at a position past the first.
 */
class ShelvedJarFilter extends Filter
{
    public static function getDefaultName(): string
    {
        return 'shelved';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.position > 0'));
    }
}
