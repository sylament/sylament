<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State\Casts;

use Sylament\Schemas\State\Casts\FileUploadStateCast;

it('keys a bare file under a fresh uuid on the way in', function (): void {
    $state = new FileUploadStateCast()->set('covers/a.png');

    expect($state)->toBeArray()->toHaveCount(1)
        ->and(array_key_first($state))->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/')
        ->and(array_values($state))->toBe(['covers/a.png']);
});

it('mints a distinct key per numerically keyed entry', function (): void {
    $state = new FileUploadStateCast()->set(['covers/a.png', 'covers/b.png']);

    expect(array_keys($state))->toHaveCount(2)
        ->and(array_keys($state)[0])->not->toBe(array_keys($state)[1])
        ->and(array_values($state))->toBe(['covers/a.png', 'covers/b.png']);
});

it('keeps a string key it was handed', function (): void {
    expect(new FileUploadStateCast()->set(['abc-123' => 'covers/a.png']))
        ->toBe(['abc-123' => 'covers/a.png']);
});

it('drops blank entries and answers an empty map for blank state', function (): void {
    $cast = new FileUploadStateCast();

    expect($cast->set(null))->toBe([])
        ->and($cast->set([]))->toBe([])
        ->and($cast->set(''))->toBe([])
        ->and($cast->set(['a' => 'covers/a.png', 'b' => null, 'c' => '']))
        ->toBe(['a' => 'covers/a.png']);
});

it('reads a single-file field back as the first value', function (): void {
    $cast = new FileUploadStateCast();

    expect($cast->get(['abc' => 'covers/a.png', 'def' => 'covers/b.png']))->toBe('covers/a.png')
        ->and($cast->get([]))->toBeNull();
});

it('passes a non-array through on the way out of a single-file field', function (): void {
    $cast = new FileUploadStateCast();

    expect($cast->get('covers/a.png'))->toBe('covers/a.png')
        ->and($cast->get(null))->toBeNull();
});

it('reads a multiple field back as a list, dropping the keys', function (): void {
    $cast = new FileUploadStateCast(isMultiple: true);

    expect($cast->get(['abc' => 'covers/a.png', 'def' => 'covers/b.png']))
        ->toBe(['covers/a.png', 'covers/b.png'])
        ->and($cast->get([]))->toBe([])
        ->and($cast->get(null))->toBe([])
        ->and($cast->get('covers/a.png'))->toBe(['covers/a.png']);
});
