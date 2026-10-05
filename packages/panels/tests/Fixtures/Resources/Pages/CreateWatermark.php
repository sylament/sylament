<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use LogicException;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Watermark;
use Sylament\Panels\Tests\Fixtures\Resources\WatermarkResource;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Builds the watermark from the two key fields the form maps, counting its
 * calls. Its form also reads its binding in two visibility closures, as
 * {@see CreateHintedStamp}'s does, so a refused create shows whether the
 * record it bound for the mapping was put back.
 */
#[AsLiveComponent]
final class CreateWatermark extends CreateRecord
{
    public static int $madeRecords = 0;

    protected static string $resource = WatermarkResource::class;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            TextInput::make('mill')->required(),
            TextInput::make('pattern')->required(),
            TextInput::make('note'),
            TextInput::make('hint')->dehydrated(false)->visible(static fn (?Watermark $record): bool => ! $record instanceof Watermark),
            TextInput::make('classHint')->dehydrated(false)->visible(static fn (TextInput $component): bool => $component->getRecordClass() === Watermark::class),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function makeRecord(array $data): Watermark
    {
        self::$madeRecords++;

        $mill = $data['mill'] ?? null;
        $pattern = $data['pattern'] ?? null;

        if (! is_string($mill) || ! is_string($pattern)) {
            throw new LogicException('The watermark form maps its mill and pattern as strings, and makeRecord() reads both.');
        }

        return new Watermark($mill, $pattern);
    }
}
