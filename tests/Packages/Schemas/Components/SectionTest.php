<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('shows the heading it is made with in its header, and no header when made without one', function (): void {
    expect($this->renderSchemaComponent(Section::make('Shipping')->components([Text::make('Body')])))
        ->toContainElement('section header h2', 'Shipping')
        ->and($this->renderSchemaComponent(Section::make()->components([Text::make('Body')])))
        ->toContainElement('section', 'Body')
        ->not->toContainElement('header');
});

it('keys itself by the ASCII slug of its heading, and has no key, so no id, with a blank heading or none', function (): void {
    expect($this->renderSchemaComponent(Section::make('Doručovacia adresa')->components([Text::make('Body')])))
        ->toContainElement(Selectors::SECTION . '[id="schema.dorucovacia-adresa::section"]')
        ->and($this->renderSchemaComponent(Section::make('   ')->components([Text::make('Body')])))
        ->toContainElement(Selectors::SECTION . ':not([id])', 'Body')
        ->and($this->renderSchemaComponent(Section::make()->components([Text::make('Body')])))
        ->toContainElement(Selectors::SECTION . ':not([id])', 'Body');
});

describe('slots', function (): void {
    $slotted = static fn (): Section => Section::make('Shipping')
        ->label('Label')
        ->beforeLabel([Text::make('Before')])
        ->afterLabel([Text::make('After')])
        ->aboveContent([Text::make('Above')])
        ->belowContent([Text::make('Below')])
        ->afterHeader([Text::make('Header end')])
        ->footer([Text::make('Footer')])
        ->components([Text::make('Body')]);

    it('renders each slot in its own place: beside the label, above and below the box, at the end of the header and in the footer', function () use ($slotted): void {
        $render = $this->renderSchemaComponent($slotted());

        expect($render)
            ->toContainElement('.sy-sc-section-label-ctn', 'Before Label After')
            ->toContainElement('section header', 'Shipping Header end')
            ->toContainElement('section footer', 'Footer')
            ->and($render->filter(Selectors::SECTION)->text())->toBe('Before Label After Above Shipping Header end Body Footer Below');
    });

    it('shows what the slots after the header and in the footer are given in place of its header and footer actions', function (): void {
        $render = $this->renderSchemaComponent(Section::make('Shipping')
            ->headerActions([Action::make('edit')])
            ->footerActions([Action::make('save')])
            ->afterHeader([Text::make('Header end')])
            ->footer([Text::make('Footer')])
            ->components([Text::make('Body')]));

        expect($render)
            ->toContainElement('section header', 'Shipping Header end')
            ->toContainElement('section footer', 'Footer')
            ->and($render->filter('button'))->toHaveCount(0);
    });

    it('aligns what follows the header and the label to the end, and nothing else it slots', function () use ($slotted): void {
        $render = $this->renderSchemaComponent($slotted());

        expect($render)
            ->toContainElement('section header ' . Selectors::SCHEMA . SupportSelectors::align('end'), 'Header end')
            ->toContainElement('.sy-sc-section-label-ctn ' . Selectors::SCHEMA . SupportSelectors::align('end'), 'After')
            ->toContainElement(Selectors::SCHEMA . SupportSelectors::NO_ALIGN, 'Before')
            ->toContainElement(Selectors::SCHEMA . SupportSelectors::NO_ALIGN, 'Above')
            ->toContainElement(Selectors::SCHEMA . SupportSelectors::NO_ALIGN, 'Below')
            ->toContainElement('section footer ' . Selectors::SCHEMA . SupportSelectors::NO_ALIGN, 'Footer');
    });

    it('lays every slot out inline, while its content keeps its grid', function () use ($slotted): void {
        $render = $this->renderSchemaComponent($slotted());

        expect($render->filter(Selectors::SCHEMA . Selectors::INLINE)->each(static fn (Crawler $schema): string => $schema->text()))
            ->toBe(['Before', 'After', 'Above', 'Header end', 'Footer', 'Below'])
            ->and($render)->toContainElement(Selectors::SECTION_CONTENT . ':not(' . Selectors::INLINE . ')', 'Body');
    });

    it('shows the actions beside its label and above or below its box as small links and its groups there small, its header and footer actions as buttons, and a slot action asked for as a button as one', function (): void {
        $render = $this->renderSchemaComponent(Section::make('Shipping')
            ->label('Label')
            ->headerActions([Action::make('edit'), ActionGroup::make([Action::make('archive')])->label('Options')])
            ->footerActions([Action::make('save')])
            ->beforeLabel([Action::make('copy')])
            ->afterLabel([ActionGroup::make([Action::make('export')])->label('More')])
            ->aboveContent([Action::make('preview')])
            ->belowContent([Action::make('print')->button()])
            ->components([Text::make('Body')]));

        expect($render)
            ->toContainElement('.sy-sc-section-label-ctn ' . SupportSelectors::SMALL_LINK, 'Copy')
            ->toContainElement('.sy-sc-section-label-ctn ' . SupportSelectors::SMALL_ICON_BUTTON . '[aria-label="More"]')
            ->toContainElement(SupportSelectors::SMALL_LINK, 'Preview')
            ->toContainElement(SupportSelectors::BUTTON . SupportSelectors::size('sm'), 'Print')
            ->toContainElement('section header ' . SupportSelectors::BUTTON . SupportSelectors::MEDIUM, 'Edit')
            ->toContainElement('section header ' . SupportSelectors::ICON_BUTTON . SupportSelectors::MEDIUM . '[aria-label="Options"]')
            ->toContainElement('section footer ' . SupportSelectors::BUTTON . SupportSelectors::MEDIUM, 'Save');
    });
});

