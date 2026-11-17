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
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of `OrderLine`s whose rows carry two edit actions over the key's `line` half
 * and the note, the field locked with `disabledOn('edit')`: `edit`, and `editRenamed`,
 * whose operation that lock does not name.
 */
#[AsLiveComponent(name: 'Tables:EditableOrderLineTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class EditableOrderLineTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
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
            ->query($this->entityManager->createQueryBuilder()->select('line')->from(OrderLine::class, 'line'))
            ->columns([
                TextColumn::make('note'),
            ])
            ->recordActions([
                EditAction::make()->schema($this->fields()),
                EditAction::make('editRenamed')->schema($this->fields()),
            ]);
    }

    /**
     * @return list<TextInput>
     */
    private function fields(): array
    {
        return [
            TextInput::make('line')->integer()->disabledOn('edit'),
            TextInput::make('note'),
        ];
    }
}
