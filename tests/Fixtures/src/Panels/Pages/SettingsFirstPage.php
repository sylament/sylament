<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Pages\Page;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * A page no panel lists that declares `settings`, whose `theme` starts `dark`, before `form`,
 * whose `title` starts `Draft`.
 */
#[AsLiveComponent]
final class SettingsFirstPage extends Page
{
    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = ['title' => 'Draft'];

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $preferences = ['theme' => 'dark'];

    public function schemas(): array
    {
        return ['settings', 'form'];
    }

    public function settings(Schema $schema): Schema
    {
        return $schema
            ->statePath('preferences')
            ->components([
                TextInput::make('theme'),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('title'),
            ]);
    }
}
