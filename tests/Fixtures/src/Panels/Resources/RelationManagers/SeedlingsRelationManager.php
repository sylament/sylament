<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\RelationManagers;

use BackedEnum;
use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\IconPosition;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Twig\Markup;

/**
 * An owner's seedlings by name, sortable, created, viewed, edited and deleted through the
 * manager's own form, sown by a create action the manager offers outside its table, and shown in
 * the tab bar with every tab static set; its `table()` hook records each run in the
 * {@see HookLog}.
 */
#[AsLiveComponent]
final class SeedlingsRelationManager extends RelationManager
{
    protected static string $relationship = 'seedlings';

    protected static string|BackedEnum|null $icon = 'heroicons:check-circle';

    protected static IconPosition $iconPosition = IconPosition::After;

    protected static ?string $badge = 'Sown';

    protected static ?string $badgeColor = 'success';

    protected static string|Markup|null $badgeTooltip = 'Sown this season';

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        $this->hookLog->record('table');

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->sortable(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()]);
    }

    public function sowAction(): CreateAction
    {
        return CreateAction::make('sow')->recordClass(Seedling::class);
    }
}
