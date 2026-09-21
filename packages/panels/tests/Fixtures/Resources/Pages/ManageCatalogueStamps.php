<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Panels\Resources\Pages\ManageRelatedRecords;
use Sylament\Panels\Tests\Fixtures\Resources\CatalogueResource;
use Sylament\Panels\Tests\Fixtures\Resources\StampResource;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The routed half of the relationship tier over the same association the
 * embedded manager lists, so both hosts are exercised against one composite
 * target.
 */
#[AsLiveComponent]
final class ManageCatalogueStamps extends ManageRelatedRecords
{
    protected static string $resource = CatalogueResource::class;

    protected static string $relationship = 'stamps';

    protected static ?string $relatedResource = StampResource::class;

    public function table(Table $table): Table
    {
        return $table->headerActions([CreateAction::make()]);
    }
}
