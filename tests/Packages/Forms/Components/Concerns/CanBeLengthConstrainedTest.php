<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\LengthForm;
use Symfony\Component\Validator\Constraints\Length;

describe('minLength() and maxLength()', function (): void {
    it('renders its bounds on the control', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('code')->minLength(3)->maxLength(5)))
            ->toContainElement('input[name="code"][minlength="3"][maxlength="5"]');
    });

    it('refuses a value shorter or longer than its bounds', function (string $value, string $message): void {
        $this->createLiveComponent(LengthForm::class)
            ->fillForm(['bounded' => $value])
            ->call('save')
            ->assertHasFormErrors(['bounded' => $message]);
    })->with([
        'too short' => ['ab', 'This value is too short. It should have 3 characters or more.'],
        'too long' => ['abcdef', 'This value is too long. It should have 5 characters or less.'],
    ]);

    it('takes a value as long as either bound', function (): void {
        $this->createLiveComponent(LengthForm::class)
            ->fillForm(['bounded' => 'abc'])
            ->call('save')
            ->assertHasNoFormErrors(['bounded'])
            ->fillForm(['bounded' => 'abcde'])
            ->call('save')
            ->assertHasNoFormErrors(['bounded']);
    });

    it('counts every character of a numeric value', function (): void {
        $this->createLiveComponent(LengthForm::class)
            ->fillForm(['amount' => '-1.5'])
            ->call('save')
            ->assertHasNoFormErrors(['amount'])
            ->fillForm(['amount' => '-12.50'])
            ->call('save')
            ->assertHasFormErrors(['amount' => Length::class]);
    });
});

describe('length()', function (): void {
    it('refuses a value of any other length with the exact-length message', function (): void {
        $this->createLiveComponent(LengthForm::class)
            ->fillForm(['exact' => 'abc'])
            ->call('save')
            ->assertHasFormErrors(['exact' => 'This value should have exactly 4 characters.'])
            ->fillForm(['exact' => 'abcd'])
            ->call('save')
            ->assertHasNoFormErrors(['exact']);
    });
});

describe('constraints', function (): void {
    it('builds no constraint from a zero bound, provisionally, so a filled value validates', function (): void {
        $this->createLiveComponent(LengthForm::class)
            ->fillForm(['zeroMax' => 'abc', 'zeroExact' => 'abc', 'bounded' => 'a'])
            ->call('save')
            ->assertHasFormErrors(['bounded'])
            ->assertHasNoFormErrors(['zeroMax', 'zeroExact']);
    });
});

describe('messages', function (): void {
    it("shows each bound's own message, and only where that bound fails", function (string $field, string $value, string $message, string $absent): void {
        $this->createLiveComponent(LengthForm::class)
            ->fillForm([$field => $value])
            ->call('save')
            ->assertHasFormErrors([$field => $message])
            ->assertHasNoFormErrors([$field => $absent]);
    })->with([
        'the minimum' => ['worded', 'ab', 'Say a little more.', 'Keep it short.'],
        'the maximum' => ['worded', 'abcdef', 'Keep it short.', 'Say a little more.'],
        'the exact length' => ['pin', 'abc', 'A pin has four characters.', 'This value should have exactly 4 characters.'],
    ]);

    it('refuses a plain message for a length, naming the options it has', function (): void {
        $field = $this->createLiveComponent(LengthForm::class)->component()->getSchema('form')->getComponent('bounded');

        expect(fn (): array => $field->validationMessages([Length::class => 'Too long.'])->getValidationConstraints())
            ->toThrow(LogicException::class, 'Sylament cannot apply a validation message to [data.bounded] because ' . Length::class . ' has no [message] message option. Use one of: [maxMessage], [minMessage], [exactMessage], [charsetMessage].');
    });
});
