<?php

declare(strict_types=1);

use Sylament\Schemas\Exceptions\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

it('carries messages keyed by absolute state path', function (): void {
    $exception = new ValidationException(['data.title' => ['This value should not be blank.']]);

    expect($exception->getErrors())->toBe(['data.title' => ['This value should not be blank.']]);
});

it('is an UnprocessableEntityHttpException so a live request re-renders the component', function (): void {
    expect(new ValidationException([]))->toBeInstanceOf(UnprocessableEntityHttpException::class);
});
