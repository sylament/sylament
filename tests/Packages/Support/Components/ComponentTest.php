<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Html;
use Sylament\Tests\Fixtures\Schemas\EnvironmentHtml;

it('makes a new instance on every make()', function (): void {
    $first = Html::make('<p>First</p>');
    $second = Html::make('<p>Second</p>');

    expect($first)->not->toBe($second)
        ->and($this->renderSchemaComponent($first))->toContainElement('p', 'First')
        ->and($this->renderSchemaComponent($second))->toContainElement('p', 'Second');
});

it('autowires the constructor of a subclass registered as a service', function (): void {
    expect($this->renderSchemaComponent(EnvironmentHtml::make(null)))->toContainElement('p', 'Built in the test environment');
});

it('builds a subclass the container does not know with new', function (): void {
    $unregistered = new class extends Html {};

    expect($this->renderSchemaComponent($unregistered::make('<p>Greeting</p>')))->toContainElement('p', 'Greeting');
});

it("sets make()'s argument before configure() runs, so a configureUsing() closure reads it", function (): void {
    Html::configureUsing(fn (Html $html): Html => $html->content("<p>{$html->getContent()}, configured</p>"));

    expect($this->renderSchemaComponent(Html::make('Made')))->toContainElement('p', 'Made, configured');
});
