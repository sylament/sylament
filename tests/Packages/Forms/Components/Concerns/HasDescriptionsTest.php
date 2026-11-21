<?php

declare(strict_types=1);

use Sylament\Forms\Components\Radio;
use Sylament\Tests\Fixtures\Forms\DeliveryMode;
use Sylament\Tests\Fixtures\Forms\TranslatedStatus;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;

use function Symfony\Component\Translation\t;

describe('descriptions()', function (): void {
    it('describes the options its map names, and those of the map a later descriptions() call gives after a render', function (): void {
        $radio = Radio::make('plan')->options(['basic' => 'Basic', 'pro' => 'Pro'])->descriptions(['basic' => 'For one']);
        $first = $this->radioDescriptions($this->renderSchemaComponent($radio));
        $radio->descriptions(['pro' => 'For teams']);

        expect($first)->toBe([['basic', 'For one'], ['pro', null]])
            ->and($this->radioDescriptions($this->renderSchemaComponent($radio)))->toBe([['basic', null], ['pro', 'For teams']]);
    });
});

describe('lookups', function (): void {
    it('renders a description line under a described option alone, an empty one for an empty description its map names', function (): void {
        expect($this->radioDescriptions($this->renderSchemaComponent(Radio::make('plan')->options(['basic' => 'Basic', 'pro' => 'Pro', 'team' => 'Team'])->descriptions(['basic' => 'For one', 'team' => '']))))
            ->toBe([['basic', 'For one'], ['pro', null], ['team', '']]);
    });
});

describe('enum descriptions', function (): void {
    it("describes each option by the enum's HasDescription, a case answering null or an empty string with no line, and an enum without the contract with none", function (): void {
        expect($this->radioDescriptions($this->renderSchemaComponent(Radio::make('mode')->enum(DeliveryMode::class))))->toBe([
            ['standard', 'Three to five days'],
            ['express', 'Next day'],
            ['courier', null],
            ['pickup', null],
        ])
            ->and($this->radioDescriptions($this->renderSchemaComponent(Radio::make('letter')->enum(StringBackedLetter::class))))->toBe([['a', null], ['b', null], ['c', null]])
            ->and($this->radioDescriptions($this->renderSchemaComponent(Radio::make('status')->enum(TranslatedStatus::class))))->toBe([['draft', 'Not yet public (translated)'], ['published', null]]);
    });

    it("takes the enum's descriptions only while its own map is empty, a closure answering an empty map included", function (): void {
        expect($this->radioDescriptions($this->renderSchemaComponent(Radio::make('mode')->enum(DeliveryMode::class)->descriptions(['courier' => 'Same day']))))->toBe([
            ['standard', null],
            ['express', null],
            ['courier', 'Same day'],
            ['pickup', null],
        ])
            ->and($this->radioDescriptions($this->renderSchemaComponent(Radio::make('mode')->enum(DeliveryMode::class)->descriptions(static fn (): array => []))))->toBe([
                ['standard', 'Three to five days'],
                ['express', 'Next day'],
                ['courier', null],
                ['pickup', null],
            ]);
    });
});

it('translates a description given as a message', function (): void {
    expect($this->radioDescriptions($this->renderSchemaComponent(Radio::make('plan')->options(['basic' => 'Basic', 'pro' => 'Pro'])->descriptions(['basic' => t('option.basic_description', [], 'fixtures-forms')]))))
        ->toBe([['basic', 'For one (translated)'], ['pro', null]]);
});
