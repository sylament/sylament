<?php

declare(strict_types=1);

use Sylament\Forms\Components\Radio;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Tests\Fixtures\Forms\RadioForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Sylament\Tests\Fixtures\Schemas\TaggingStateCast;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Choice;

describe('render', function (): void {
    it('renders a radio group named by the field label, one radio per option named by the field id and bound to its state path, each labelled by its option and described only where the option has a description', function (): void {
        $render = $this->renderSchemaComponent(Radio::make('plan')->options(['basic' => 'Basic', 'pro' => 'Pro'])->descriptions(['pro' => 'For teams']));
        $group = $render->filter(Selectors::RADIO_GROUP);

        expect($render->filter(sprintf('[id="%s"]', $group->attr('aria-labelledby')))->text())->toBe('Plan')
            ->and($group->filter(Selectors::RADIO)->each(static fn (Crawler $input): array => [$input->attr('value'), $input->attr('id'), $input->attr('name'), $input->attr('data-model'), $input->attr('aria-invalid')]))->toBe([
                ['basic', 'schema.plan-basic', 'schema.plan', 'norender|data.plan', null],
                ['pro', 'schema.plan-pro', 'schema.plan', 'norender|data.plan', null],
            ])
            ->and($group->filter('label')->each(static fn (Crawler $option): array => [
                $option->filter(Selectors::RADIO_OPTION_LABEL)->text(),
                $option->filter(Selectors::RADIO_OPTION_DESCRIPTION)->count() === 1 ? $option->filter(Selectors::RADIO_OPTION_DESCRIPTION)->text() : null,
            ]))->toBe([['Basic', null], ['Pro', 'For teams']]);
    });

    it('names each group by the field id, so two radios of one name under different state paths stay two groups', function (): void {
        $radio = static fn (): Radio => Radio::make('choice')->options(['a' => 'A']);
        $render = $this->renderSchemaComponent(Group::make([
            Group::make([$radio()])->statePath('first'),
            Group::make([$radio()])->statePath('second'),
        ]));

        expect($render->filter(Selectors::RADIO)->each(static fn (Crawler $input): ?string => $input->attr('name')))->toBe(['schema.first.choice', 'schema.second.choice']);
    });
});

describe('boolean()', function (): void {
    it('offers Yes and No under the keys 1 and 0, or the labels it is given', function (): void {
        $options = fn (Radio $radio): array => $this->renderSchemaComponent($radio)->filter('label')->each(static fn (Crawler $option): array => [
            $option->filter(Selectors::RADIO)->attr('value'),
            $option->filter(Selectors::RADIO_OPTION_LABEL)->text(),
        ]);

        expect($options(Radio::make('subscribed')->boolean()))->toBe([['1', 'Yes'], ['0', 'No']])
            ->and($options(Radio::make('subscribed')->boolean('Agree', 'Refuse')))->toBe([['1', 'Agree'], ['0', 'Refuse']]);
    });

    it('stores true and false as the int keys 1 and 0 and reads them back as booleans, where an option radio stores the string key', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Radio::make('subscribed')->boolean(), Radio::make('declined')->boolean(), Radio::make('answer')->options([1 => 'Yes', 0 => 'No']))
            ->fill(['subscribed' => true, 'declined' => false, 'answer' => true]);

        expect($host->data)->toBe(['subscribed' => 1, 'declined' => 0, 'answer' => '1'])
            ->and($schema->getComponent('subscribed')?->getState())->toBeTrue()
            ->and($schema->getComponent('declined')?->getState())->toBeFalse();
    });
});

describe('default state', function (): void {
    it('fills a true or false default as the key 1 or 0, and any other default as it is', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(
            Radio::make('declined')->options([1 => 'Yes', 0 => 'No'])->default(false),
            Radio::make('agreed')->options([1 => 'Yes', 0 => 'No'])->default(true),
            Radio::make('plan')->options(['basic' => 'Basic'])->default('basic'),
        )->fill();

        expect(array_map(static fn (string $field): mixed => $schema->getComponent($field)?->getState(), ['declined', 'agreed', 'plan']))->toBe([0, 1, 'basic']);
    });
});

describe('state casts', function (): void {
    it('reads a submitted key back as its option key, an int key as the int, and a blank state as null', function (mixed $raw, int|string|null $state): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Radio::make('priority')->options([1 => 'Low', 2 => 'High', 'none' => 'None']));
        $host->data['priority'] = $raw;

        expect($schema->getComponent('priority')?->getState())->toBe($state);
    })->with([
        'an int key' => ['2', 2],
        'a string key' => ['none', 'none'],
        'a blank state' => ['', null],
    ]);

    it('drops the option cast once a custom cast is given, so the custom cast reads the submitted key as it came', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Radio::make('priority')->options([1 => 'Low', 2 => 'High'])->stateCast(TaggingStateCast::First));
        $host->data['priority'] = '2';

        expect($schema->getComponent('priority')?->getState())->toBe('2 first:get');
    });
});

