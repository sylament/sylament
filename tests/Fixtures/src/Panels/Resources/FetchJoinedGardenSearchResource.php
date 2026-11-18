<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Doctrine\ORM\QueryBuilder;
use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Garden;

/**
 * A searched garden whose global search query fetch-joins its seedlings as `seedlings` while it searches the dotted
 * `seedlings.name`, a head the search refuses to share.
 */
final class FetchJoinedGardenSearchResource extends Resource
{
    protected static ?string $recordClass = Garden::class;

    protected static ?string $recordTitleAttribute = 'name';

    public function getGloballySearchableAttributes(): array
    {
        return ['name', 'seedlings.name'];
    }

    public function getGlobalSearchRecordQuery(): QueryBuilder
    {
        return parent::getGlobalSearchRecordQuery()
            ->addSelect('seedlings')
            ->leftJoin('record.seedlings', 'seedlings');
    }
}
