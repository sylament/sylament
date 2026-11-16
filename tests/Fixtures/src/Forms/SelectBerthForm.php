<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Berth;
use Sylament\Tests\Fixtures\Entity\BerthCleat;
use Sylament\Tests\Fixtures\Entity\BerthMooring;
use Sylament\Tests\Fixtures\Entity\Quay;
use Sylament\Tests\Fixtures\Entity\QuayBollard;
use Sylament\Tests\Fixtures\Entity\QuayMooring;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A new {@see Berth}'s form, or a new {@see Quay}'s, whose selects pick and create records
 * keyed by what the application assigns: a {@see BerthMooring} with both option forms over
 * its two key strings, searched by its pier or by its label, and created by a form missing
 * a key half, or by one switching the key rules off; a {@see BerthCleat} whose berth half
 * no form maps; a {@see QuayBollard} whose key its column lower-cases; and a
 * {@see QuayMooring} whose prePersist callback fills neither key half. The `record` prop
 * picks the owner.
 */
#[AsLiveComponent(name: 'Forms:SelectBerthForm', template: '@Fixtures/forms/SelectBerthForm.html.twig')]
class SelectBerthForm implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * @var class-string<Berth|Quay>
     */
    #[LiveProp]
    public string $record = Berth::class;

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        $schema->statePath('data')->record($this->record);

        if ($this->record === Quay::class) {
            return $schema->components([
                Select::make('bollard')->relationship('bollard', 'label')->createOptionForm([TextInput::make('code'), TextInput::make('label')]),
                Select::make('mooring')->relationship('mooring', 'label')->createOptionForm([TextInput::make('pier'), TextInput::make('label')]),
            ]);
        }

        return $schema->components([
            Select::make('mooring')
                ->relationship('mooring', 'label')
                ->manageOptionForm([TextInput::make('pier'), TextInput::make('slot'), TextInput::make('label')]),
            Select::make('byPier')->relationship('mooring', 'label')->searchable(['pier']),
            Select::make('byLabel')->relationship('mooring', 'label')->searchable(['pier'])->searchable(),
            Select::make('partialMooring')
                ->relationship('mooring', 'label')
                ->createOptionForm([TextInput::make('pier'), TextInput::make('label')]),
            Select::make('unguardedMooring')
                ->relationship('mooring', 'label')
                ->createOptionForm(static fn (Schema $schema): Schema => $schema->validateRecordKey(false)->components([
                    TextInput::make('pier'),
                    TextInput::make('slot'),
                    TextInput::make('label'),
                ])),
            Select::make('cleat')
                ->relationship('cleat', 'label')
                ->createOptionForm([TextInput::make('slot'), TextInput::make('label')]),
        ]);
    }
}
