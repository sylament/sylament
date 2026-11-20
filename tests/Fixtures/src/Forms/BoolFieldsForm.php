<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\Toggle;
use Sylament\Forms\Components\ToggleButtons;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * One field of each boolean kind, so a save of the untouched form fails all but the last:
 * a required `terms` checkbox, a required and accepted `notify` toggle, a required
 * `channels` multiple toggle-buttons field over `email` and `sms`, a required `size`
 * grouped toggle-buttons field over `s` and `l`, a `confirmed` `boolean()`
 * toggle-buttons field defaulting to yes, and a multiple and a grouped one over the keys
 * `01` and `1`, which read as one number. `mount()` fills the defaults; `save()`
 * validates the form.
 */
#[AsLiveComponent(name: 'Forms:BoolFieldsForm', template: '@Fixtures/forms/BoolFieldsForm.html.twig')]
final class BoolFieldsForm implements InteractsWithSchemas
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
        return $schema
            ->statePath('data')
            ->components([
                Checkbox::make('terms')->required(),
                Toggle::make('notify')->required()->accepted(),
                ToggleButtons::make('channels')->multiple()->required()->options(['email' => 'Email', 'sms' => 'SMS']),
                ToggleButtons::make('size')->grouped()->required()->options(['s' => 'Small', 'l' => 'Large']),
                ToggleButtons::make('confirmed')->boolean()->default(true),
                ToggleButtons::make('codes')->multiple()->options(['01' => 'Zero one', 1 => 'One']),
                ToggleButtons::make('groupedCode')->grouped()->options(['01' => 'Zero one', 1 => 'One']),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}
