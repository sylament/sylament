<?php

declare(strict_types=1);

use Sylament\Forms\Components\Radio;
use Sylament\Forms\Components\Repeater;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
use Symfony\Component\DomCrawler\Crawler;

describe('the installs', function (): void {
    it('refuses a value two items share at validation, and makes the field live', function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(Repeater::make('slots')->components([Radio::make('choice')->options(['a' => 'A', 'b' => 'B'])->disableOptionsWhenSelectedInSiblingRepeaterItems()]));
        $host->data = ['slots' => ['x' => ['choice' => 'a'], 'y' => ['choice' => 'a']]];

        expect(fn () => $host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect(array_keys($exception->getErrors()))->toContain('data.slots.y.choice');
        })
            ->and($this->renderSchemaComponent(
                Repeater::make('slots')->components([Radio::make('choice')->options(['a' => 'A', 'b' => 'B'])->disableOptionsWhenSelectedInSiblingRepeaterItems()]),
                ['slots' => ['x' => ['choice' => 'a']]],
            )->filter('input[data-model="data.slots.x.choice"]'))->toHaveCount(2);
    });
});

describe('the predicate', function (): void {
    it('disables an option only another item holds and keeps one nobody holds enabled', function (): void {
        $crawler = $this->renderSchemaComponent(
            Repeater::make('slots')->components([Radio::make('choice')->options(['a' => 'A', 'b' => 'B'])->disableOptionsWhenSelectedInSiblingRepeaterItems()]),
            ['slots' => ['x' => ['choice' => 'a'], 'y' => ['choice' => null]]],
        );

        expect($crawler->filter(FormsSelectors::repeaterItem('y') . ' input[value="a"][disabled]'))->toHaveCount(1)
            ->and($crawler->filter(FormsSelectors::repeaterItem('y') . ' input[value="b"]:not([disabled])'))->toHaveCount(1)
            ->and($crawler->filter(FormsSelectors::repeaterItem('x') . ' input:not([disabled])'))->toHaveCount(2);
    });

    it('keeps a value both items hold clickable', function (): void {
        $crawler = $this->renderSchemaComponent(
            Repeater::make('slots')->components([Radio::make('choice')->options(['a' => 'A', 'b' => 'B'])->disableOptionsWhenSelectedInSiblingRepeaterItems()]),
            ['slots' => ['x' => ['choice' => 'a'], 'y' => ['choice' => 'a'], 'z' => ['choice' => 'b']]],
        );
        $disabled = static fn (string $item): array => $crawler->filter(FormsSelectors::repeaterItem($item) . ' input[disabled]')->each(static fn (Crawler $input): string => $input->attr('value') ?? '');

        expect($disabled('x'))->toBe(['b'])
            ->and($disabled('y'))->toBe(['b'])
            ->and($disabled('z'))->toBe(['a']);
    });

    it('disables nothing for a field outside any repeater', function (): void {
        $crawler = $this->renderSchemaComponent(Radio::make('choice')->options(['a' => 'A', 'b' => 'B'])->disableOptionsWhenSelectedInSiblingRepeaterItems(), ['choice' => 'a']);

        expect($crawler->filter('input[type="radio"]'))->toHaveCount(2)
            ->and($crawler->filter('input[disabled]'))->toHaveCount(0);
    });
});

describe('option vocabularies', function (): void {
    it("disables the option a sibling's value names in the field's own vocabulary", function (Closure $radio, mixed $sibling, string $option): void {
        $crawler = $this->renderSchemaComponent(
            Repeater::make('slots')->components([$radio()->disableOptionsWhenSelectedInSiblingRepeaterItems()]),
            ['slots' => ['x' => ['choice' => $sibling], 'y' => ['choice' => null]]],
        );

        expect($crawler->filter(FormsSelectors::repeaterItem('y') . ' input[disabled]')->each(static fn (Crawler $input): string => $input->attr('value') ?? ''))->toBe([$option]);
    })->with([
        'a backed enum' => [fn (): Radio => Radio::make('choice')->options(StringBackedLetter::class), StringBackedLetter::B, 'b'],
        'a boolean' => [fn (): Radio => Radio::make('choice')->boolean(), false, '0'],
        'an integer-keyed option the wire sends as a string' => [fn (): Radio => Radio::make('choice')->options([1 => 'One', 2 => 'Two']), '2', '2'],
    ]);
});

describe('the sibling path arithmetic', function (): void {
    it("finds each item's value through the field's own path inside a layout component", function (): void {
        $crawler = $this->renderSchemaComponent(
            Repeater::make('slots')->components([Section::make()->components([Radio::make('choice')->options(['a' => 'A', 'b' => 'B'])->disableOptionsWhenSelectedInSiblingRepeaterItems()])]),
            ['slots' => ['x' => ['choice' => 'b'], 'y' => ['choice' => null]]],
        );

        expect($crawler->filter(FormsSelectors::repeaterItem('y') . ' input[disabled]')->each(static fn (Crawler $input): string => $input->attr('value') ?? ''))->toBe(['b']);
    });
});
