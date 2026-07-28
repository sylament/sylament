<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

/**
 * A schema-capable live host whose actions carry modal schemas, so the modal
 * slot order and the form-wrapper tag swap can be exercised end to end. Named
 * for the form wrapper rather than the schema so it does not read as a variant
 * of `ModalSchemaHost`, which exists for the reactive/nested pipeline instead.
 *
 * `schemas()` is deliberately empty: this host declares no page-level schema —
 * every schema it owns is built by `getMountedActionSchema()` and registered
 * through `cacheSchema()`, so the default `['schema']` list would only make
 * `getCachedSchemas()` demand a `schema()` method that has nothing to declare.
 * The schema components are plain `Text`, not form fields: `forms` is not a
 * dependency of `actions` and the fixture Kernel does not register it, and a
 * stateless component is enough to make `mountedActionHasSchema()` true.
 */
#[AsLiveComponent(name: 'FormModalHost', template: '@ActionsFixtures/components/FormModalHost.html.twig')]
class FormModalHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @return array<int, string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function reviewAction(): Action
    {
        return Action::make('review')
            ->modalContent(new Markup('<p class="test-review-body">Body</p>', 'UTF-8'))
            ->modalContentFooter(new Markup('<p class="test-review-outro">Outro</p>', 'UTF-8'))
            ->schema([Text::make('Reason required')])
            ->action(fn (): null => null);
    }

    public function unwrappedAction(): Action
    {
        return Action::make('unwrapped')
            ->modalContent(new Markup('<p class="test-review-body">Body</p>', 'UTF-8'))
            ->modalContentFooter(new Markup('<p class="test-review-outro">Outro</p>', 'UTF-8'))
            ->schema([Text::make('Reason required')])
            ->formWrapper(false)
            ->action(fn (): null => null);
    }
}
