<?php

declare(strict_types=1);

use Sylament\Schemas\State\Casts\FileUploadStateCast;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

it('reads the files as a list when multiple, and the first file otherwise', function (bool $isMultiple, mixed $state, mixed $expected): void {
    $cast = $isMultiple ? new FileUploadStateCast(isMultiple: true) : new FileUploadStateCast();

    expect($cast->get($state))->toBe($expected);
})->with([
    'multiple, keyed files' => [true, ['k1' => 'a.png', 'k2' => 'b.png'], ['a.png', 'b.png']],
    'multiple, one file' => [true, 'a.png', ['a.png']],
    'multiple, none' => [true, null, []],
    'single, keyed files' => [false, ['k1' => 'a.png', 'k2' => 'b.png'], 'a.png'],
    'single, one file' => [false, 'a.png', 'a.png'],
    'single, none' => [false, null, null],
]);

it('writes the files keyed by uuid, dropping blank ones and keeping a key that is not a number', function (): void {
    $cast = new FileUploadStateCast();

    $state = $cast->set(['a.png', '', 'kept' => 'b.png']);
    $uuid = array_key_first($state);

    expect($state)->toHaveCount(2)
        ->and($state[$uuid])->toBe('a.png')
        ->and($state['kept'])->toBe('b.png')
        ->and(Uuid::isValid((string) $uuid) && Uuid::fromString((string) $uuid) instanceof UuidV4)->toBeTrue();

    $wrapped = $cast->set('c.png');

    expect($wrapped)->toHaveCount(1)
        ->and(array_values($wrapped))->toBe(['c.png'])
        ->and(array_key_first($wrapped))->not->toBe($uuid);
});
