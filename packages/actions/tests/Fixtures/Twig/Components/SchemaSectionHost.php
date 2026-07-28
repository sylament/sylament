<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host whose only action lives inside its schema — a Section header
 * action — so the whole mount round-trip must travel through
 * `context['schemaComponent']`: the rendered trigger emits the context, and
 * `resolveSchemaComponentAction()` resolves it back via `Schema::getAction()`.
 */
#[AsLiveComponent(name: 'SchemaSectionHost', template: '@ActionsFixtures/components/SchemaSectionHost.html.twig')]
class SchemaSectionHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * Exposed as a LiveProp so the closure's side effect survives the round-trip
     * and shows up as the template's `data-published` marker.
     */
    #[LiveProp]
    public bool $published = false;

    public function schema(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Publishing')->headerActions([
                Action::make('publish')
                    ->requiresConfirmation()
                    ->action(fn (): true => $this->published = true),
            ]),
        ]);
    }
}
