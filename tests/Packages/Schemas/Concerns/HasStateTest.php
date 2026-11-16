<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\ProfileForm;

describe('fill', function (): void {
    it('fills its fields from an array, runs each hydrated hook after those of the fields inside it, and writes null where the array says nothing', function (): void {
        $tester = $this->createLiveComponent(ProfileForm::class)
            ->call('fillProfile')
            ->assertFormSet(['author' => ['name' => 'Ada'], 'greeting' => 'Hello Ada', 'stamp' => 'draft', 'draft' => 'kept']);

        expect($tester->component()->data)->toHaveKey('subject')
            ->and($tester->component()->data['subject'])->toBeNull();
    });

    it('fills a group whose state holds nothing, as a record without the related one leaves it', function (): void {
        $this->createLiveComponent(ProfileForm::class)
            ->call('fillWithoutAuthor')
            ->assertFormSet(['author' => ['name' => ''], 'greeting' => 'Hello ']);
    });

    it('fills its fields from the properties of an object', function (): void {
        $this->createLiveComponent(ProfileForm::class)
            ->call('fillFromMemo')
            ->assertFormSet(['subject' => 'Minutes']);
    });
});

describe('getState', function (): void {
    it('validates before it answers, a field inside a group whose state holds nothing included, running no hook that commits the state of an invalid form', function (): void {
        $this->createLiveComponent(ProfileForm::class)
            ->fillForm(['author' => null])
            ->call('takeState')
            ->assertHasFormErrors(['author.name'])
            ->assertFormSet(['stamp' => null]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);
    });

    it('answers the saved state once its fields have committed it, and hands the same state to the validated callback', function (): void {
        $this->createLiveComponent(ProfileForm::class)
            ->call('fillProfile')
            ->call('takeState');

        $state = '{"author":{"name":"Ada"},"stamp":"committed","greeting":"Hello Ada","subject":null}';

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["validated: {$state}", "state: {$state}"]);
    });
});

describe('getStateSnapshot', function (): void {
    it('answers the saved state without validating it or running the hooks that commit it', function (): void {
        $this->createLiveComponent(ProfileForm::class)
            ->call('fillProfile')
            ->fillForm(['author' => ['name' => '']])
            ->call('takeSnapshot')
            ->assertHasNoFormErrors()
            ->assertFormSet(['stamp' => 'draft']);

        expect($this::getContainer()->get(HookLog::class)->hooks)
            ->toBe(['snapshot: {"author":{"name":null},"stamp":"draft","greeting":"Hello Ada","subject":null}']);
    });
});
