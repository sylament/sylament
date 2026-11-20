<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\CheckboxList;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * One checkbox list per way a list reads, casts and guards its state: a static map with
 * a description and a disabled option, int keys, keys `01` and `1` that read as one number,
 * a required list, a required list that also wants two items, item bounds, a backed enum, an `in()` list and one with nothing
 * to enumerate. A save validates every field, so a case asserts the errors of the field
 * it fills.
 */
#[AsLiveComponent(name: 'Forms:CheckboxListForm', template: '@Fixtures/forms/CheckboxListForm.html.twig')]
class CheckboxListForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    public function mount(): void
    {
        $this->getSchema('form')->fill();
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        $plants = ['fern' => 'Fern', 'moss' => 'Moss'];

        return $schema
            ->statePath('data')
            ->components([
                CheckboxList::make('tags')
                    ->options([...$plants, 'ivy' => 'Ivy'])
                    ->descriptions(['fern' => 'Feathery fronds'])
                    ->disableOptionWhen(static fn (string $value): bool => $value === 'ivy'),
                CheckboxList::make('numbered')->options([1 => 'One', 2 => 'Two']),
                CheckboxList::make('codes')->options(['01' => 'Zero one', 1 => 'One']),
                CheckboxList::make('chosen')->options($plants)->required(),
                CheckboxList::make('chosenPair')->options($plants)->required()->minItems(2),
                CheckboxList::make('bounded')->options(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'])->minItems(2)->maxItems(3),
                CheckboxList::make('letters')->enum(StringBackedLetter::class),
                CheckboxList::make('restricted')->options($plants)->in(['fern', 'yew']),
                CheckboxList::make('unusable'),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}
