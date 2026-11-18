<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

/**
 * A table of larder jars with an empty state of its own: a heading, an icon, a
 * description, and actions declared, replaced and pushed, among them a `create`, a
 * hidden one and a group. The description and a markup block replacing the whole
 * state are mount props.
 */
#[AsLiveComponent(name: 'Tables:EmptyStateLarderJarTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class EmptyStateLarderJarTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public ?string $emptyStateDescription = 'Seal a jar to fill the shelf.';

    #[LiveProp]
    public bool $replacesEmptyState = false;

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
                TextColumn::make('label'),
            ])
            ->defaultSort('label')
            ->emptyStateHeading('The larder is bare')
            ->emptyStateDescription($this->emptyStateDescription)
            ->emptyStateIcon('support:star')
            ->emptyStateActions([Action::make('discard')])
            ->emptyStateActions([
                Action::make('create')->action(function (): void {
                    $this->hookLog->record('create');
                }),
                Action::make('restock')->hidden(),
                ActionGroup::make([Action::make('import'), Action::make('borrow')]),
            ])
            ->pushEmptyStateActions([Action::make('donate')])
            ->emptyState($this->replacesEmptyState ? new Markup('<p class="jar-shelf-note">Nothing on this shelf yet.</p>', 'UTF-8') : null);
    }
}
