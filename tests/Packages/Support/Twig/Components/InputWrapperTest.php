<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Html;
use Sylament\Support\Icons\IconManager;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Twig\Markup;

it('wraps its content, carrying every caller attribute whole and appending its class', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:InputWrapper', ['class' => 'price', 'tabindex' => '0', 'data-field' => 'price'], content: '<input name="price">')->crawler())
        ->toContainElement('div' . SupportSelectors::STANDALONE_ROOT . SupportSelectors::INPUT_WRAPPER . '.price[tabindex="0"][data-field="price"] .sy-input-wrp-content-ctn input[name="price"]');
});

it('is disabled and invalid only when told', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:InputWrapper', ['disabled' => true, 'valid' => false], content: '<input name="price">')->crawler())
        ->toContainElement('div' . SupportSelectors::INPUT_WRAPPER . SupportSelectors::DISABLED . SupportSelectors::INVALID . ' input[name="price"]')
        ->and($this->renderTwigComponent('SylamentSupport:InputWrapper', content: '<input name="price">')->crawler())
        ->toContainElement('div' . SupportSelectors::INPUT_WRAPPER . ':not(' . SupportSelectors::DISABLED . '):not(' . SupportSelectors::INVALID . ') input[name="price"]');
});

it('shows a prefix and a suffix label around its content, and no affix for a blank one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:InputWrapper', ['prefix' => '€', 'suffix' => 'per kg'], content: '<input name="price">')->crawler())
        ->toContainElement(SupportSelectors::PREFIX . '.sy-input-wrp-prefix-has-label ' . SupportSelectors::INPUT_WRAPPER_LABEL, '€')
        ->toContainElement(SupportSelectors::SUFFIX . '.sy-input-wrp-suffix-has-label ' . SupportSelectors::INPUT_WRAPPER_LABEL, 'per kg')
        ->and($this->renderTwigComponent('SylamentSupport:InputWrapper', ['prefix' => '', 'suffix' => new Markup('', 'UTF-8')], content: '<input name="price">')->crawler())
        ->toContainElement(SupportSelectors::INPUT_WRAPPER . ' input[name="price"]')
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

it('reads action, icon and label before its content, and label, icon and action after it', function (): void {
    $render = $this->renderTwigComponent('SylamentSupport:InputWrapper', [
        'prefixActions' => [Html::make('<button type="button">Copy</button>')],
        'prefixIcon' => 'support:star',
        'prefix' => '€',
        'suffix' => 'per kg',
        'suffixIcon' => 'support:heart',
        'suffixActions' => [Html::make('<button type="button">Clear</button>')],
    ], content: '<input name="price">')->crawler();

    expect($render->filter(SupportSelectors::PREFIX)->text())->toBe('Copy Star €')
        ->and($render->filter(SupportSelectors::SUFFIX)->text())->toBe('per kg Heart Clear');
});

it('puts an affix inside the border only on the side told', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:InputWrapper', ['prefix' => '€', 'suffix' => 'per kg', 'inlinePrefix' => true])->crawler())
        ->toContainElement(SupportSelectors::INLINE_PREFIX, '€')
        ->toContainElement(SupportSelectors::SUFFIX . ':not(.sy-inline)', 'per kg')
        ->and($this->renderTwigComponent('SylamentSupport:InputWrapper', ['prefix' => '€', 'suffix' => 'per kg', 'inlineSuffix' => true])->crawler())
        ->toContainElement(SupportSelectors::PREFIX . ':not(.sy-inline)', '€')
        ->toContainElement(SupportSelectors::INLINE_SUFFIX, 'per kg');
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
