<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Symfony\Component\DomCrawler\Crawler;

describe('slots', function (): void {
    it('renders its children and each named slot it is given', function (): void {
        $render = $this->renderSchemaComponent(Section::make('Order')
            ->components([Text::make('In the content')])
            ->afterHeader([Text::make('After the header')])
            ->footer(Text::make('In the footer')));

        expect($render)
            ->toContainElement(Selectors::SECTION_CONTENT . ' ' . Selectors::TEXT, 'In the content')
            ->toContainElement('header ' . Selectors::TEXT, 'After the header')
            ->toContainElement('footer ' . Selectors::TEXT, 'In the footer');
    });

    it('replaces what a slot held when it is given again, rather than adding to it', function (): void {
        $render = $this->renderSchemaComponent(Section::make('Order')
            ->components([Text::make('a')])
            ->footer([Text::make('First footer')])
            ->footer([Text::make('Second footer')]));

        expect($render)
            ->toContainElement(Selectors::TEXT, 'Second footer')
            ->and($render->filter(Selectors::TEXT)->each(static fn (Crawler $text): string => $text->text()))->not->toContain('First footer');
    });

    it('takes one child on its own as well as a list', function (Closure $child): void {
        expect($this->renderSchemaComponent(Section::make('Order')->components($child())))
            ->toContainElement(Selectors::SECTION_CONTENT . ' ' . Selectors::TEXT, 'Alone');
    })->with([
        'a component' => [static fn (): Text => Text::make('Alone')],
        'a string' => [static fn (): string => 'Alone'],
    ]);

    it('materialises a named slot afresh on every read, so a slot closure sees what changed since', function (): void {
        $label = 'First read';
        $section = Section::make('Order')
            ->components([Text::make('a')])
            ->footer(static function () use (&$label): array {
                return [Text::make($label)];
            });

        expect($this->renderSchemaComponent($section))->toContainElement(Selectors::TEXT, 'First read');

        $label = 'Second read';

        expect($this->renderSchemaComponent($section))->toContainElement(Selectors::TEXT, 'Second read');
    });

    it('renders a slot of actions in a schema bound to no live host', function (): void {
        expect($this->renderViewComponent(Schema::make()->components([Section::make('Order')->headerActions([Action::make('edit')])])))
            ->toContainButtons('header button', ['Edit']);
    });
});
