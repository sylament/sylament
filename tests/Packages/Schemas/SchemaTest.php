<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\InjectionForm;
use Sylament\Tests\Fixtures\Schemas\ProfileForm;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

describe('closure injection', function (): void {
    it('hands a closure of its own the schema, its host and its record by name, and the record by its class or an interface it implements', function (): void {
        expect($this->createLiveComponent(InjectionForm::class)->render()->crawler())
            ->toContainElement(Selectors::TEXT, 'Container form')
            ->toContainElement(Selectors::TEXT, 'Hosted here')
            ->toContainElement(Selectors::TEXT, 'Record Minutes')
            ->toContainElement(Selectors::TEXT, 'Memo Minutes')
            ->toContainElement(Selectors::TEXT, 'Note Minutes')
            ->toContainElement(Selectors::TEXT, 'No date');
    });
});

describe('alignment factories', function (): void {
    it('aligns the children it is made with', function (string $factory): void {
        expect($this->renderViewComponent(Schema::{$factory}([Text::make('a')])))
            ->toContainElement(Selectors::SCHEMA . SupportSelectors::align($factory) . ' ' . Selectors::TEXT, 'a');
    })->with(['start', 'end', 'center', 'between']);
});

describe('gap and density', function (): void {
    it('spaces a root schema, and packs an inline one unless told otherwise', function (): void {
        expect($this->renderViewComponent(Schema::make()->components([Text::make('a')])))
            ->toContainElement(Selectors::SCHEMA . Selectors::GAP . ':not(' . Selectors::DENSE . ')')
            ->and($this->renderViewComponent(Schema::make()->inline()->components([Text::make('a')])))
            ->toContainElement(Selectors::SCHEMA . Selectors::DENSE)
            ->and($this->renderViewComponent(Schema::make()->inline()->dense(false)->components([Text::make('a')])))
            ->toContainElement(Selectors::SCHEMA . Selectors::INLINE . ':not(' . Selectors::DENSE . ')');
    });
});

describe('live binding', function (): void {
    it('binds the fields of a nested schema at the liveness of the component around it', function (string $modifier, Closure $section): void {
        expect($this->renderSchemaComponent($section()))
            ->toContainElement("input[data-model=\"{$modifier}data.city\"]");
    })->with([
        'live' => ['', fn (): Section => Section::make('Address')->live()->components([TextInput::make('city')])],
        'on blur' => ['on(change)|', fn (): Section => Section::make('Address')->live(onBlur: true)->components([TextInput::make('city')])],
        'debounced' => ['debounce(300)|', fn (): Section => Section::make('Address')->live(debounce: 300)->components([TextInput::make('city')])],
    ]);

    it('binds the fields of a nested schema at its own liveness over the one around it', function (): void {
        $section = Section::make('Address')
            ->live(onBlur: true)
            ->components(Schema::make()->live(debounce: 300)->components([TextInput::make('city')]));

        expect($this->renderSchemaComponent($section))->toContainElement('input[data-model="debounce(300)|data.city"]');
    });
});

describe('render', function (): void {
    it('lays its visible children out on its own grid, each in a cell spanning, starting and ordered as it says, under its extra attributes', function (): void {
        $render = $this->renderViewComponent(Schema::make()->columns(3)->extraAttributes(['data-purpose' => 'billing'])->components([
            Text::make('Wide')->columnSpan(2)->columnStart(2)->columnOrder(1),
            Text::make('Plain'),
            Text::make('Concealed')->hidden(),
        ]));

        expect($render)
            ->toContainElement(Selectors::SCHEMA . SupportSelectors::STANDALONE_ROOT . '[data-purpose="billing"]' . SupportSelectors::columns('lg', 3))
            ->toContainElement(SupportSelectors::columnSpan('lg', 2) . SupportSelectors::columnStart('lg', 2) . SupportSelectors::columnOrder('lg', 1) . ' ' . Selectors::COMPONENT_CELL . ' ' . Selectors::TEXT, 'Wide')
            ->toContainElement(Selectors::COMPONENT_CELL . ' ' . Selectors::TEXT, 'Plain')
            ->and($render->filter(Selectors::COMPONENT_CELL))->toHaveCount(2);
    });

    it('renders an action or an action group child in an action cell', function (): void {
        $render = $this->renderViewComponent(Schema::make()->components([
            Action::make('archive'),
            ActionGroup::make([Action::make('export')])->label('More'),
        ]));

        expect($render)
            ->toContainElement(Selectors::ACTION_CELL . ' button', 'Archive')
            ->toContainElement(Selectors::ACTION_CELL . ' button[aria-label="More"]')
            ->and($render->filter(Selectors::COMPONENT_CELL))->toHaveCount(0);
    });

    it('carries the schema controller, keyed and listening for a failed validation and a state reset, only as the root schema of a live host', function (): void {
        $render = $this->renderSchemaComponent(Section::make('Billing')->components([Text::make('a')]));

        expect($render)
            ->toContainElement(Selectors::ROOT_SCHEMA . Selectors::schemaKeyed('schema') . '[data-action~="form-validation-error@window->sylament--schemas--schema#onValidationError"][data-action~="reset-schema-component-state@window->sylament--schemas--schema#onStateReset"]')
            ->toContainElement(Selectors::SECTION_CONTENT . Selectors::UNCONTROLLED)
            ->and($this->renderViewComponent(Schema::make()->components([Text::make('a')])))
            ->toContainElement(Selectors::SCHEMA . Selectors::UNCONTROLLED);
    });

    it('renders nothing when no child is visible, so a section leaves out the content around it', function (): void {
        expect(Schema::make()->components([Text::make('a')->hidden()])->render($this::getContainer()->get('twig')))->toBeNull()
            ->and($this->renderSchemaComponent(Section::make('Empty')->components([Text::make('a')->hidden()])))
            ->toContainElement('h2', 'Empty')
            ->not->toContainElement(Selectors::sectionContentOf('schema.empty'));
    });
});

describe('client state reset', function (): void {
    it('tells the browser to reset the client state of its components, naming its key', function (): void {
        $root = $this->createLiveComponent(ProfileForm::class)
            ->call('resetClientState')
            ->render()->crawler()->filter('[data-live-events-to-dispatch-value]');

        expect(json_decode((string) $root->attr('data-live-events-to-dispatch-value'), true, flags: JSON_THROW_ON_ERROR))
            ->toBe([['event' => 'reset-schema-component-state', 'payload' => ['schemaKey' => 'form']]]);
    });
});
