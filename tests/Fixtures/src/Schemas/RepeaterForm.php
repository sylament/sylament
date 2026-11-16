<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A repeater holding two items, whose field labels itself by the position of the item
 * it repeats in, beside a field outside any repeater labelling itself the same way.
 */
#[AsLiveComponent(name: 'Schemas:RepeaterForm', template: '@Fixtures/schemas/RepeaterForm.html.twig')]
class RepeaterForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [
        'members' => [
            'ada' => ['name' => 'Ada'],
            'grace' => ['name' => 'Grace'],
        ],
    ];

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Repeater::make('members')->reorderable(false)->components([
                    Section::make()->components([
                        TextInput::make('name')->label($this->labelByItem(...)),
                    ]),
                ]),
                TextInput::make('team')->label($this->labelByItem(...)),
            ]);
    }

    private function labelByItem(TextInput $component): string
    {
        if ($component->getParentRepeater() === null) {
            return 'Not repeated';
        }

        return 'Member ' . ($component->getParentRepeaterItemIndex() + 1);
    }
}
