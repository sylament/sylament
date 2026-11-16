<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Html;
use Sylament\Support\Icons\IconManager;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Twig\Markup;

it('wraps its content, carrying every caller attribute whole and appending its class', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:InputWrapper', ['class' => 'price', 'tabindex' => '0', 'data-field' => 'price'], content: '<input name="price">')->crawler())
        ->toContainElement('div.sy.sy-input-wrp.price[tabindex="0"][data-field="price"] .sy-input-wrp-content-ctn input[name="price"]');
});

it('is disabled and invalid only when told', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:InputWrapper', ['disabled' => true, 'valid' => false], content: '<input name="price">')->crawler())
        ->toContainElement('div.sy-input-wrp' . SupportSelectors::DISABLED . '.sy-invalid input[name="price"]')
        ->and($this->renderTwigComponent('SylamentSupport:InputWrapper', content: '<input name="price">')->crawler())
        ->toContainElement('div.sy-input-wrp:not(' . SupportSelectors::DISABLED . '):not(.sy-invalid) input[name="price"]');
});

it('shows a prefix and a suffix label around its content, and no affix for a blank one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:InputWrapper', ['prefix' => '€', 'suffix' => 'per kg'], content: '<input name="price">')->crawler())
        ->toContainElement(SupportSelectors::PREFIX . '.sy-input-wrp-prefix-has-label .sy-input-wrp-label', '€')
        ->toContainElement(SupportSelectors::SUFFIX . '.sy-input-wrp-suffix-has-label .sy-input-wrp-label', 'per kg')
        ->and($this->renderTwigComponent('SylamentSupport:InputWrapper', ['prefix' => '', 'suffix' => new Markup('', 'UTF-8')], content: '<input name="price">')->crawler())
        ->toContainElement('.sy-input-wrp input[name="price"]')
        ->not->toContainElement(SupportSelectors::PREFIX)
        ->not->toContainElement(SupportSelectors::SUFFIX);
});

it('draws an affix icon, the one an application registers under its alias even with no icon, and no affix for one that resolves to nothing', function (): void {
    $this::getContainer()->get(IconManager::class)->register(['tests.price-prefix' => 'support:heart']);

    expect($this->renderTwigComponent('SylamentSupport:InputWrapper', ['prefixIcon' => 'support:star', 'suffixIcon' => 'support:star'])->crawler())
        ->toContainElement(SupportSelectors::PREFIX_ICON, 'Star')
        ->toContainElement(SupportSelectors::SUFFIX_ICON, 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:InputWrapper', ['prefixIcon' => 'support:star', 'prefixIconAlias' => 'tests.price-prefix'])->crawler())
        ->toContainElement(SupportSelectors::PREFIX_ICON, 'Heart')
        ->and($this->renderTwigComponent('SylamentSupport:InputWrapper', ['prefixIconAlias' => 'tests.price-prefix', 'suffixIconAlias' => 'tests.unregistered'])->crawler())
        ->toContainElement(SupportSelectors::PREFIX_ICON, 'Heart')
        ->not->toContainElement(SupportSelectors::SUFFIX);
});

it('puts an affix inside the border only on the side told', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:InputWrapper', ['prefix' => '€', 'suffix' => 'per kg', 'inlinePrefix' => true])->crawler())
        ->toContainElement(SupportSelectors::INLINE_PREFIX, '€')
        ->toContainElement(SupportSelectors::SUFFIX . ':not(.sy-inline)', 'per kg')
        ->and($this->renderTwigComponent('SylamentSupport:InputWrapper', ['prefix' => '€', 'suffix' => 'per kg', 'inlineSuffix' => true])->crawler())
        ->toContainElement(SupportSelectors::PREFIX . ':not(.sy-inline)', '€')
        ->toContainElement(SupportSelectors::SUFFIX . '.sy-inline', 'per kg');
});

it('shows its visible affix actions and skips hidden ones, opening no affix for hidden actions alone', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:InputWrapper', [
        'prefixActions' => [Html::make('<button type="button">Copy</button>'), Html::make('<button type="button">Paste</button>')->hidden()],
        'suffixActions' => [Html::make('<button type="button">Clear</button>')->hidden()],
    ])->crawler())
        ->toContainButtons(SupportSelectors::PREFIX_ACTIONS, ['Copy'])
        ->not->toContainElement(SupportSelectors::SUFFIX)
        ->and($this->renderTwigComponent('SylamentSupport:InputWrapper', ['suffixActions' => [Html::make('<button type="button">Clear</button>')]])->crawler())
        ->toContainButtons(SupportSelectors::SUFFIX_ACTIONS, ['Clear']);
});
