<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Resources\StampResource;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A nested create whose form reads its binding in two visibility closures:
 * the hint shows while the form is bound to no record, and the class hint
 * while it is bound to the record class, so whatever record a create binds
 * for its mapping must be gone again, and the class back, by the time a
 * refusal re-renders the form.
 */
#[AsLiveComponent]
final class CreateHintedStamp extends CreateRecord
{
    protected static string $resource = StampResource::class;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            TextInput::make('code')->required(),
            TextInput::make('caption'),
            TextInput::make('hint')->dehydrated(false)->visible(static fn (?Stamp $record): bool => ! $record instanceof Stamp),
            TextInput::make('classHint')->dehydrated(false)->visible(static fn (TextInput $component): bool => $component->getRecordClass() === Stamp::class),
        ]);
    }
}
