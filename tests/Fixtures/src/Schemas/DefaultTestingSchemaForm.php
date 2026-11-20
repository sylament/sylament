<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Declares `form`, then `settings`, whose `theme` starts `dark`, and names `settings` as the
 * schema its testing helpers test by default.
 */
#[AsLiveComponent(name: 'Schemas:DefaultTestingSchemaForm', template: '@Fixtures/schemas/DefaultTestingSchemaForm.html.twig')]
class DefaultTestingSchemaForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $preferences = ['theme' => 'dark'];

    public function schemas(): array
    {
        return ['form', 'settings'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('code'),
            ]);
    }

    public function settings(Schema $schema): Schema
    {
        return $schema
            ->statePath('preferences')
            ->components([
                TextInput::make('theme'),
            ]);
    }

    public function getDefaultTestingSchemaName(): ?string
    {
        return 'settings';
    }
}
