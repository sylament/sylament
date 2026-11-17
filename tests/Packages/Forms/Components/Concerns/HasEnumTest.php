<?php

declare(strict_types=1);

use Sylament\Forms\Components\Radio;
use Sylament\Tests\Fixtures\Forms\DeliveryMode;
use Sylament\Tests\Fixtures\Forms\PlainSuit;
use Sylament\Tests\Fixtures\Forms\RadioForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Sylament\Tests\Fixtures\Schemas\TaggingStateCast;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Error\RuntimeError;

describe('enum()', function (): void {
    it('offers the cases of the enum a field is given and reads its state as the case', function (): void {
        $tester = $this->createLiveComponent(RadioForm::class);

        expect($tester->render()->crawler()->filter('[aria-labelledby="form.mode-label"] ' . Selectors::RADIO)->each(static fn (Crawler $input): ?string => $input->attr('value')))->toBe(['standard', 'express', 'courier', 'pickup'])
            ->and($tester->fillForm(['mode' => 'express'])->component()->getSchema('form')->getComponent('mode')?->getState())->toBe(DeliveryMode::Express);
    });

    it('keys the options of a pure enum by case name, yet refuses it at the first render, naming the field and the enum', function (): void {
        expect(Radio::make('suit')->options(PlainSuit::class)->getOptions())->toBe(['Hearts' => 'Hearts', 'Spades' => 'Spades'])
            ->and(fn () => $this->renderSchemaComponent(Radio::make('suit')->options(PlainSuit::class)))
            ->toThrow(RuntimeError::class, 'The [suit] field was given the enum [' . PlainSuit::class . '], which is not a backed enum, so its cases cannot round-trip through form state. Back the enum with string or int values.');
    });
});

describe('state casts', function (): void {
    it('stores a case as its backing value and reads a stored value back as its case, a value no case backs as nothing', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Radio::make('mode')->enum(DeliveryMode::class))->fill(['mode' => DeliveryMode::Express]);
        $stored = $host->data['mode'];
        $host->data['mode'] = 'drone';

        expect($stored)->toBe('express')
            ->and($schema->getComponent('mode')?->getState())->toBeNull();
    });

    it('runs the enum cast ahead of a custom cast, which then reads the case rather than the stored value', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Radio::make('mode')->enum(DeliveryMode::class)->stateCast(TaggingStateCast::First));
        $host->data['mode'] = 'express';

        expect($schema->getComponent('mode')?->getState())->toBe(' first:get');
    });
});
