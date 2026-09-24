<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ReplicateAction;
use Sylament\Actions\ViewAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateImprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditImprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ImprintProbePage;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListImprints;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The flat composite resource: a scalar pair with every page a
 * single-identifier resource has, a record page of its own, and both identifier
 * fields exposed as ordinary editable inputs.
 */
final class ImprintResource extends Resource
{
    protected static ?string $recordClass = Imprint::class;

    protected static ?string $recordTitleAttribute = 'label';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('house')->required(),
            TextInput::make('year')->required(),
            TextInput::make('label'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('house'),
                TextColumn::make('year'),
                TextColumn::make('label'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ReplicateAction::make()->schema(fn (Schema $schema): Schema => $this->form($schema)),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    /**
     * @return array<string|array<string>>
     */
    public function getGloballySearchableAttributes(): array
    {
        return ['label', 'house', 'catalogue.title'];
    }

    /**
     * @return array<NavigationItem>
     */
    public function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([EditImprint::class, ImprintProbePage::class]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListImprints::route('/'),
            'create' => CreateImprint::route('/create'),
            'edit' => EditImprint::route('/{record}/edit'),
            'probe' => new PageRegistration(ImprintProbePage::class, '/{record}/probe'),
        ];
    }
}
