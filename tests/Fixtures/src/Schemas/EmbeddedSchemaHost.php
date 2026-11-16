<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Actions;
use Sylament\Schemas\Components\EmbeddedSchema;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host whose template renders its `page` schema and then its `form` schema: the page
 * embeds `details`, which the template renders nowhere else, and `form`, which it
 * renders again, between two lines of text, beside a row of actions whose `archive`
 * records its run in the request's {@see HookLog}; `archive`'s schema is declared and
 * never embedded.
 */
#[AsLiveComponent(name: 'Schemas:EmbeddedSchemaHost', template: '@Fixtures/schemas/EmbeddedSchemaHost.html.twig')]
class EmbeddedSchemaHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public function schemas(): array
    {
        return ['page', 'form', 'details', 'archive'];
    }

    public function page(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make('Before'),
                EmbeddedSchema::make('details'),
                EmbeddedSchema::make('form'),
                Text::make('After'),
                Actions::make([
                    Action::make('archive')->action(function (): void {
                        $this->hookLog->record('archive');
                    }),
                ]),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([TextInput::make('title')]);
    }

    public function details(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([TextInput::make('notes')]);
    }

    public function archive(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([TextInput::make('reason')]);
    }
}
