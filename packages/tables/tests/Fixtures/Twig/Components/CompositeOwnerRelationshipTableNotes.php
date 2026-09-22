<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Columns\TextInputColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ShortcutNote;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * One scalar-pair record's own collection: a relation-scoped table whose owner
 * is keyed by two fields, carrying the surfaces that resolve a row back to a
 * record — a confirming record action, an editable cell, a per-row link and a
 * by-key bulk delete.
 *
 * Both halves of the owner's key are `LiveProp`s, so one host serves the owner
 * a test wants and the sibling that shares only its first half.
 *
 * `#[ORM\OrderBy(['heading' => 'ASC'])]` on {@see Shortcut::$notes} is what
 * gives the table a default sort with no `defaultSort()` of its own.
 */
#[AsLiveComponent(name: 'CompositeOwnerRelationshipTableNotes', template: '@TablesFixtures/components/CompositeOwnerRelationshipTableNotes.html.twig')]
class CompositeOwnerRelationshipTableNotes implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public string $ownerHost = 'plain';

    #[LiveProp]
    public string $ownerPath = 'about';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(function (): ?Relationship {
                $shortcut = $this->entityManager->find(Shortcut::class, ['host' => $this->ownerHost, 'path' => $this->ownerPath]);

                return $shortcut instanceof Shortcut ? new Relationship($shortcut, 'notes') : null;
            })
            ->columns([
                TextInputColumn::make('heading'),
                TextColumn::make('position'),
            ])
            ->recordUrl(static fn (ShortcutNote $record): string => '/notes/' . $record->getId())
            ->recordActions([
                Action::make('promote')
                    ->requiresConfirmation()
                    ->action(function (ShortcutNote $record): void {
                        $record->setHeading($record->getHeading() . ' (promoted)');

                        $this->entityManager->flush();
                    }),
            ])
            ->toolbarActions([DeleteBulkAction::make()->fetchSelectedRecords(false)])
            ->paginated([10]);
    }
}
