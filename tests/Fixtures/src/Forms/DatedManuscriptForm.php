<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\DateTimePicker;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\Record\ManuscriptObjectKinds;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Two date-time pickers framed on a record whose properties are date objects, so the cast
 * reads each into an object: `immutableAt` and `mutableAt`. `save()` validates the form.
 */
#[AsLiveComponent(name: 'Forms:DatedManuscriptForm', template: '@Fixtures/forms/DatedManuscriptForm.html.twig')]
final class DatedManuscriptForm implements InteractsWithSchemas
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
            ->record(ManuscriptObjectKinds::class)
            ->components([
                DateTimePicker::make('immutableAt'),
                DateTimePicker::make('mutableAt'),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}