describe('checked state', function (): void {
    it('renders checked on the stored option alone, never on another key that reads as the same number, moving it to the option a later request selects, and on the No answer of a boolean radio for 0 but on neither answer before anyone answers', function (): void {
        $checked = static fn (Crawler $render, string $field): array => $render->filter("[aria-labelledby=\"form.{$field}-label\"] " . Selectors::RADIO . '[checked]')->each(static fn (Crawler $input): ?string => $input->attr('value'));
        $tester = $this->createLiveComponent(RadioForm::class);
        $render = $tester->render()->crawler();

        expect($render->filter('[aria-labelledby="form.subscribed-label"] ' . Selectors::RADIO))->toHaveCount(2)
            ->and($checked($render, 'subscribed'))->toBe([])
            ->and($checked($render, 'plan'))->toBe([]);

        $render = $tester->fillForm(['plan' => 'basic', 'subscribed' => 0])->render()->crawler();

        expect($checked($render, 'plan'))->toBe(['basic'])
            ->and($checked($render, 'subscribed'))->toBe(['0']);

        $render = $tester->fillForm(['plan' => 'pro', 'subscribed' => '0', 'code' => '1'])->assertFormSet(['plan' => 'pro'])->render()->crawler();

        expect($checked($render, 'plan'))->toBe(['pro'])
            ->and($checked($render, 'subscribed'))->toBe(['0'])
            ->and($checked($render, 'code'))->toBe(['1']);
    });
});

describe('layout', function (): void {
    it('lays its options out in a column-wise grid by default, in the columns and direction it is given, and in a wrapping row while inline', function (): void {
        $group = fn (Radio $radio): Crawler => $this->renderSchemaComponent($radio->options(['a' => 'A']))->filter(Selectors::RADIO_GROUP);
        $default = $group(Radio::make('plan'));
        $rows = $group(Radio::make('plan')->columns(3)->gridDirection('row'));
        $inline = $group(Radio::make('plan')->inline());
        $notInline = $group(Radio::make('plan')->inline(false));

        expect([$default->matches(SupportSelectors::GRID_DIRECTION_COLUMN), $default->matches(SupportSelectors::INLINE)])->toBe([true, false])
            ->and([$rows->matches(SupportSelectors::columns('lg', 3)), $rows->matches(SupportSelectors::GRID_DIRECTION_COLUMN)])->toBe([true, false])
            ->and([$inline->matches(SupportSelectors::INLINE), $inline->attr('style')])->toBe([true, null])
            ->and([$notInline->matches(SupportSelectors::INLINE), $notInline->matches(SupportSelectors::GRID_DIRECTION_COLUMN)])->toBe([false, true]);
    });
});

describe('validation', function (): void {
    it('refuses a value it does not offer, marking the group and every option invalid and described by the error list', function (): void {
        $group = static fn (Crawler $render): array => $render->filter('[role="radiogroup"][aria-labelledby="form.plan-label"]')->each(static fn (Crawler $group): array => [$group->attr('aria-invalid'), $group->attr('aria-describedby')]);
        $options = static fn (Crawler $render): array => $render->filter('[aria-labelledby="form.plan-label"] ' . Selectors::RADIO)->each(static fn (Crawler $input): array => [$input->attr('aria-invalid'), $input->attr('aria-describedby')]);
        $tester = $this->createLiveComponent(RadioForm::class)->fillForm(['plan' => 'gold']);

        expect($options($tester->render()->crawler()))->toBe([[null, null], [null, null], [null, null]])
            ->and($group($tester->render()->crawler()))->toBe([[null, null]]);

        $tester->call('save')->assertHasFormErrors(['plan' => Choice::class]);

        expect($options($tester->render()->crawler()))->toBe([['true', 'form.plan-error'], ['true', 'form.plan-error'], ['true', 'form.plan-error']])
            ->and($group($tester->render()->crawler()))->toBe([['true', 'form.plan-error']]);
    });

    it('validates an enum radio by its cases, refusing a value no case backs though the enum cast reads it as nothing selected', function (string $state, bool $isValid): void {
        $tester = $this->createLiveComponent(RadioForm::class)->fillForm(['mode' => $state])->call('save');

        $isValid ? $tester->assertHasNoFormErrors(['mode']) : $tester->assertHasFormErrors(['mode' => Choice::class]);
    })->with([
        'a case value' => ['courier', true],
        'a value no case backs' => ['drone', false],
    ]);

    it('skips the membership check for a blank state, on a radio with nothing to enumerate too', function (?string $blank): void {
        $this->createLiveComponent(RadioForm::class)
            ->fillForm(['plan' => $blank, 'unusable' => $blank])
            ->call('save')
            ->assertHasNoFormErrors(['plan', 'unusable']);
    })->with([
        'null' => [null],
        'an empty string' => [''],
    ]);

    it('checks an in() list instead of the options, on a radio that has none', function (string $state, bool $isValid): void {
        $tester = $this->createLiveComponent(RadioForm::class)->fillForm(['restricted' => $state])->call('save');

        $isValid ? $tester->assertHasNoFormErrors(['restricted']) : $tester->assertHasFormErrors(['restricted' => Choice::class]);
    })->with([
        'a listed value' => ['yew', true],
        'a value the list leaves out' => ['moss', false],
    ]);

    it('refuses to validate a filled radio with nothing to enumerate, naming its state path', function (): void {
        expect(fn () => $this->createLiveComponent(RadioForm::class)->fillForm(['unusable' => 'x'])->call('save'))
            ->toThrow(LogicException::class, 'Sylament failed to validate the [data.unusable] radio because it has no options() configuration. Call options() so Sylament knows which values are valid.');
    });
});

