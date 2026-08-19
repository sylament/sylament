<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Twig\Components;

use RuntimeException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\ConstrainedField;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host whose schema nests a constrained field inside a `Section` and keeps a
 * second one at the root, so the validation identity of a component's child schema
 * (`schema.basics::section`) can be told apart from the root's (`schema`).
 *
 * The validating actions swallow the `ValidationException` on purpose: the browser
 * event is dispatched before the throw, and only a completed request renders the
 * response the test reads it from.
 */
#[AsLiveComponent(name: 'ValidationNestedHost', template: '@SchemasFixtures/components/ValidationNestedHost.html.twig')]
class ValidationNestedHost implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /** @var array<string, mixed> */
    #[LiveProp(writable: true)]
    public array $data = [];

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Basics')->components([
                    ConstrainedField::make('title')->required(),
                ]),
                ConstrainedField::make('other')->required(),
            ]);
    }

    public function getBasicsSchema(): Schema
    {
        $section = $this->getSchemaComponent('schema.basics::section');

        if (!$section instanceof Component) {
            throw new RuntimeException('The Basics section is missing from the fixture schema.');
        }

        $childSchema = $section->getChildSchema();

        if (!$childSchema instanceof Schema) {
            throw new RuntimeException('The Basics section carries no child schema.');
        }

        return $childSchema;
    }

    #[LiveAction]
    public function validateBasics(): void
    {
        try {
            $this->getBasicsSchema()->validate();
        } catch (ValidationException) {
        }
    }

    #[LiveAction]
    public function validateRoot(): void
    {
        try {
            $this->validateSchema($this->getSchema());
        } catch (ValidationException) {
        }
    }

    #[LiveAction]
    public function validateRootSilently(): void
    {
        $this->validateSchema($this->getSchema(), shouldThrow: false);
    }

    #[LiveAction]
    public function noop(): void
    {
    }
}
