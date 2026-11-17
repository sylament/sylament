<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\FusedGroup;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Forms\FusedGroupForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

describe('make()', function (): void {
    it('renders the components it is made with inside the group, after configure() has run', function (): void {
        FusedGroup::configureUsing(static fn (FusedGroup $group): FusedGroup => $group->label('Configured'));

        expect($this->renderSchemaComponent(FusedGroup::make([TextInput::make('first'), TextInput::make('second')])))
            ->toContainElement(Selectors::LABEL_HEADING, 'Configured')
            ->toContainElement(Selectors::FUSED_GROUP . ' input[name="first"]')
            ->toContainElement(Selectors::FUSED_GROUP . ' input[name="second"]');
    });
});

describe('render', function (): void {
    it('renders a group inside its field wrapper, labelled by the heading its key names, with no gap between its children', function (): void {
        $render = $this->renderSchemaComponent(FusedGroup::make([TextInput::make('first'), TextInput::make('second')])->key('contact')->label('Contact'));

        expect($render)
            ->toContainElement(Selectors::LABEL_HEADING . '[id="schema.contact-label"]:not(.sy-sr-only)', 'Contact')
            ->toContainElement(Selectors::FIELD_WRAPPER . ' ' . Selectors::FUSED_GROUP . '[role="group"][id="schema.contact"][aria-labelledby="schema.contact-label"]:not([aria-invalid])')
            ->toContainElement(Selectors::FUSED_GROUP . ' ' . SchemasSelectors::SCHEMA . ':not(' . SchemasSelectors::GAP . ') input[name="second"]')
            ->and($render->filter(SchemasSelectors::GAP))->toHaveCount(1)
            ->and($render->filter(Selectors::FUSED_GROUP . ' ' . SchemasSelectors::GAP))->toHaveCount(0);
    });

    it('points a group at no label without both a key and a label', function (): void {
        expect($this->renderSchemaComponent(FusedGroup::make([TextInput::make('first')])->label('Contact')))
            ->toContainElement(Selectors::LABEL_HEADING, 'Contact')
            ->toContainElement(Selectors::FUSED_GROUP . '[role="group"]:not([id]):not([aria-labelledby])')
            ->and($this->renderSchemaComponent(FusedGroup::make([TextInput::make('first')])->key('contact')))
            ->toContainElement(Selectors::FUSED_GROUP . '[role="group"][id="schema.contact"]:not([aria-labelledby])');
    });

    it('puts its extra attributes on its field wrapper, not on the group', function (): void {
        expect($this->renderSchemaComponent(FusedGroup::make([TextInput::make('first')])
            ->extraAttributes(['data-tone' => 'group'])
            ->extraFieldWrapperAttributes(['data-width' => 'full'])))
            ->toContainElement(Selectors::FIELD_WRAPPER . '[data-tone="group"][data-width="full"] ' . Selectors::FUSED_GROUP . ':not([data-tone]):not([data-width]) input[name="first"]');
    });
});

describe('slots', function (): void {
    it('renders its label slots around its label, the before- and after-label ones inline, the after-label one at the end, its actions there small', function (): void {
        $render = $this->renderSchemaComponent(FusedGroup::make([TextInput::make('first')])
            ->label('Contact')
            ->aboveLabel([Text::make('Above label'), Action::make('approve')])
            ->belowLabel('Below label')
            ->beforeLabel('Before label')
            ->afterLabel([Action::make('explain'), ActionGroup::make([Action::make('export')])->label('More')]));

        expect($render->filter(Selectors::LABEL_COLUMN)->text())->toBe('Above label Approve Before label Contact Explain ellipsis-vertical-20-solid Export Below label')
            ->and($render)
            ->toContainElement(SchemasSelectors::SCHEMA . ':not(' . SchemasSelectors::INLINE . ')', 'Above label Approve')
            ->toContainElement(SchemasSelectors::SCHEMA . ':not(' . SchemasSelectors::INLINE . ')', 'Below label')
            ->toContainElement(SchemasSelectors::SCHEMA . SchemasSelectors::INLINE . SupportSelectors::NO_ALIGN, 'Before label')
            ->toContainElement(SchemasSelectors::SCHEMA . SchemasSelectors::INLINE . SupportSelectors::align('end') . ' ' . SupportSelectors::SMALL_LINK, 'Explain')
            ->toContainElement(SchemasSelectors::SCHEMA . SchemasSelectors::INLINE . SupportSelectors::align('end') . ' ' . SupportSelectors::SMALL_ICON_BUTTON . '[aria-label="More"]')
            ->toContainButtons(SupportSelectors::SMALL_LINK, ['Explain'])
            ->toContainButtons(SupportSelectors::BUTTON . SupportSelectors::MEDIUM, ['Approve']);
    });

    it('renders its content slots around the group, the above- and below-content ones inline, and a field in one plain', function (): void {
        $render = $this->renderSchemaComponent(FusedGroup::make([TextInput::make('first')])
            ->aboveContent('Above content')
            ->belowContent('Below content')
            ->beforeContent('Before content')
            ->afterContent([TextInput::make('code')]));

        expect($render->filter(Selectors::CONTENT_COLUMN)->text())->toBe('Above content Before content First Code Below content')
            ->and($render)
            ->toContainElement(SchemasSelectors::SCHEMA . SchemasSelectors::INLINE, 'Above content')
            ->toContainElement(SchemasSelectors::SCHEMA . SchemasSelectors::INLINE, 'Below content')
            ->toContainElement(SchemasSelectors::SCHEMA . ':not(' . SchemasSelectors::INLINE . ')', 'Before content')
            ->toContainElement(SchemasSelectors::SCHEMA . ':not(' . SchemasSelectors::INLINE . ')' . SupportSelectors::NO_ALIGN, 'Code')
            ->toContainElement(Selectors::FIELD_WRAPPER . ' label.sy-sr-only[for="schema.code"]', 'Code')
            ->toContainElement(Selectors::FIELD_WRAPPER . ' input[name="code"]')
            ->and($render->filter(Selectors::LABEL_COLUMN))->toHaveCount(0);
    });

    it('renders its error-message slots inline around its error list, only while it has errors', function (): void {
        $tester = $this->createLiveComponent(FusedGroupForm::class);

        expect($tester->render()->crawler())
            ->toContainElement(Selectors::FUSED_GROUP . ' input[name="first"]')
            ->not->toContainElement(SchemasSelectors::TEXT)
            ->and($tester->fillForm(['first' => '123456', 'second' => ''])->call('save')->render()->crawler())
            ->toContainElement(SchemasSelectors::SCHEMA . SchemasSelectors::INLINE, 'Above the errors')
            ->toContainElement(SchemasSelectors::SCHEMA . SchemasSelectors::INLINE, 'Below the errors')
            ->and($tester->render()->crawler()->filter(Selectors::CONTENT_COLUMN)->text())->toBe('First Second Above the errors This value should not be blank. Below the errors');
    });
});

