<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Html;
use Sylament\Tests\Fixtures\Schemas\ExtraDataHtml;

it('renders the explicit template over the default one, and the default once template(null) clears it', function (): void {
    $html = Html::make('<p>Greeting</p>')->defaultTemplate('@Fixtures/schemas/template-context.html.twig');

    expect($this->renderViewComponent($html))->toContainElement('p', 'Greeting')
        ->not->toContainElement('[data-template]')
        ->and($this->renderViewComponent($html->template(null)))->toContainElement('[data-template="context"] [data-variable="component"]', '<p>Greeting</p>');
});

it("hands the template the component, the extra data and each data layer, template()'s and templateData()'s, in order, the later key winning", function (): void {
    $html = ExtraDataHtml::make('Greeting')
        ->template('@Fixtures/schemas/template-context.html.twig', ['first' => 'template() layer'])
        ->templateData(['source' => 'first layer'])
        ->templateData(fn (): array => ['source' => 'second layer']);

    expect($this->renderViewComponent($html))
        ->toContainElement('[data-variable="component"]', 'Greeting')
        ->toContainElement('[data-variable="extra"]', 'extra data')
        ->toContainElement('[data-variable="first"]', 'template() layer')
        ->toContainElement('[data-variable="source"]', 'second layer')
        ->toContainElement('[data-variable="attributes"]', 'undefined');
});

it('renders nothing without a template', function (): void {
    expect(Html::make('<p>Greeting</p>')->template(null)->render($this::getContainer()->get('twig')))->toBeNull();
});
