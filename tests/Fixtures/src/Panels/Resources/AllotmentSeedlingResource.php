<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditAllotmentSeedling;

/**
 * A allotment's seedlings as a resource nested under the allotment's, a parent keyed by its garden and a number:
 * a name field and an edit page.
 */
final class AllotmentSeedlingResource extends Resource
{
    protected static ?string $recordClass = Seedling::class;

    protected static ?string $parentResource = AllotmentResource::class;

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'edit' => EditAllotmentSeedling::route('/{record}/edit'),
        ];
    }
}
