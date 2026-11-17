<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Larder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The crocks of the larder named `Cellar`, a relation-scoped table whose header creates
 * crocks: `create` into the table's relationship, and `createAnnexed` into the crocks of
 * the larder named `Annex`, which its own `relationship()` names.
 */
#[AsLiveComponent(name: 'Tables:LarderCrockTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class LarderCrockTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(fn (): Relationship => new Relationship($this->larder('Cellar'), 'crocks'))
            ->columns([
                TextColumn::make('label'),
            ])
            ->headerActions([
                CreateAction::make()->schema($this->fields()),
                CreateAction::make('createAnnexed')
                    ->relationship(fn (): Relationship => new Relationship($this->larder('Annex'), 'crocks'))
                    ->schema($this->fields()),
            ]);
    }

    /**
     * @return list<TextInput>
     */
    private function fields(): array
    {
        return [
            TextInput::make('shelf'),
            TextInput::make('label'),
        ];
    }

    private function larder(string $name): Larder
    {
        return $this->entityManager->getRepository(Larder::class)->findOneBy(['name' => $name])
            ?? throw new LogicException("No larder named {$name} was seeded.");
    }
}
