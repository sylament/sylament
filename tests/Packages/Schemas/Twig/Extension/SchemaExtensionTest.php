<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Sylament\Tests\Fixtures\Schemas\NoteForm;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Twig\Error\RuntimeError;

it('renders the schema a template names', function (): void {
    expect($this->createLiveComponent(CodeForm::class)->render()->crawler())
        ->toContainElement('input' . Selectors::boundTo('data.code'));
});

it("renders the host's schema named schema when the template names none, as markup a variable passes on unescaped", function (): void {
    expect($this->createLiveComponent(NoteForm::class)->render()->crawler())
        ->toContainElement('input' . Selectors::boundTo('data.note'));
});

it('refuses to render a schema from a template whose host holds no schemas', function (array $context): void {
    expect(fn (): string => $this::getContainer()->get('twig')->createTemplate('{{ renderSchema() }}')->render($context))
        ->toThrow(RuntimeError::class, 'Component must be an instance of Sylament\Schemas\Contracts\InteractsWithSchemas');
})->with([
    'no host' => [[]],
    'another object' => [['this' => new stdClass()]],
]);
