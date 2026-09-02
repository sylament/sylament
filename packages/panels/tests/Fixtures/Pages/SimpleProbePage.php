<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Pages;

use Sylament\Panels\Pages\SimplePage;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Width;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class SimpleProbePage extends SimplePage
{
    protected static ?string $slug = 'simple';

    protected static ?string $title = 'Probe Simple';

    protected ?string $subheading = 'A card';

    protected Width|string|null $maxWidth = Width::Medium;

    public function content(Schema $schema): Schema
    {
        return $schema->components([Text::make('simple body')]);
    }
}
