<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Get;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form whose labels show what `$get` reads: each flag is stored as an int and read
 * through its field as a bool, so a label tells a field's state from the raw bag.
 */
#[AsLiveComponent(name: 'Schemas:ReaderForm', template: '@Fixtures/schemas/ReaderForm.html.twig')]
class ReaderForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [
        'subscribed' => 1,
        'extra' => 'Kept',
        'lookup' => ['flag' => 1],
        'search' => ['flag' => 1],
    ];

    #[LiveProp]
    public string $heading = 'Outside';

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('subscribed')
                    ->stateCast(new BooleanStateCast(isStoredAsInt: true))
                    ->label(static fn (Get $get): string => 'Own ' . var_export($get('subscribed'), true)),
                Group::make([
                    TextInput::make('city')
                        ->label(static fn (Get $get): string => 'Up ' . var_export($get('../subscribed'), true) . ', absolute ' . var_export($get('data.subscribed', isAbsolute: true), true) . ', from the root ' . var_export($get('/data.subscribed'), true)),
                ])->statePath('address'),
                TextInput::make('note')
                    ->label(static fn (Get $get): string => 'Bag ' . var_export($get('extra'), true)),
                TextInput::make('caption')
                    ->label(static fn (Get $get): string => 'Host ' . var_export($get('../heading'), true)),
                Fieldset::make()
                    ->statePath('lookup')
                    ->label(static fn (Get $get): string => 'Skipped ' . var_export($get('lookup.flag'), true))
                    ->components([TextInput::make('flag')->stateCast(new BooleanStateCast(isStoredAsInt: true))]),
                Fieldset::make()
                    ->statePath('search')
                    ->label(static fn (Get $get): string => 'Searched ' . var_export($get->skipComponentsChildSchemasWhileSearching(condition: false)('search.flag'), true))
                    ->components([TextInput::make('flag')->stateCast(new BooleanStateCast(isStoredAsInt: true))]),
            ]);
    }
}
