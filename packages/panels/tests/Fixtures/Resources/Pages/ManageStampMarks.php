<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\ManageRelatedRecords;
use Sylament\Panels\Tests\Fixtures\Resources\StampResource;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The routed host of the relationship tier over a composite owner: the page
 * resolves its stamp, and the refusal is its table's to carry.
 */
#[AsLiveComponent]
final class ManageStampMarks extends ManageRelatedRecords
{
    protected static string $resource = StampResource::class;

    protected static string $relationship = 'marks';

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('label')]);
    }
}
