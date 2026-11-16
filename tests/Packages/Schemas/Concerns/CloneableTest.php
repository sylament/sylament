<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\Selectors;

it('builds its children afresh for the copy, keyed where the copy is, and leaves the original as it was', function (): void {
    $schema = Schema::make()->key('form')->components(static fn (Schema $container): array => [
        Fieldset::make("In {$container->getKey()}")->key('street')->components([Text::make('a')]),
    ]);

    expect($this->renderViewComponent($schema))->toContainElement('fieldset[id="form.street"] legend', 'In form');

    $clone = $schema->getClone()->key('copy');

    expect($this->renderViewComponent($clone))->toContainElement('fieldset[id="copy.street"] legend', 'In copy')
        ->and($this->renderViewComponent($schema))->toContainElement('fieldset[id="form.street"] legend', 'In form');
});

it('runs its after-clone hooks on the copy, bound to it and given the copy and the original', function (): void {
    $schema = Schema::make()
        ->key('form')
        ->components([Fieldset::make('Street')->key('street')->components([Text::make('a')])])
        ->afterCloned(function (): void {
            $this->key('copy');
        })
        ->afterCloned(function (Schema $clone, Schema $original): void {
            $clone->extraAttributes(['data-original' => $original->getKey()]);
        });

    expect($this->renderViewComponent($schema->getClone()))
        ->toContainElement(Selectors::SCHEMA . '[data-original="form"] fieldset[id="copy.street"]')
        ->and($this->renderViewComponent($schema))
        ->toContainElement(Selectors::SCHEMA . ':not([data-original]) fieldset[id="form.street"]');
});
