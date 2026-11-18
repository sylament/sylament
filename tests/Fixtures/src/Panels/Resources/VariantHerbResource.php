<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Operation;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\CompanionHerb;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditActionedHerb;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditLoggedHerb;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditRetitledHerb;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListVariantHerbs;

/**
 * Herbs, whose form maps a name no other herb carries, a Latin name only outside an edit and a line only an edit shows;
 * it mounts the edit page's variants each under its own key, and registers no create, view or edit page, so a header
 * action stays a modal and the record's crumb links nowhere.
 */
final class VariantHerbResource extends Resource
{
    public const string EDIT_ONLY_LINE = 'Trim before it flowers.';

    protected static ?string $recordClass = CompanionHerb::class;

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->unique(),
            TextInput::make('latinName')->hiddenOn(Operation::Edit),
            Text::make(self::EDIT_ONLY_LINE)->visibleOn(Operation::Edit),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListVariantHerbs::route('/'),
            'logged' => EditLoggedHerb::route('/{record}/logged'),
            'retitled' => EditRetitledHerb::route('/{record}/retitled'),
            'actioned' => EditActionedHerb::route('/{record}/actioned'),
        ];
    }
}
