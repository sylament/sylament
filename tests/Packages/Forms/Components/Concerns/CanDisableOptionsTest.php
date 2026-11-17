<?php

declare(strict_types=1);

use Sylament\Forms\Components\Radio;
use Sylament\Forms\Components\Select;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Tests\Fixtures\Forms\RadioForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Choice;

describe('disableOptionWhen()', function (): void {
    it('disables the options a predicate names by value or by label, every option for true, and any option one of its merged predicates names, a later unmerged predicate replacing them', function (): void {
        $disabled = fn (Radio $radio): array => $this->renderSchemaComponent($radio)->filter(Selectors::RADIO)->each(static fn (Crawler $input): array => [$input->attr('value'), $input->attr('disabled') !== null]);
        $radio = static fn (): Radio => Radio::make('plan')->options(['basic' => 'Basic', 'pro' => 'Pro', 'team' => 'Team']);
        $byValue = static fn (string $value): bool => $value === 'pro';
        $byLabel = static fn (string $label): bool => $label === 'Team';

        expect($disabled($radio()->disableOptionWhen($byValue)))->toBe([['basic', false], ['pro', true], ['team', false]])
            ->and($disabled($radio()->disableOptionWhen($byLabel)))->toBe([['basic', false], ['pro', false], ['team', true]])
            ->and($disabled($radio()->disableOptionWhen(true)))->toBe([['basic', true], ['pro', true], ['team', true]])
            ->and($disabled($radio()->disableOptionWhen($byValue)->disableOptionWhen($byLabel, merge: true)))->toBe([['basic', false], ['pro', true], ['team', true]])
            ->and($disabled($radio()->disableOptionWhen($byValue)->disableOptionWhen($byLabel)))->toBe([['basic', false], ['pro', false], ['team', true]]);
    });

    it('clears its predicates for null, while a merged null leaves the registered ones in force', function (): void {
        $disabled = fn (Radio $radio): array => $this->renderSchemaComponent($radio)->filter(Selectors::RADIO)->each(static fn (Crawler $input): array => [$input->attr('value'), $input->attr('disabled') !== null]);
        $radio = static fn (): Radio => Radio::make('plan')->options(['basic' => 'Basic', 'pro' => 'Pro', 'team' => 'Team']);
        $byValue = static fn (string $value): bool => $value === 'pro';

        expect($disabled($radio()->disableOptionWhen($byValue)->disableOptionWhen(null)))->toBe([['basic', false], ['pro', false], ['team', false]])
            ->and($disabled($radio()->disableOptionWhen($byValue)->disableOptionWhen(null, merge: true)))->toBe([['basic', false], ['pro', true], ['team', false]]);
    });
});

describe('enabled options', function (): void {
    it("validates a grouped option by its value, flattening the group, and refuses a disabled one or the group's label", function (string $state, bool $isValid): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(Select::make('plant')
            ->options(['Ferns' => ['fern' => 'Fern', 'moss' => 'Moss'], 'ivy' => 'Ivy'])
            ->disableOptionWhen(static fn (string $value): bool => $value === 'moss'));
        $host->data['plant'] = $state;

        try {
            $schema->validate();
            $errors = [];
        } catch (ValidationException $validationException) {
            $errors = $validationException->getErrors();
        }

        expect($errors)->toBe($isValid ? [] : ['data.plant' => ['The value you selected is not a valid choice.']]);
    })->with([
        'a grouped option' => ['fern', true],
        'an ungrouped option' => ['ivy', true],
        'a disabled grouped option' => ['moss', false],
        "the group's label" => ['Ferns', false],
    ]);
});

describe('validation choices', function (): void {
    it('admits an enabled option in the form a fill stores and in the form a browser submits, and refuses a disabled or tampered one', function (string $field, bool|int|string $state, bool $isValid): void {
        $tester = $this->createLiveComponent(RadioForm::class)->fillForm([$field => $state])->call('save');

        $isValid ? $tester->assertHasNoFormErrors([$field]) : $tester->assertHasFormErrors([$field => Choice::class]);
    })->with([
        'an enabled option' => ['plan', 'pro', true],
        'a disabled option' => ['plan', 'legacy', false],
        'the stored boolean answer' => ['subscribed', 1, true],
        'the submitted boolean answer' => ['subscribed', '0', true],
        'the boolean a custom cast stores' => ['confirmed', true, true],
        'the submitted int key' => ['priority', '2', true],
        'a tampered int key' => ['priority', '3', false],
        'an enum case value' => ['mode', 'standard', true],
    ]);
});
