<?php

declare(strict_types=1);

use Sylament\Forms\Components\CheckboxList;
use Sylament\Tests\Fixtures\Forms\Selectors;

it('renders a string option label as text unless allowHtml() is on', function (bool $isHtmlAllowed): void {
    $label = $this->renderSchemaComponent(CheckboxList::make('tags')->options(['fern' => '<b>Fern</b>'])->allowHtml($isHtmlAllowed))
        ->filter(Selectors::CHECKBOX_LIST_OPTION_LABEL);

    expect($label->filter('b')->count())->toBe($isHtmlAllowed ? 1 : 0)
        ->and($label->text())->toBe($isHtmlAllowed ? 'Fern' : '<b>Fern</b>');
})->with([
    'without allowHtml()' => [false],
    'with allowHtml()' => [true],
]);

it("keeps an option's description escaped under allowHtml(), the label being the one raw site", function (): void {
    $option = $this->renderSchemaComponent(CheckboxList::make('tags')->options(['fern' => '<b>Fern</b>'])->descriptions(['fern' => '<b>Feathery</b>'])->allowHtml());

    expect($option->filter(Selectors::CHECKBOX_LIST_OPTION_LABEL . ' b')->text())->toBe('Fern')
        ->and($option->filter(Selectors::CHECKBOX_LIST_OPTION_DESCRIPTION)->text())->toBe('<b>Feathery</b>')
        ->and($option->filter(Selectors::CHECKBOX_LIST_OPTION_DESCRIPTION . ' b')->count())->toBe(0);
});
