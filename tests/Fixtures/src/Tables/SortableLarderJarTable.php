<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Schemas\Query\SortDirections;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A larder's jars a user sorts: by label, by position, by a supplier's id and by the date a
 * jar was sealed on, a column toggled off by default; never by its seal, which is not
 * sortable, nor by its id, which is sortable but hidden. It sorts by position by default.
 * Each variant configures one thing: the default sort (`noDefaultSort` declares none,
 * `pathDefaultSort` names the supplier's name, which no column carries,
 * `upperCaseDescDefaultSort` declares position `'DESC'`, `closureDefaultSort` orders the
 * builder it is handed by position in the direction it is handed, declared descending),
 * the tiebreaker (`noKeySort`), the query (`orderedQuery` orders it by label itself), the
 * pages (`paged`, two rows a page), the grouping (`groupsOnly` lists the seals alone), and
 * the lane (`relationship` lists the jars of the larder named `Pantry` instead of every
 * jar, `relationshipBesideQuery` declares that relationship beside the query, recording
 * in the {@see HookLog} whenever its closure runs).
 */
#[AsLiveComponent(name: 'Tables:SortableLarderJarTable', template: '@Fixtures/tables/table.html.twig')]
class SortableLarderJarTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

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
        $query = $this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar');

        if ($this->has('orderedQuery')) {
            $query->orderBy('jar.label');
        }

        $table = $this->has('relationship')
            ? $table->relationship(fn (): Relationship => new Relationship($this->pantry(), 'jars'))
            : $table->query($query);

        if ($this->has('relationshipBesideQuery')) {
            $table->relationship(function (): Relationship {
                $this->hookLog->record('relationship');

                return new Relationship($this->pantry(), 'jars');
            });
        }

        $table->columns([
            TextColumn::make('label')->sortable(),
            TextColumn::make('position')->sortable(),
            TextColumn::make('seal'),
            TextColumn::make('supplier.id')->sortable(),
            TextColumn::make('sealedOn')->date('Y-m-d')->sortable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('id')->sortable()->hidden(),
        ]);

        match (true) {
            $this->has('noDefaultSort') => null,
            $this->has('pathDefaultSort') => $table->defaultSort('supplier.name'),
            $this->has('upperCaseDescDefaultSort') => $table->defaultSort('position', 'DESC'),
            $this->has('closureDefaultSort') => $table->defaultSort(static fn (QueryBuilder $query, string $direction): QueryBuilder => $query->addOrderBy('jar.position', SortDirections::from($direction)), 'desc'),
            default => $table->defaultSort('position'),
        };

        if ($this->has('noKeySort')) {
            $table->defaultKeySort(false);
        }

        if ($this->has('paged')) {
            $table->paginated([2]);
        }

        if ($this->has('groupsOnly')) {
            $table->groups(['seal'])->defaultGroup('seal')->groupsOnly();
        }

        return $table;
    }

    private function has(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }

    private function pantry(): Larder
    {
        return $this->entityManager->getRepository(Larder::class)->findOneBy(['name' => 'Pantry'])
            ?? throw new LogicException('No larder named Pantry was seeded.');
    }
}
