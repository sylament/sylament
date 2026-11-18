<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

/**
 * A table of larder jars whose rows link to each jar and whose columns click
 * differently: a sortable label linking elsewhere in a new tab, a position that mounts
 * an action, a shelf that calls a closure, a maker linking by its own state, a seal
 * date that clicks nothing, and a seal under a sortable markup label that keeps the
 * row's link.
 */
#[AsLiveComponent(name: 'Tables:ClickableCellsLarderJarTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ClickableCellsLarderJarTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label')
                    ->sortable()
                    ->url(static fn (LarderJar $record): string => "/labels/{$record->id}", shouldOpenInNewTab: true),
                TextColumn::make('position')->action(Action::make('restack')->action(function (LarderJar $record): void {
                    $this->hookLog->record("restack {$record->label}");
                })),
                TextColumn::make('shelf')
                    ->state('Top')
                    ->action(function (LarderJar $record): void {
                        $this->hookLog->record("shelve {$record->label}");
                    }),
                TextColumn::make('maker')
                    ->state('Acme')
                    ->url(static fn (string $state): string => "/makers/{$state}"),
                TextColumn::make('sealedOn')->disabledClick(),
                TextColumn::make('seal')
                    ->label(new Markup('<em>Seal</em>', 'UTF-8'))
                    ->sortable(),
            ])
            ->defaultSort('label')
            ->recordUrl(static fn (LarderJar $record): string => "/jars/{$record->id}");
    }
}
