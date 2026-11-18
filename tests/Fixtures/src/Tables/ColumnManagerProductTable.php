<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Enums\ColumnManagerLayout;
use Sylament\Tables\Enums\ColumnManagerResetActionPosition;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Products under an `id` column nobody may toggle, a toggleable `name` and a toggleable
 * `code` (the name upper-cased) hidden until a user shows it, edited in a deferred
 * dropdown column manager remembered in the session.
 * Each variant configures one thing, and `variants` is writable so a later request can
 * change the definition under a state already applied: `managerOff` and `managerOn` answer
 * `columnManager()` explicitly, `live` applies each change at once, `reorderable` lets the
 * columns be dragged, `modal` opens the manager in a modal, `resetInFooter` moves its reset
 * beside the apply button, `sized` lays its items out in two columns in a `30rem` wide
 * dropdown at most `20rem` high, `wide` gives it the `lg` width token, `customized` relabels and recolours the trigger and gives
 * it attributes of its own and relabels the apply button, `plain` makes `name` and `code`
 * untoggleable, `hiddenToggleable` adds a toggleable `secret` column the developer hid,
 * `blankLabel` blanks the `name` label, `codeDropped` drops `code`, `noteAdded` declares a
 * toggleable `note` column between `name` and `code`, and `idToggleable` makes `id`
 * toggleable, hidden until shown.
 */
#[AsLiveComponent(name: 'Tables:ColumnManagerProductTable', template: '@Fixtures/tables/table.html.twig')]
class ColumnManagerProductTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    /**
     * @var list<string>
     */
    #[LiveProp(writable: true)]
    public array $variants = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        $toggleable = !$this->has('plain');

        $columns = [
            TextColumn::make('id')->label('ID')->toggleable($this->has('idToggleable'), isToggledHiddenByDefault: true),
            TextColumn::make('name')->label($this->has('blankLabel') ? '' : 'Name')->toggleable($toggleable),
        ];

        if ($this->has('noteAdded')) {
            $columns[] = TextColumn::make('note')->label('Note')->state('Fragile')->toggleable();
        }

        if (!$this->has('codeDropped')) {
            $columns[] = TextColumn::make('code')
                ->label('Code')
                ->state(static fn (Product $record): string => mb_strtoupper($record->name))
                ->toggleable($toggleable, isToggledHiddenByDefault: true);
        }

        if ($this->has('hiddenToggleable')) {
            $columns[] = TextColumn::make('secret')->label('Secret')->state('Hush')->hidden()->toggleable();
        }

        $table
            ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->columns($columns)
            ->defaultSort('name')
            ->deferColumnManager(!$this->has('live'))
            ->reorderableColumns($this->has('reorderable'));

        if ($this->has('managerOff')) {
            $table->columnManager(false);
        }

        if ($this->has('managerOn')) {
            $table->columnManager();
        }

        if ($this->has('modal')) {
            $table->columnManagerLayout(ColumnManagerLayout::Modal);
        }

        if ($this->has('resetInFooter')) {
            $table->columnManagerResetActionPosition(ColumnManagerResetActionPosition::Footer);
        }

        if ($this->has('sized')) {
            $table->columnManagerColumns(2)->columnManagerWidth('30rem')->columnManagerMaxHeight('20rem');
        }

        if ($this->has('wide')) {
            $table->columnManagerWidth('lg');
        }

        if ($this->has('customized')) {
            $table
                ->columnManagerTriggerAction(static function (Action $action): void {
                    $action->label('Choose columns')->color('primary')->extraAttributes(['data-customized' => 'trigger']);
                })
                ->columnManagerApplyAction(static fn (Action $action): Action => $action->label('Show these columns'));
        }

        return $table;
    }

    protected function has(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }
}
