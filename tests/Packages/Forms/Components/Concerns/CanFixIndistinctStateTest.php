<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Forms\IndistinctRepeaterHost;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;

describe('the installs', function (): void {
    it('refuses a value two items share at validation, and makes each pick a round trip', function (): void {
        $tester = $this->createLiveComponent(IndistinctRepeaterHost::class, ['data' => ['rows' => ['x' => ['pick' => 'a'], 'y' => ['pick' => 'a']]]]);

        expect($tester->render()->crawler()->filter('select[data-model="data.rows.x.pick"]'))->toHaveCount(1);

        $tester->call('save')->assertHasFormErrors(['rows.y.pick']);
    });

    it('installs nothing observable when its condition is false', function (): void {
        $tester = $this->createLiveComponent(IndistinctRepeaterHost::class, ['data' => ['loose' => ['x' => ['pick' => 'a'], 'y' => ['pick' => null]]]]);

        expect($tester->render()->crawler()->filter('select[data-model="norender|data.loose.x.pick"]'))->toHaveCount(1);

        $tester->fillForm(['loose' => ['y' => ['pick' => 'a']]])
            ->assertFormSet(['loose' => ['x' => ['pick' => 'a'], 'y' => ['pick' => 'a']]])
            ->call('save')
            ->assertHasNoFormErrors(['loose.x.pick', 'loose.y.pick']);
    });
});

describe('the scalar arm', function (): void {
    it('clears a sibling holding the picked value and leaves the others alone', function (): void {
        $this->createLiveComponent(IndistinctRepeaterHost::class, ['data' => ['rows' => ['x' => ['pick' => 'a'], 'y' => ['pick' => 'b'], 'z' => ['pick' => null]]]])
            ->fillForm(['rows' => ['z' => ['pick' => 'a']]])
            ->assertFormSet(['rows.x.pick' => null, 'rows.y.pick' => 'b', 'rows.z.pick' => 'a']);
    });

    it('leaves the sibling alone when an item clears its own value', function (): void {
        $this->createLiveComponent(IndistinctRepeaterHost::class, ['data' => ['rows' => ['x' => ['pick' => 'a'], 'y' => ['pick' => 'b']]]])
            ->fillForm(['rows' => ['y' => ['pick' => null]]])
            ->assertFormSet(['rows.x.pick' => 'a', 'rows.y.pick' => null]);
    });
});

describe('the bool arm', function (): void {
    it("unticks a sibling's ticked checkbox", function (): void {
        $this->createLiveComponent(IndistinctRepeaterHost::class, ['data' => ['rows' => ['x' => ['primary' => true], 'y' => ['primary' => false]]]])
            ->fillForm(['rows' => ['y' => ['primary' => true]]])
            ->assertFormSet(['rows.x.primary' => false, 'rows.y.primary' => true]);
    });
});

describe('the array arm', function (): void {
    it("subtracts the picked values from a sibling's selection, leaving a list", function (): void {
        $this->createLiveComponent(IndistinctRepeaterHost::class, ['data' => ['rows' => ['x' => ['picks' => []], 'y' => ['picks' => ['a', 'b']]]]])
            ->fillForm(['rows' => ['x' => ['picks' => ['a', 'c']]]])
            ->assertFormSet(['rows.x.picks' => ['a', 'c'], 'rows.y.picks' => ['b']]);
    });

    it('takes a pick off every sibling and stops there', function (): void {
        $this->createLiveComponent(IndistinctRepeaterHost::class, ['data' => ['rows' => ['x' => ['picks' => []], 'y' => ['picks' => ['a', 'b']], 'z' => ['picks' => ['b', 'c']]]]])
            ->fillForm(['rows' => ['x' => ['picks' => ['a']]]])
            ->assertFormSet(['rows.x.picks' => ['a'], 'rows.y.picks' => ['b'], 'rows.z.picks' => ['b', 'c']]);
    });

    it('ignores a non-scalar entry in the picked values and drops one from the sibling it repairs', function (): void {
        $this->createLiveComponent(IndistinctRepeaterHost::class, ['data' => ['rows' => ['x' => ['picks' => []], 'y' => ['picks' => ['a', ['bogus'], 'b']]]]])
            ->fillForm(['rows' => ['x' => ['picks' => ['a', ['b']]]]])
            ->assertFormSet(['rows.y.picks' => ['b']]);
    });
});

describe('the sibling path arithmetic', function (): void {
    it("finds each sibling's value through the field's own path inside a layout component of the item", function (): void {
        $this->createLiveComponent(IndistinctRepeaterHost::class, ['data' => ['rows' => ['x' => ['nested' => 'b'], 'y' => ['nested' => null]]]])
            ->fillForm(['rows' => ['y' => ['nested' => 'b']]])
            ->assertFormSet(['rows.x.nested' => null, 'rows.y.nested' => 'b']);
    });

    it('edits nothing for a field outside any repeater', function (): void {
        $this->createLiveComponent(IndistinctRepeaterHost::class, ['data' => ['rows' => ['x' => ['pick' => 'a']], 'solo' => null]])
            ->fillForm(['solo' => 'a'])
            ->assertFormSet(['solo' => 'a', 'rows.x.pick' => 'a']);
    });
});

describe('the enum-backed field', function (): void {
    it('repairs by the backing value', function (): void {
        $this->createLiveComponent(IndistinctRepeaterHost::class, ['data' => ['rows' => ['x' => ['letter' => 'a'], 'y' => ['letter' => null]]]])
            ->fillForm(['rows' => ['y' => ['letter' => 'a']]])
            ->assertFormSet(['rows.x.letter' => null, 'rows.y.letter' => StringBackedLetter::A]);
    });
});
