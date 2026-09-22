<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Panels\Resources\Pages\ManageRelatedRecords;
use Sylament\Panels\Tests\Fixtures\Resources\BinderyResource;
use Sylament\Panels\Tests\Fixtures\Resources\ImprintBindingResource;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The routed host of the relationship tier over a scalar-pair owner: it names
 * a related resource, so its row and header URLs are minted with the owner
 * standing in as that resource's own parent parameter.
 */
#[AsLiveComponent]
final class ManageBinderyBindings extends ManageRelatedRecords
{
    protected static string $resource = BinderyResource::class;

    protected static string $relationship = 'bindings';

    protected static ?string $relatedResource = ImprintBindingResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([TextColumn::make('title')])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
