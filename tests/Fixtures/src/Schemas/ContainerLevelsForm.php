<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A repeater whose item holds one field directly and one a level below the item,
 * beside a field two levels below the bag, on a bag that starts empty: a test keys the
 * items and fills the levels above the fields through the mount data.
 */
#[AsLiveComponent(name: 'Schemas:ContainerLevelsForm', template: '@Fixtures/schemas/ContainerLevelsForm.html.twig')]
class ContainerLevelsForm implements InteractsWithSchemas
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
                Repeater::make('members')->reorderable(false)->components([
                    TextInput::make('name'),
                    TextInput::make('home.city'),
                ]),
                TextInput::make('address.street.name'),
            ]);
    }
}
