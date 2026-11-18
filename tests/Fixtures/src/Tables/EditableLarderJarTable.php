<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\SelectColumn;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Columns\TextInputColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The jars at a non-negative position, each cell editing one of their properties: the
 * label (at most twelve characters, locked on a wax-sealed jar, its before hook recording
 * the state it replaces and its after hook the row as stored), the position (which must be
 * given), the sealing date, the seal, the supplier's name (any scalar), and the larder's
 * jars, a collection no cell can hold; the larder's name opens the jar.
 */
#[AsLiveComponent(name: 'Tables:EditableLarderJarTable', template: '@Fixtures/tables/table.html.twig')]
class EditableLarderJarTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar')->where('jar.position >= 0'))
            ->columns([
                TextInputColumn::make('label')
                    ->constraint(new Length(max: 12))
                    ->validationMessages([Length::class => ['maxMessage' => 'Keep the label to {{ limit }} characters.']])
                    ->disabled(static fn (LarderJar $record): bool => $record->seal === JarSeal::Wax)
                    ->beforeStateUpdated(function (TextInputColumn $column, LarderJar $record, mixed $state): void {
                        $this->hookLog->record(sprintf('before the label of %s: %s replaces %s', $record->label, var_export($state, true), var_export($column->getState(), true)));
                    })
                    ->afterStateUpdated(function (LarderJar $record): void {
                        $stored = $this->entityManager->getConnection()->fetchOne(
                            sprintf('SELECT label FROM %s WHERE id = ?', $this->entityManager->getClassMetadata(LarderJar::class)->getTableName()),
                            [$record->id],
                        );

                        $this->hookLog->record(sprintf('after the label: the row reads %s', var_export($stored, true)));
                    }),
                TextInputColumn::make('position')->constraint(new NotBlank(message: 'Number the jar.')),
                TextInputColumn::make('sealedOn'),
                SelectColumn::make('seal')->enum(JarSeal::class),
                TextInputColumn::make('supplier.name')->constraint(new Type(type: 'scalar', message: 'Name the supplier plainly.')),
                TextInputColumn::make('larder.jars'),
                TextColumn::make('larder.name')->action(function (LarderJar $record): void {
                    $this->hookLog->record("open {$record->label}");
                }),
            ]);
    }
}
