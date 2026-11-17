<?php

declare(strict_types=1);

use Sylament\Forms\Components\Radio;
use Sylament\Tests\Fixtures\Forms\DeliveryMode;
use Sylament\Tests\Fixtures\Forms\PlainSuit;
use Sylament\Tests\Fixtures\Forms\RadioForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Choice;

describe('options()', function (): void {
    it('renders exactly the options of its map, in order, an int key among string ones', function (): void {
        $options = $this->renderSchemaComponent(Radio::make('plan')->options(['pro' => 'Pro', 1 => 'One', 'basic' => 'Basic']))
            ->filter('label')
            ->each(static fn (Crawler $option): array => [$option->filter(Selectors::RADIO)->attr('value'), $option->filter(Selectors::RADIO_OPTION_LABEL)->text()]);

        expect($options)->toBe([['pro', 'Pro'], ['1', 'One'], ['basic', 'Basic']]);
    });

    it('offers nothing for a string that names no enum, so it refuses every value', function (): void {
        $group = $this->renderSchemaComponent(Radio::make('dated')->options('date'))->filter(Selectors::RADIO_GROUP);

        expect($group)->toHaveCount(1)
            ->and($group->filter(Selectors::RADIO))->toHaveCount(0);

        $this->createLiveComponent(RadioForm::class)
            ->fillForm(['dated' => 'date'])
            ->call('save')
            ->assertHasFormErrors(['dated' => Choice::class]);
    });
});

describe('enum options', function (): void {
    it("offers one option per enum case keyed by its backing value and labelled by the enum's HasLabel, a case answering no label by its name and a markup label as markup, and labels an enum without the contract by its case names", function (): void {
        $options = fn (string $enum): Crawler => $this->renderSchemaComponent(Radio::make('mode')->options($enum))->filter('label');
        $delivery = $options(DeliveryMode::class);
        $read = static fn (Crawler $option): array => [$option->filter(Selectors::RADIO)->attr('value'), $option->filter(Selectors::RADIO_OPTION_LABEL)->text()];

        expect($delivery->each($read))->toBe([
            ['standard', 'Standard delivery'],
            ['express', 'Express delivery'],
            ['courier', 'By courier'],
            ['pickup', 'Pickup'],
        ])
            ->and($delivery->eq(1)->filter(Selectors::RADIO_OPTION_LABEL . ' b')->text())->toBe('Express')
            ->and($options(StringBackedLetter::class)->each($read))->toBe([['a', 'A'], ['b', 'B'], ['c', 'C']]);
    });

    it('reads its state as the case of the enum its options name', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Radio::make('mode')->options(DeliveryMode::class));
        $host->data['mode'] = 'courier';

        expect($schema->getComponent('mode')?->getState())->toBe(DeliveryMode::Courier);
    });
});

describe('comparison state', function (): void {
    it('checks the option an enum case or a submitted int key names', function (): void {
        $checked = static fn (Crawler $render, string $field): array => $render->filter("[aria-labelledby=\"form.{$field}-label\"] " . Selectors::RADIO . '[checked]')->each(static fn (Crawler $input): ?string => $input->attr('value'));
        $render = $this->createLiveComponent(RadioForm::class)->fillForm(['mode' => 'express', 'priority' => '2'])->render()->crawler();

        expect($checked($render, 'mode'))->toBe(['express'])
            ->and($checked($render, 'priority'))->toBe(['2']);
    });

    it('compares a boolean as the key 1 or 0, and an object that is no backed enum as no option at all', function (mixed $value, ?int $key): void {
        expect(Radio::make('plan')->normalizeOptionComparisonValue($value))->toBe($key);
    })->with([
        'true' => [true, 1],
        'false' => [false, 0],
        'a pure enum case' => [PlainSuit::Hearts, null],
        'a plain object' => [new stdClass(), null],
    ]);
});
