<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use SplFileInfo;
use stdClass;
use Sylament\Forms\Validation\MinFileSize;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

function fileOfBytes(int $bytes, string $name = 'sample.bin'): string
{
    $path = sys_get_temp_dir() . '/sylament-min-file-size-' . bin2hex(random_bytes(6)) . '-' . $name;
    file_put_contents($path, str_repeat('x', $bytes));

    return $path;
}

it('flags a file under the minimum, naming the size, the limit and the unit', function (): void {
    $violations = Validation::createValidator()->validate(
        new UploadedFile(fileOfBytes(3), 'tiny.bin', null, null, true),
        new MinFileSize(kilobytes: 1),
    );

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('The file is too small (0 KiB). Minimum size is 1 KiB.');
});

it('accepts a file at or over the minimum', function (): void {
    $validator = Validation::createValidator();

    expect($validator->validate(new UploadedFile(fileOfBytes(2048), 'big.bin', null, null, true), new MinFileSize(kilobytes: 1)))->toHaveCount(0)
        ->and($validator->validate(new UploadedFile(fileOfBytes(1024), 'exact.bin', null, null, true), new MinFileSize(kilobytes: 1)))->toHaveCount(0);
});

it('accepts a plain path and an SplFileInfo, the value types FileValidator takes', function (): void {
    $path = fileOfBytes(2048);
    $validator = Validation::createValidator();

    expect($validator->validate($path, new MinFileSize(kilobytes: 1)))->toHaveCount(0)
        ->and($validator->validate(new SplFileInfo($path), new MinFileSize(kilobytes: 1)))->toHaveCount(0);
});

it('passes a null or empty value through, the NotBlank idiom', function (mixed $value): void {
    expect(Validation::createValidator()->validate($value, new MinFileSize(kilobytes: 1)))->toHaveCount(0);
})->with([
    'null' => [null],
    'empty string' => [''],
]);

it('flags a file that is not there rather than passing it', function (): void {
    $violations = Validation::createValidator()->validate(
        sys_get_temp_dir() . '/sylament-min-file-size-missing-' . bin2hex(random_bytes(6)),
        new MinFileSize(kilobytes: 1),
    );

    expect($violations)->toHaveCount(1);
});

it('rejects a value type no file can be read from', function (): void {
    // The validator throws `UnexpectedValueException`, which the recursive validator turns into
    // a type violation rather than letting it escape — so the message is what a caller observes.
    $violations = Validation::createValidator()->validate(new stdClass(), new MinFileSize(kilobytes: 1));

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value should be of type string.');
});

it('carries a message the caller overrides', function (): void {
    $violations = Validation::createValidator()->validate(
        new UploadedFile(fileOfBytes(3), 'tiny.bin', null, null, true),
        new MinFileSize(kilobytes: 1, message: 'Too small: {{ size }}/{{ limit }} {{ suffix }}.'),
    );

    expect($violations->get(0)->getMessage())->toBe('Too small: 0/1 KiB.');
});
