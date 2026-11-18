<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Twig\Error\RuntimeError;

it('renders the table of the host the template runs in', function (): void {
    $this->seed($lamp = new Product('Lamp'));

    expect($this->createLiveComponent(ProductTable::class)->render()->crawler())
        ->toContainElement(Selectors::cell((string) $lamp->id, 'name'), 'Lamp');
});

it('refuses to render a table from a template whose host is no table host', function (array $context): void {
    expect(fn (): string => $this::getContainer()->get('twig')->createTemplate('{{ renderTable() }}')->render($context))
        ->toThrow(RuntimeError::class, 'Component must be an instance of Sylament\Tables\Contracts\InteractsWithTable');
})->with([
    'no host' => [[]],
    'another object' => [['this' => new stdClass()]],
]);
