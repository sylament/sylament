<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Actions\SecondManager;
use Sylament\Tests\Fixtures\Entity\VolumeSpine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateSecondManagerSpine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListSecondManagerSpines;

/**
 * Spines keyed by one assigned string, created through the request's {@see SecondManager} in place of the manager the
 * registry answers, with no edit page.
 */
final class SecondManagerSpineResource extends Resource
{
    protected static ?string $recordClass = VolumeSpine::class;

    public function __construct(
        private readonly SecondManager $secondManager,
    ) {
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->secondManager->get();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code'),
            TextInput::make('label'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('label'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListSecondManagerSpines::route('/'),
            'create' => CreateSecondManagerSpine::route('/create'),
        ];
    }
}
