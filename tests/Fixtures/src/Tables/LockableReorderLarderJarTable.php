<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A larder's jars, two a page, sortable by label and reordered by position while `locked`
 * is off, recording in the {@see HookLog} the order before and after each reorder as
 * `before <keys>` and `after <keys>`. Each variant configures one thing: the direction
 * (`desc`), the query (`scoped` leaves out the jar labelled `Spare`), the pages while
 * reordering (`paginatedWhileReordering`), and the rows (`arrayRecords` lists plain arrays).
 */
#[AsLiveComponent(name: 'Tables:LockableReorderLarderJarTable', template: '@Fixtures/tables/table.html.twig')]
class LockableReorderLarderJarTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    /**
     * @var list<string>
     */
    #[LiveProp]
    public array $variants = [];

    #[LiveProp(writable: true)]
    public bool $locked = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        if ($this->has('arrayRecords')) {
            $table->records(static fn (): array => [
                ['__key' => 'plum', 'label' => 'Plum', 'position' => 1],
            ]);
        } else {
            $table->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'));
        }

        if ($this->has('scoped')) {
            $table->modifyQueryUsing(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("jar.label <> 'Spare'"));
        }

        return $table
            ->columns([
                TextColumn::make('label')->sortable(),
                TextColumn::make('position'),
            ])
            ->defaultSort('position')
            ->paginated([2])
            ->paginatedWhileReordering($this->has('paginatedWhileReordering'))
            ->reorderable('position', condition: fn (): bool => !$this->locked, direction: $this->has('desc') ? 'desc' : null)
            ->beforeReordering(function (array $order): void {
                $this->hookLog->record('before ' . implode(',', array_filter($order, is_string(...))));
            })
            ->afterReordering(function (array $order): void {
                $this->hookLog->record('after ' . implode(',', array_filter($order, is_string(...))));
            });
    }

    private function has(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }
}
