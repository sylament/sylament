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
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of larder jars whose rows link to each jar, the `Hazard` jar's link an unsafe
 * scheme, under link attributes naming an `href`, a `target` and a data attribute of
 * their own, which a second call replaces or merges into; with a record action named
 * instead, its rows click that action and link nowhere. The new tab, the merge and the
 * record action are mount props.
 */
#[AsLiveComponent(name: 'Tables:LinkedLarderJarTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class LinkedLarderJarTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public bool $opensInNewTab = false;

    #[LiveProp]
    public bool $mergesLinkAttributes = true;

    #[LiveProp]
    public ?string $clickAction = null;

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
                TextColumn::make('position'),
            ])
            ->defaultSort('label')
            ->openRecordUrlInNewTab($this->opensInNewTab)
            ->recordUrl($this->clickAction === null ? static fn (LarderJar $record): string => $record->label === 'Hazard' ? 'javascript:alert(1)' : "/jars/{$record->id}" : null)
            ->extraRecordLinkAttributes(['data-shelf' => 'top'])
            ->extraRecordLinkAttributes(function (LarderJar $record): array {
                $this->hookLog->record("link attributes {$record->label}");

                return ['href' => '/elsewhere', 'target' => '_self', 'data-jar' => $record->label];
            }, merge: $this->mergesLinkAttributes)
            ->recordAction($this->clickAction)
            ->recordActions([
                Action::make('view')->action(function (LarderJar $record): void {
                    $this->hookLog->record("view {$record->label}");
                }),
            ]);
    }

    #[LiveAction]
    public function inspectJar(#[LiveArg] string $recordKey): void
    {
        $this->hookLog->record("inspect {$recordKey}");
    }
}
