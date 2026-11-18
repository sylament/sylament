<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateNoIndexArticle;

/**
 * A parentless resource registering a create page and no index page.
 */
final class NoIndexResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $slug = 'no-index';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreateNoIndexArticle::route('/create'),
        ];
    }
}
