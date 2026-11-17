<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\Textarea;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A required textarea, textareas cleaned by `stripCharacters()` and by `trim()` and
 * bounded so only the cleaned value fits, and one cleaned by neither.
 */
#[AsLiveComponent(name: 'Forms:TextareaForm', template: '@Fixtures/forms/TextareaForm.html.twig')]
final class TextareaForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Textarea::make('notes')->required(),
                Textarea::make('masked')->stripCharacters(['-'])->maxLength(3),
                Textarea::make('trimmed')->trim()->maxLength(3),
                Textarea::make('plain'),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}
