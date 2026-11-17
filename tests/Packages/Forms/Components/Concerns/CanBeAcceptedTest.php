<?php

declare(strict_types=1);

use Sylament\Forms\Components\Checkbox;
use Sylament\Schemas\Exceptions\ValidationException;
use Symfony\Component\Validator\Constraints\IsFalse;
use Symfony\Component\Validator\Constraints\IsTrue;

beforeEach(function (): void {
    $this->host = $this->validatingRecordMappingHost();
});

describe('accepted()', function (): void {
    it('demands a true value, passing an unset key, and adds nothing when its condition is false', function (): void {
        $schema = $this->host->mapping(
            Checkbox::make('ticked')->accepted(),
            Checkbox::make('unticked')->accepted(),
            Checkbox::make('unset')->accepted(),
            Checkbox::make('optional')->accepted(false),
        );
        $this->host->data = ['ticked' => true, 'unticked' => false, 'optional' => false];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.unticked' => ['This value should be true.']]);
        });
    });

    it("takes its message from validationMessages() whichever call order, and fails a word Laravel's rule would accept", function (): void {
        $schema = $this->host->mapping(
            Checkbox::make('before')->validationMessages([IsTrue::class => 'Accept the terms.'])->accepted(),
            Checkbox::make('after')->accepted()->validationMessages([IsTrue::class => 'Accept the terms.']),
            Checkbox::make('worded')->accepted(),
        );
        $this->host->data = ['before' => false, 'after' => false, 'worded' => 'yes'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.before' => ['Accept the terms.'],
                'data.after' => ['Accept the terms.'],
                'data.worded' => ['This value should be of type bool.', 'This value should be true.'],
            ]);
        });
    });
});

describe('declined()', function (): void {
    it('demands a false value, passing an unset key while refusing a blank string, under its own message when given one', function (): void {
        $schema = $this->host->mapping(
            Checkbox::make('unticked')->declined(),
            Checkbox::make('ticked')->declined(),
            Checkbox::make('blank')->declined(),
            Checkbox::make('unset')->declined(),
            Checkbox::make('worded')->declined()->validationMessages([IsFalse::class => 'Leave it unticked.']),
        );
        $this->host->data = ['unticked' => false, 'ticked' => true, 'blank' => '', 'worded' => true];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.ticked' => ['This value should be false.'],
                'data.blank' => ['This value should be false.'],
                'data.worded' => ['Leave it unticked.'],
            ]);
        });
    });
});
