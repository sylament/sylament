<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Tests\Fixtures\Actions\Entity\Product;
use Sylament\Tests\Fixtures\Actions\ProductTable;
use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Symfony\Component\Validator\Constraints\Length;

it('fails asserting a form state the host does not hold', function (): void {
    $tester = $this->createLiveComponent(CodeForm::class)->fillForm(['code' => 'X1']);

    expect(fn () => $tester->assertFormSet(['code' => null]))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that a component [code] has the expected state');
});

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

it('passes asserting no form errors after a redirect and fails asserting some, since no validation failed', function (): void {
    $this->seed($product = new Product('Lamp'));

    $tester = $this->createLiveComponent(ProductTable::class)
        ->callAction(TestAction::make('open')->table($product))
        ->assertHasNoFormErrors();

    expect(fn () => $tester->assertHasFormErrors())->toThrow(AssertionFailedError::class, 'Component has no errors.');
});
