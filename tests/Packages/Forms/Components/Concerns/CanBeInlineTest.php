<?php

declare(strict_types=1);

use Sylament\Forms\Components\Checkbox;
use Sylament\Tests\Fixtures\Forms\Selectors;

it('puts a checkbox inside its label by default, and in the content column below it once inline(false)', function (): void {
    $inline = $this->renderSchemaComponent(Checkbox::make('terms'));
    $stacked = $this->renderSchemaComponent(Checkbox::make('terms')->inline(false));

    expect($inline->filter('label[for="schema.terms"]')->text())->toBe('Terms')
        ->and($inline->filter(Selectors::LABEL_ROW . ' label[for="schema.terms"] input[name="terms"]'))->toHaveCount(1)
        ->and($stacked->filter('label[for="schema.terms"]')->text())->toBe('Terms')
        ->and($stacked->filter('label[for="schema.terms"] input[name="terms"]'))->toHaveCount(0)
        ->and($stacked->filter(Selectors::CONTENT_COLUMN . ' input[name="terms"]'))->toHaveCount(1);
});