describe('required', function (): void {
    it('marks a required, enabled group aria-required, never a disabled one, and describes the group and every radio by its helper text', function (): void {
        $radio = static fn (): Radio => Radio::make('plan')->options(['basic' => 'Basic', 'pro' => 'Pro'])->helperText('Billed monthly.')->required();
        $render = $this->renderSchemaComponent($radio());

        expect($render)->toContainElement(Selectors::RADIO_GROUP . '[aria-required="true"][aria-describedby="schema.plan-helper-text"]')
            ->and($render->filter(Selectors::RADIO . '[aria-describedby="schema.plan-helper-text"]'))->toHaveCount(2)
            ->and($this->renderSchemaComponent($radio()->disabled()))->toContainElement(Selectors::RADIO_GROUP . ':not([aria-required])');
    });

    it('marks each enabled radio of a required field required, none of a disabled field, and none a collapsible section may conceal', function (): void {
        $radio = static fn (): Radio => Radio::make('plan')->options(['basic' => 'Basic', 'pro' => 'Pro'])->disableOptionWhen(static fn (string $value): bool => $value === 'pro')->required();
        $required = static fn (Crawler $render): array => $render->filter(Selectors::RADIO)->each(static fn (Crawler $input): ?string => $input->attr('required'));

        expect($required($this->renderSchemaComponent($radio())))->toBe(['', null])
            ->and($required($this->renderSchemaComponent($radio()->disabled())))->toBe([null, null])
            ->and($required($this->renderSchemaComponent(Section::make('Plan')->collapsible()->components([$radio()]))))->toBe([null, null]);
    });

    it('accepts either answer of a required boolean radio, No included, refuses only no answer, and marks both radios required', function (): void {
        $tester = $this->createLiveComponent(RadioForm::class);
        $inputs = $tester->render()->crawler()->filter('[aria-labelledby="form.subscribed-label"] ' . Selectors::RADIO);

        expect($inputs)->toHaveCount(2)
            ->and($inputs->each(static fn (Crawler $input): ?string => $input->attr('required')))->toBe(['', '']);

        foreach ([0, '0', '1'] as $answer) {
            $tester->fillForm(['subscribed' => $answer])->call('save')->assertHasNoFormErrors(['subscribed']);
        }

        $tester->fillForm(['subscribed' => null])->call('save')->assertHasFormErrors(['subscribed' => RequiredValue::class]);
    });
});

describe('extra attributes', function (): void {
    it('puts its extra attributes on the field wrapper, the group repeating none of them', function (): void {
        $render = $this->renderSchemaComponent(Radio::make('plan')
            ->options(['basic' => 'Basic', 'pro' => 'Pro', 'team' => 'Team'])
            ->extraAttributes(['data-wrapper' => 'w', 'id' => 'mine']));
        $group = $render->filter(Selectors::RADIO_GROUP);

        expect($render->filter(Selectors::FIELD_WRAPPER)->each(static fn (Crawler $wrapper): array => [$wrapper->attr('data-wrapper'), $wrapper->attr('id')]))->toBe([['w', 'mine']])
            ->and($render->filter('[id="mine"]'))->toHaveCount(1)
            ->and([$group->count(), $group->attr('data-wrapper'), $group->attr('id')])->toBe([1, null, null]);
    });
});
