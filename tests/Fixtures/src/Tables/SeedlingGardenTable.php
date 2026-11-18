<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A seedling table a user sorts and searches: `name` sorts, searches globally and on its own
 * input without splitting its own term into words, `garden.name` reads through the to-one,
 * sorts by the garden's name and then its own and searches on its own input alone, and `id`
 * does neither. It lists every seedling, or with `gardenId` one garden's through
 * `relationship()`. Each variant configures one thing: `siblings` adds a `garden.seedlings.name`
 * column read through a collection, `queryBeside` declares a query over every seedling beside
 * the relationship, and `ownerless` hands the relationship no owner; the relationship closure
 * records each evaluation in the {@see HookLog}.
 */
#[AsLiveComponent(name: 'Tables:SeedlingGardenTable', template: '@Fixtures/tables/table.html.twig')]
class SeedlingGardenTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    #[LiveProp]
    public ?int $gardenId = null;

    /**
     * @var list<string>
     */
    #[LiveProp]
    public array $variants = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        if (($this->gardenId === null && !$this->has('ownerless')) || $this->has('queryBeside')) {
            $table->query(fn (): QueryBuilder => $this->entityManager->createQueryBuilder()->select('seedling')->from(Seedling::class, 'seedling'));
        }

        if ($this->gardenId !== null || $this->has('ownerless')) {
            $table->relationship(function (): ?Relationship {
                $this->hookLog->record('relationship evaluated');

                $garden = $this->has('ownerless') ? null : $this->entityManager->find(Garden::class, $this->gardenId);

                return $garden instanceof Garden ? new Relationship($garden, 'seedlings') : null;
            });
        }

        $columns = [
            TextColumn::make('name')
                ->sortable()
                ->searchable(isIndividual: true)
                ->splitIndividualSearchTerms(false),
            TextColumn::make('garden.name')
                ->sortable(['garden.name', 'name'])
                ->searchable(isIndividual: true, isGlobal: false),
            TextColumn::make('id'),
        ];

        if ($this->has('siblings')) {
            $columns[] = TextColumn::make('garden.seedlings.name');
        }

        return $table->columns($columns);
    }

    private function has(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }
}
