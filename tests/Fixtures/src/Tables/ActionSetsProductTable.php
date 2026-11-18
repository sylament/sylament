<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\BulkAction;
use Sylament\Actions\BulkActionGroup;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Enums\HeaderActionsPosition;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A product table whose toolbar and header action sets are declared, replaced and pushed
 * as its `setup` says, every action that runs recording which one ran in the
 * {@see HookLog}: a set replaced by a second call (`toolbarReplaced`, `headerReplaced`,
 * the first set a `purge` bulk action, the second holding a hidden `audit` and, in the
 * toolbar, a group of `print` and `mail`), a `purge` set pushed onto (`toolbarPushed`,
 * `headerPushed`), header actions placed at the bottom (`headerBottom`), a plain `peek`
 * made bulk as the only toolbar action (`peeking`), a header and a toolbar `note` under
 * one name with the header set or the toolbar set replaced afterwards (`shadowed`,
 * `evicted`), and every kind of bulk-only trigger beside a plain one in both bands
 * (`bulkOnly`).
 */
#[AsLiveComponent(name: 'Tables:ActionSetsProductTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ActionSetsProductTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public string $setup = 'toolbarReplaced';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        $table
            ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->columns([
                TextColumn::make('name'),
            ])
            ->defaultSort('name');

        return match ($this->setup) {
            'toolbarReplaced' => $table
                ->toolbarActions([BulkAction::make('purge')])
                ->toolbarActions([
                    $this->logged('export'),
                    Action::make('audit')->hidden(),
                    ActionGroup::make([$this->logged('print'), $this->logged('mail')]),
                ]),
            'toolbarPushed' => $table
                ->toolbarActions([BulkAction::make('purge')])
                ->pushToolbarActions([$this->logged('export')]),
            'headerReplaced' => $table
                ->headerActions([BulkAction::make('purge')])
                ->headerActions([$this->logged('restock'), Action::make('audit')->hidden()]),
            'headerPushed' => $table
                ->headerActions([BulkAction::make('purge')])
                ->pushHeaderActions([$this->logged('restock')]),
            'headerBottom' => $table->headerActions([$this->logged('restock')], position: HeaderActionsPosition::Bottom),
            'peeking' => $table->toolbarActions([
                Action::make('peek')->bulk()->accessSelectedRecords()->action(function (array $records): void {
                    $this->hookLog->record('peek read ' . implode(', ', array_map(
                        static fn (mixed $record): string => $record instanceof Product ? $record->name : get_debug_type($record),
                        $records,
                    )));
                }),
            ]),
            'shadowed' => $table
                ->headerActions([$this->logged('note', by: 'header')])
                ->toolbarActions([$this->logged('note', by: 'toolbar')])
                ->headerActions([$this->logged('restock')]),
            'evicted' => $table
                ->headerActions([$this->logged('note', by: 'header')])
                ->toolbarActions([$this->logged('note', by: 'toolbar')])
                ->toolbarActions([$this->logged('export')]),
            'bulkOnly' => $table
                ->headerActions([BulkAction::make('purge'), $this->logged('restock')])
                ->toolbarActions([
                    Action::make('peek')->bulk(),
                    ActionGroup::make([BulkAction::make('archive')])->label('Archiving'),
                    BulkActionGroup::make([BulkAction::make('tidy')]),
                    $this->logged('export'),
                ]),
            default => throw new LogicException("No setup [{$this->setup}]."),
        };
    }

    private function logged(string $name, string $by = ''): Action
    {
        return Action::make($name)->action(function () use ($name, $by): void {
            $this->hookLog->record(mb_trim("{$by} {$name}"));
        });
    }
}
