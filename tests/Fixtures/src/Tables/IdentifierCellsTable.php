<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\SelectColumn;
use Sylament\Tables\Columns\TextInputColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Actions\RecordingAssociationReader;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The lines of the composite-keyed class it is given, `OrderLine` or `InvoiceLine`, whose
 * cells edit the `line` half of their key and the note, read through an association reader
 * of the application's own; an invoice line's `invoice` half is a select over the invoices.
 */
#[AsLiveComponent(name: 'Tables:IdentifierCellsTable', template: '@Fixtures/tables/table.html.twig')]
class IdentifierCellsTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    /**
     * @var class-string<OrderLine|InvoiceLine>
     */
    #[LiveProp]
    public string $lineClass = OrderLine::class;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RecordingAssociationReader $reader,
    ) {
    }

    public function table(Table $table): Table
    {
        $table->setAssociationReader($this->reader);

        $columns = [
            TextInputColumn::make('line'),
            TextInputColumn::make('note'),
        ];

        if ($this->lineClass === InvoiceLine::class) {
            $columns[] = SelectColumn::make('invoice')->options([7 => 'Invoice 7', 8 => 'Invoice 8']);
        }

        return $table
            ->query($this->entityManager->createQueryBuilder()->select('line')->from($this->lineClass, 'line'))
            ->columns($columns);
    }
}
