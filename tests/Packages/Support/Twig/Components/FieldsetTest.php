<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('is a fieldset around its content, carrying the caller attributes and appending its class', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Fieldset', ['class' => 'billing', 'data-step' => '2', 'label' => 'Address'], content: '<input name="street">')->crawler())
        ->toContainElement('fieldset' . SupportSelectors::STANDALONE_ROOT . SupportSelectors::FIELDSET . '.billing[data-step="2"] legend', 'Address')
        ->toContainElement('fieldset' . SupportSelectors::FIELDSET . ' input[name="street"]');
});

it('is labelled by a legend, hidden visually when told, and has none for a blank label', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Fieldset', ['label' => 'Address'])->crawler())
        ->toContainElement('fieldset' . SupportSelectors::FIELDSET . ':not(' . SupportSelectors::FIELDSET_LABEL_HIDDEN . ') legend', 'Address')
        ->and($this->renderTwigComponent('SylamentSupport:Fieldset', ['label' => 'Address', 'labelHidden' => true])->crawler())
        ->toContainElement('fieldset' . SupportSelectors::FIELDSET . SupportSelectors::FIELDSET_LABEL_HIDDEN . ' legend', 'Address')
        ->and($this->renderTwigComponent('SylamentSupport:Fieldset', content: '<input name="street">')->crawler())
        ->toContainElement('fieldset' . SupportSelectors::FIELDSET . ' input[name="street"]')
        ->not->toContainElement('legend')
        ->and($this->renderTwigComponent('SylamentSupport:Fieldset', ['label' => '   '], content: '<input name="street">')->crawler())
        ->toContainElement('fieldset' . SupportSelectors::FIELDSET . ' input[name="street"]')
        ->not->toContainElement('legend');
});

it('marks a required label with an asterisk, and marks nothing without a label', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Fieldset', ['label' => 'Address', 'required' => true])->crawler())
        ->toContainElement('legend', 'Address*')
        ->toContainElement('legend sup' . SupportSelectors::FIELDSET_REQUIRED_MARK, '*')
        ->and($this->renderTwigComponent('SylamentSupport:Fieldset', ['required' => true], content: '<input name="street">')->crawler())
        ->toContainElement('fieldset' . SupportSelectors::FIELDSET . ' input[name="street"]')
        ->not->toContainElement('sup');
});

it('is uncontained only when told', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Fieldset', ['label' => 'Address', 'contained' => false])->crawler())
        ->toContainElement('fieldset' . SupportSelectors::FIELDSET . '.sy-fieldset-not-contained legend', 'Address')
        ->and($this->renderTwigComponent('SylamentSupport:Fieldset', ['label' => 'Address'])->crawler())
        ->toContainElement('fieldset' . SupportSelectors::FIELDSET . ':not(.sy-fieldset-not-contained) legend', 'Address');
});