describe('rendering', function (): void {
    it('heads its box with its heading and description, wired to ids derived from its own, around its content', function (): void {
        $render = $this->renderSchemaComponent(Section::make('Shipping')->description('Where it goes')->id('ship')->components([Text::make('Body')]));

        expect($render)
            ->toContainElement(Selectors::SECTION . SupportSelectors::STANDALONE_ROOT . '#ship section[aria-labelledby="ship-heading"][aria-describedby="ship-description"] h2#ship-heading', 'Shipping')
            ->toContainElement('section header p#ship-description', 'Where it goes')
            ->toContainElement('section #ship-content ' . Selectors::SECTION_CONTENT, 'Body');
    });

    it('shows its label above its box, labelled by it only without a heading, and wires no id without one of its own', function (): void {
        expect($this->renderSchemaComponent(Section::make()->label('Carrier')->id('ship')->components([Text::make('Body')])))
            ->toContainElement('.sy-sc-section-label-ctn .sy-sc-section-label#ship-label', 'Carrier')
            ->toContainElement('section[aria-labelledby="ship-label"]', 'Body')
            ->and($this->renderSchemaComponent(Section::make('Shipping')->label('Carrier')->id('ship')->components([Text::make('Body')])))
            ->toContainElement('.sy-sc-section-label:not([id])', 'Carrier')
            ->toContainElement('section[aria-labelledby="ship-heading"] h2', 'Shipping')
            ->and($unwired = $this->renderSchemaComponent(Section::make()->label('Carrier')->components([Text::make('Body')])))
            ->toContainElement(Selectors::SECTION . ' .sy-sc-section-label', 'Carrier')
            ->toContainElement('section:not([aria-labelledby])', 'Body')
            ->and($unwired->filter(Selectors::SECTION . '[id], ' . Selectors::SECTION . ' [id]'))->toHaveCount(0);
    });

    it('keeps a hidden label in the markup for assistive technology, hidden only from view', function (): void {
        expect($this->renderSchemaComponent(Section::make()->label('Carrier')->hiddenLabel()->components([Text::make('Body')])))
            ->toContainElement('.sy-sc-section-label.sy-sc-section-label-hidden', 'Carrier')
            ->and($this->renderSchemaComponent(Section::make()->label('Carrier')->components([Text::make('Body')])))
            ->toContainElement('.sy-sc-section-label:not(.sy-sc-section-label-hidden)', 'Carrier');
    });

    it('carries its extra attributes on its root, its own id winning over an extra one', function (): void {
        expect($this->renderSchemaComponent(Section::make('Shipping')->id('ship')->extraAttributes(['id' => 'other', 'class' => 'custom', 'data-region' => 'eu'])->components([Text::make('Body')])))
            ->toContainElement(Selectors::SECTION . '.custom#ship[data-region="eu"] h2', 'Shipping')
            ->not->toContainElement('#other');
    });

    it('cannot be collapsed while aside', function (): void {
        expect($this->renderSchemaComponent(Section::make('Shipping')->aside()->collapsible()->components([Text::make('Body')])))
            ->toContainElement('section' . SupportSelectors::ASIDE . ':not(' . SupportSelectors::COLLAPSIBLE . ') h2', 'Shipping')
            ->not->toContainElement(SupportSelectors::SECTION_COLLAPSE_BUTTON);
    });
});
