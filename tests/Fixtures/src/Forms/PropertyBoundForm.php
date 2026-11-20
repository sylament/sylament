<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form with no state path, so its one text input, `search`, is bound to the host's own
 * writable `search` property.
 */
#[AsLiveComponent(name: 'Forms:PropertyBoundForm', template: '@Fixtures/forms/PropertyBoundForm.html.twig')]
final class PropertyBoundForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    #[LiveProp(writable: true)]
    public ?string $search = null;

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('search')]);
    }
}
