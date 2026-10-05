<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Forms\Components\Hidden;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * An article create whose mutateFormDataBeforeCreate() does what the `arm`
 * prop names: adds a status and a nested key no component maps (`adding`);
 * upper-cases the mapped title (`overwriting`, and `injected`, where the case
 * types a status no component maps); or adds the status a Hidden field maps
 * (`hidden`) or a field that is not dehydrated maps (`pruned`).
 */
#[AsLiveComponent]
final class CreateHookedDataArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    #[LiveProp]
    public string $arm = 'adding';

    public function form(Schema $schema): Schema
    {
        return parent::form($schema)->components([
            TextInput::make('title')->required(),
            TextInput::make('slug')->required(),
            ...match ($this->arm) {
                'hidden' => [Hidden::make('status')->required(false)],
                'pruned' => [TextInput::make('status')->dehydrated(false)->required(false)],
                default => [],
            },
        ]);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $title = $data['title'] ?? null;

        return match ($this->arm) {
            'adding' => [...$data, 'status' => 'published', 'meta' => ['source' => 'hook']],
            'hidden', 'pruned' => [...$data, 'status' => 'published'],
            default => [...$data, 'title' => is_string($title) ? mb_strtoupper($title) : $title],
        };
    }
}
