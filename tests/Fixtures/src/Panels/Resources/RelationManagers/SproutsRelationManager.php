<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\RelationManagers;

use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * An owner's seedlings under a title, badge and badge tooltip that are catalogue messages, which a
 * manager can only return from its static getters.
 */
#[AsLiveComponent]
final class SproutsRelationManager extends RelationManager
{
    protected static string $relationship = 'seedlings';

    public static function getTitle(object $ownerRecord, string $pageClass): TranslatableInterface
    {
        return new TranslatableMessage('manager.title', [], 'fixtures-panels');
    }

    public static function getBadge(object $ownerRecord, string $pageClass): TranslatableInterface
    {
        return new TranslatableMessage('manager.badge', [], 'fixtures-panels');
    }

    public static function getBadgeTooltip(object $ownerRecord, string $pageClass): TranslatableInterface
    {
        return new TranslatableMessage('manager.tooltip', [], 'fixtures-panels');
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')]);
    }
}
