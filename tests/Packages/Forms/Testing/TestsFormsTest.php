<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Tests\Fixtures\Forms\RedirectingForm;
use Sylament\Tests\Fixtures\Forms\TagListForm;
use Sylament\Tests\Fixtures\Forms\WithdrawnCardForm;
use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Sylament\Tests\Fixtures\Schemas\RepeaterForm;
use Sylament\Tests\Fixtures\Schemas\ScopedValidationForm;
use Sylament\Tests\Fixtures\Schemas\StickyValidationForm;
use Symfony\Component\Validator\Constraints\Length;

it('asserts an error a failed save left by its field, its constraint or its message, and fails on one it did not leave', function (array $left, array $notLeft, string $message): void {
    $tester = $this->createLiveComponent(CodeForm::class)
        ->fillForm(['code' => ''])
        ->call('save')
        ->assertHasFormErrors($left);

    expect(fn () => $tester->assertHasFormErrors($notLeft))->toThrow(AssertionFailedError::class, $message);
})->with([
    'a field' => [['code'], ['note'], 'Component missing error: data.note'],
    'a constraint' => [['code' => RequiredValue::class], ['code' => Length::class], 'Component has no matching failed rule or error message [' . Length::class . '] for [data.code] attribute.'],
    'a message' => [['code' => 'This value should not be blank.'], ['code' => 'This value is too long.'], 'Component has no matching failed rule or error message [This value is too long.] for [data.code] attribute.'],
]);

it('asserts no error a failed save did not leave by its field, its constraint or its message, and fails on one it left', function (array $notLeft, array $left, string $message): void {
    $tester = $this->createLiveComponent(CodeForm::class)
        ->fillForm(['code' => ''])
        ->call('save')
        ->assertHasNoFormErrors($notLeft);

    expect(fn () => $tester->assertHasNoFormErrors($left))->toThrow(AssertionFailedError::class, $message);
})->with([
    'a field' => [['note'], ['code'], 'Component has error: data.code'],
    'a constraint' => [['code' => Length::class], ['code' => RequiredValue::class], 'Component has [' . RequiredValue::class . '] errors for [data.code] attribute.'],
    'a message' => [['code' => 'This value is too long.'], ['code' => 'This value should not be blank.'], 'Component has [This value should not be blank.] errors for [data.code] attribute.'],
]);

it('asserts a form has errors only after a failed save, and none only after a passed one', function (): void {
    $failed = $this->createLiveComponent(CodeForm::class)->fillForm(['code' => ''])->call('save')->assertHasFormErrors();
    $passed = $this->createLiveComponent(CodeForm::class)->fillForm(['code' => 'X1'])->call('save')->assertHasNoFormErrors();

    expect(fn () => $failed->assertHasNoFormErrors())->toThrow(AssertionFailedError::class, 'Component has errors: "data.code" => ["This value should not be blank."]')
        ->and(fn () => $passed->assertHasFormErrors())->toThrow(AssertionFailedError::class, 'Component has no errors.');
});

it('reads only the errors of the form under test, not those another form on the host shows', function (): void {
    $tester = $this->createLiveComponent(ScopedValidationForm::class)
        ->call('saveSettings')
        ->assertHasFormErrors(['theme'], form: 'settings')
        ->assertHasNoFormErrors();

    expect(fn () => $tester->assertHasFormErrors())->toThrow(AssertionFailedError::class, 'Component has no errors.');
});

it("reads a record's refusal no field maps, filed at the form's own path", function (): void {
    $tester = $this->createLiveComponent(WithdrawnCardForm::class)
        ->call('save')
        ->assertHasFormErrors();

    expect(fn () => $tester->assertHasNoFormErrors())->toThrow(AssertionFailedError::class, 'Component has errors: "data" => ["That title is withdrawn."]');
});

it('passes asserting no form errors after a redirect and fails asserting some, since no validation failed', function (): void {
    $tester = $this->createLiveComponent(RedirectingForm::class)
        ->fillForm(['code' => 'X1'])
        ->call('save')
        ->assertRedirect(RedirectingForm::REDIRECT_URL)
        ->assertHasNoFormErrors();

    expect(fn () => $tester->assertHasFormErrors())->toThrow(AssertionFailedError::class, 'Component has no errors.');
});

describe('fillForm()', function (): void {
    it('writes each key in its own request in the order given, so a later key wins over a hook and a hook over an earlier key', function (array $state, string $slug): void {
        $this->createLiveComponent(StickyValidationForm::class)
            ->fillForm($state)
            ->assertFormSet(['title' => 'Hello', 'slug' => $slug]);
    })->with([
        'a key after the field whose hook writes it' => [['title' => 'Hello', 'slug' => 'custom'], 'custom'],
        'a key before the field whose hook writes it' => [['slug' => 'custom', 'title' => 'Hello'], 'hello'],
    ]);

    it('writes a nested or dotted key under the form and leaves the keys it omits alone, the later of a key given twice winning', function (array $state, string $grace): void {
        $this->createLiveComponent(RepeaterForm::class)
            ->fillForm($state)
            ->assertFormSet(['members' => ['ada' => ['name' => 'Ada'], 'grace' => ['name' => $grace]]]);
    })->with([
        'a nested array' => [['members' => ['grace' => ['name' => 'Grace H']]], 'Grace H'],
        'a dotted key' => [['members.grace.name' => 'Grace H'], 'Grace H'],
        'a key given twice' => [['members' => ['grace' => ['name' => 'Grace H']], 'members.grace.name' => 'Grace M'], 'Grace M'],
    ]);

    it('replaces a list with the list it is given, a shorter or an empty one', function (array $tags): void {
        $this->createLiveComponent(TagListForm::class)
            ->fillForm(['tags' => $tags])
            ->assertFormSet(['tags' => $tags]);
    })->with([
        'a shorter list' => [['go']],
        'an empty list' => [[]],
    ]);

    it('removes a numeric key the filled state leaves out', function (): void {
        $this->createLiveComponent(TagListForm::class)
            ->fillForm(['tags' => [1 => 'twig']])
            ->assertFormSet(['tags' => [1 => 'twig']]);
    });

    it('empties the form when given no state', function (): void {
        $this->createLiveComponent(CodeForm::class)
            ->fillForm(['code' => 'X1'])
            ->assertFormSet(['code' => 'X1'])
            ->fillForm()
            ->assertFormSet(['code' => null]);
    });
});
