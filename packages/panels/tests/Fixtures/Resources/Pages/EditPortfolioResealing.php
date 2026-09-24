<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Portfolio;
use Sylament\Panels\Tests\Fixtures\Resources\PortfolioResource;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A form that maps the title alone, and a save hook that renames the
 * portfolio's seal and moves its serial — a key change on a related record no
 * field of the form points at.
 */
#[AsLiveComponent]
final class EditPortfolioResealing extends EditRecord
{
    protected static string $resource = PortfolioResource::class;

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            TextInput::make('title'),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        if ($record instanceof Portfolio) {
            $record->getSeal()?->setTitle('Resealed')->setSerial('S9');
        }

        return $data;
    }
}
