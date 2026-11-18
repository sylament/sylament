<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Doctrine\ORM\QueryBuilder;
use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\LarderBin;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditLarderBin;

/**
 * A larder's bins, keyed by their code, as a resource nested under the stocked larder's, titled
 * by code, whose record query hides every bin coded `HIDDEN…`: an edit page.
 */
final class LarderBinResource extends Resource
{
    public const string HIDDEN_PREFIX = 'HIDDEN';

    protected static ?string $recordClass = LarderBin::class;

    protected static ?string $parentResource = StockedLarderResource::class;

    protected static ?string $recordTitleAttribute = 'code';

    public function getRecordQuery(): QueryBuilder
    {
        return parent::getRecordQuery()
            ->andWhere('record.code NOT LIKE :hidden_bin')
            ->setParameter('hidden_bin', self::HIDDEN_PREFIX . '%');
    }

    public function getPages(): array
    {
        return [
            'edit' => EditLarderBin::route('/{record}/edit'),
        ];
    }
}
