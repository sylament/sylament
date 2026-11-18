<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Support\Enums\Size;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Enums\RecordActionsPosition;
use Sylament\Tables\Enums\RecordCheckboxPosition;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A product table whose record actions replace a first set (`discard`) with `open`, a
 * `restock` hidden for the Lamp, a `ship` given a button, a `weigh` given a large size and
 * a group holding `rename`, every one of them hidden for a product named Sealed; `open` and
 * `rename` record the product they ran on in the {@see HookLog}. The actions sit where
 * `position` names a {@see RecordActionsPosition} case (the default without one), and with
 * `selectable` an `archive` bulk action turns the checkboxes on, placed where
 * `checkboxPosition` names a {@see RecordCheckboxPosition} case.
 */
#[AsLiveComponent(name: 'Tables:PositionedRecordActionsProductTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class PositionedRecordActionsProductTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public ?string $position = null;

    #[LiveProp]
    public bool $selectable = false;

    #[LiveProp]
    public ?string $checkboxPosition = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        $sealed = static fn (Product $record): bool => $record->name === 'Sealed';

        return $table
            ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->columns([
                TextColumn::make('name'),
            ])
            ->defaultSort('name')
            ->recordActions([Action::make('discard')])
            ->recordActions([
                $this->logged('open')->hidden($sealed),
                Action::make('restock')->hidden(static fn (Product $record): bool => in_array($record->name, ['Lamp', 'Sealed'], true)),
                Action::make('ship')->button()->hidden($sealed),
                Action::make('weigh')->size(Size::Large)->hidden($sealed),
                ActionGroup::make([$this->logged('rename')->hidden($sealed)]),
            ], position: array_find(RecordActionsPosition::cases(), fn (RecordActionsPosition $case): bool => $case->name === $this->position))
            ->recordCheckboxPosition(array_find(RecordCheckboxPosition::cases(), fn (RecordCheckboxPosition $case): bool => $case->name === $this->checkboxPosition))
            ->toolbarActions($this->selectable ? [BulkAction::make('archive')] : []);
    }

    private function logged(string $name): Action
    {
        return Action::make($name)->action(function (Product $record) use ($name): void {
            $this->hookLog->record("{$name} {$record->name}");
        });
    }
}
