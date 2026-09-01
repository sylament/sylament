<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\CreateAction;
use Sylament\Panels\Resources\Pages\ManageRelatedRecords;
use Sylament\Panels\Tests\Fixtures\Resources\MrrChecklistResource;
use Sylament\Panels\Tests\Fixtures\Resources\MrrProjectResource;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The same `tasks` association as {@see ManageMrrProjectTasks}, related to a
 * resource that registers no create page — so the header create action mounts a
 * modal rather than linking, which is what puts the page's action hooks on the
 * only path that reaches them.
 *
 * It is deliberately **not** keyed by the relationship name: the related
 * resource nests under nothing, so no index url of its own ever looks this page
 * up on the parent.
 */
#[AsLiveComponent]
final class ManageMrrProjectChecklist extends ManageRelatedRecords
{
    protected static string $resource = MrrProjectResource::class;

    protected static string $relationship = 'tasks';

    protected static ?string $relatedResource = MrrChecklistResource::class;

    /**
     * @return array<Action|ActionGroup>
     */
    public function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function table(Table $table): Table
    {
        // A table action that is neither a create nor a row frame: the only
        // shape the page's `$action->getTable()` guard is reachable through.
        return $table->headerActions([Action::make('probe')->requiresConfirmation()]);
    }
}
