<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Doctrine\ORM\QueryBuilder;
use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Seedling;

/**
 * A garden's seedlings whose record query names no entity to select from, registered on no
 * panel.
 */
final class RootlessSeedlingResource extends Resource
{
    protected static ?string $recordClass = Seedling::class;

    protected static ?string $parentResource = GardenResource::class;

    public function getRecordQuery(): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder()->select('seedling');
    }
}
