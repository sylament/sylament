<?php

declare(strict_types=1);

use Sylament\Forms\Validation\ActiveUrl;
use Sylament\Forms\Validation\ActiveUrlValidator;
use Sylament\Tests\Fixtures\Forms\ListedHostActiveUrlValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;

it("passes a URL whose host has a DNS record, and refuses one whose host has none, a value with no host and a value that is no text", function (mixed $value, bool $passes): void {
    $validator = Validation::createValidatorBuilder()
        ->setConstraintValidatorFactory(new ConstraintValidatorFactory([ActiveUrlValidator::class => new ListedHostActiveUrlValidator()]))
        ->getValidator();

    expect(array_map(static fn (ConstraintViolationInterface $violation): string => (string) $violation->getMessage(), iterator_to_array($validator->validate($value, new ActiveUrl()))))
        ->toBe($passes ? [] : ['This value is not an active URL.']);
})->with([
    'a resolving host' => ['https://resolves.test/path', true],
    'a host with no record' => ['https://nowhere.test', false],
    'no host' => ['not a url', false],
    'a list' => [['https://resolves.test'], false],
    'a blank value' => ['', true],
]);
