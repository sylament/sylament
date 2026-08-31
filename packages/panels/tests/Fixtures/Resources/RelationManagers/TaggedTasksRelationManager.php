<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\RelationManagers;

use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * The configured manager: it declares a default property, so a registration
 * that names the same key proves the configuration's properties are spread
 * last.
 */
#[AsLiveComponent]
final class TaggedTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    #[LiveProp]
    public ?string $tag = null;

    /**
     * @return array<string, mixed>
     */
    public static function getDefaultProperties(): array
    {
        return ['tag' => 'default'];
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')]);
    }
}
