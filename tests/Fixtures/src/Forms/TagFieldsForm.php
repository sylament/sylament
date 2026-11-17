<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\TagsInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Two tags fields: `tags`, required, and `joined`, stored as one comma-joined string.
 * `mount()` fills the form from the record it is given; `save()` validates it.
 */
#[AsLiveComponent(name: 'Forms:TagFieldsForm', template: '@Fixtures/forms/TagFieldsForm.html.twig')]
class TagFieldsForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * @param array<string, mixed> $record
     */
    public function mount(array $record = []): void
    {
        $this->getSchema('form')->fill($record);
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TagsInput::make('tags')->required(),
                TagsInput::make('joined')->separator(','),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}
