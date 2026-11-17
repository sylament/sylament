<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Actions\RecordingAssociationReader;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of `CappedHeadline`s whose rows carry edit actions over the title: `edit` titled
 * by the record's title, `editWatched` whose schema holds an application's reader,
 * `editMutated` whose fill upper-cases the title, and `editListed` whose fill mutator
 * answers a list.
 */
#[AsLiveComponent(name: 'Tables:EditableHeadlineTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class EditableHeadlineTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RecordingAssociationReader $reader,
    ) {
    }

    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('headline')->from(CappedHeadline::class, 'headline'))
            ->columns([
                TextColumn::make('title'),
            ])
            ->recordActions([
                EditAction::make()
                    ->recordTitleAttribute('title')
                    ->schema([TextInput::make('title')]),
                EditAction::make('editWatched')
                    ->schema(fn (Schema $schema): Schema => $this->watched($schema)->components([TextInput::make('title')])),
                EditAction::make('editMutated')
                    ->schema([TextInput::make('title')])
                    ->mutateRecordDataUsing(fn (array $data): array => [...$data, 'title' => 'Filled by the mutator']),
                EditAction::make('editListed')
                    ->schema([TextInput::make('title')])
                    ->mutateRecordDataUsing(fn (array $data): array => array_values($data)),
            ]);
    }

    private function watched(Schema $schema): Schema
    {
        $schema->setAssociationReader($this->reader);

        return $schema;
    }
}