describe('child schemas', function (): void {
    it('renders the fields of a schema it is handed already built with their full wrapper, as it renders fields it is handed as components plain', function (): void {
        expect($this->renderSchemaComponent(FusedGroup::make()->components(Schema::make()->components([TextInput::make('first')]))))
            ->toContainElement(Selectors::FUSED_GROUP . ' ' . Selectors::LABEL_COLUMN . ' label[for="schema.first"]:not(.sy-sr-only)', 'First')
            ->and($this->renderSchemaComponent(FusedGroup::make([TextInput::make('first')])))
            ->toContainElement(Selectors::FUSED_GROUP . ' label.sy-sr-only[for="schema.first"]', 'First')
            ->not->toContainElement(Selectors::FUSED_GROUP . ' ' . Selectors::LABEL_COLUMN);
    });
});

describe('plain wrapper', function (): void {
    it('renders a field plain in any schema carrying the plain wrapper view, and in full under any other view', function (): void {
        $grouped = static fn (string $view): Group => Group::make(Schema::make()->fieldWrapperView($view)->components([TextInput::make('first')->required()]));

        expect($this->renderSchemaComponent($grouped(FusedGroup::PLAIN_FIELD_WRAPPER_VIEW)))
            ->toContainElement('label.sy-sr-only[for="schema.first"]', 'First')
            ->toContainElement('input[name="first"]')
            ->not->toContainElement(Selectors::LABEL_COLUMN)
            ->and($this->renderSchemaComponent($grouped('my-app::field-wrapper')))
            ->toContainElement(Selectors::LABEL_COLUMN . ' label[for="schema.first"]:not(.sy-sr-only) ' . Selectors::REQUIRED_MARK, '*');
    });

    it('renders an inline checkbox it holds as its label alone, and a checkbox told not to be inline as its control', function (): void {
        $render = $this->renderSchemaComponent(FusedGroup::make([Checkbox::make('agree')]));

        expect($render)
            ->toContainElement(Selectors::FUSED_GROUP . ' label.sy-sr-only[for="schema.agree"]', 'Agree')
            ->and($render->filter('input'))->toHaveCount(0)
            ->and($this->renderSchemaComponent(FusedGroup::make([Checkbox::make('agree')->inline(false)])))
            ->toContainElement(Selectors::FUSED_GROUP . ' input[type="checkbox"][name="agree"]');
    });
});

describe('required', function (): void {
    it('marks its label required when a field it holds is required or only marked so, and leaves it unmarked when none is', function (): void {
        $marked = fn (FusedGroup $group): Crawler => $this->renderSchemaComponent($group->label('Contact'));

        expect($marked(FusedGroup::make([TextInput::make('first'), TextInput::make('second')->required()])))
            ->toContainElement(Selectors::LABEL_ROW . ' ' . Selectors::REQUIRED_MARK, '*')
            ->and($marked(FusedGroup::make([TextInput::make('first')->markAsRequired()])))
            ->toContainElement(Selectors::LABEL_ROW . ' ' . Selectors::REQUIRED_MARK, '*')
            ->and($marked(FusedGroup::make([Text::make('Fill both.'), TextInput::make('first')])))
            ->toContainElement(Selectors::LABEL_ROW, 'Contact')
            ->not->toContainElement(Selectors::REQUIRED_MARK);
    });
});

describe('errors', function (): void {
    it('lists every message of the first field that failed and none of the next, marking the group invalid with nothing describing it', function (): void {
        $tester = $this->createLiveComponent(FusedGroupForm::class);

        expect($tester->render()->crawler())
            ->toContainElement(Selectors::FUSED_GROUP . '[role="group"]:not([aria-invalid])')
            ->not->toContainElement(Selectors::ERROR_MESSAGE);

        $tester->fillForm(['first' => 'abc', 'second' => ''])->call('save')->assertHasFormErrors(['first', 'second']);
        $render = $tester->render()->crawler();

        expect($render->filter(Selectors::ERROR_MESSAGE)->each(static fn (Crawler $message): string => $message->text()))
            ->toBe(['This value is too short. It should have 5 characters or more.', 'This value is not valid.'])
            ->and($render)->toContainElement(Selectors::FUSED_GROUP . '[role="group"][aria-invalid="true"]:not([aria-describedby])')
            ->and($tester->fillForm(['first' => '123456'])->call('save')->render()->crawler()->filter(Selectors::ERROR_MESSAGE)->each(static fn (Crawler $message): string => $message->text()))
            ->toBe(['This value should not be blank.']);
    });
});
