<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Panels\Resources\PottedPlantResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * An edit page whose `content` schema holds a line of {@see self::CONTENT} where relation managers would go.
 */
#[AsLiveComponent]
final class EditPottedPlant extends EditRecord
{
    public const string CONTENT = 'Water weekly.';

    protected static string $resource = PottedPlantResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Text::make(self::CONTENT),
        ]);
    }
}
