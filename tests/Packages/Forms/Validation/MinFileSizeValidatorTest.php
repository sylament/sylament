<?php

declare(strict_types=1);

use Sylament\Forms\Validation\MinFileSize;
use Sylament\Tests\Infrastructure\WorkerDirectory;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Validator\ValidatorInterface;

beforeEach(function (): void {
    $this->directory = WorkerDirectory::path() . '/storage/primary';
    $filesystem = new Filesystem();
    $filesystem->dumpFile("{$this->directory}/floor.bin", str_repeat('a', 2048));
    $filesystem->dumpFile("{$this->directory}/above.bin", str_repeat('a', 3000));
    $filesystem->dumpFile("{$this->directory}/below.bin", str_repeat('a', 1024));
});

it('passes a file at or above its floor in KiB and refuses a smaller one, an unreadable path counting as empty', function (string $file, ?string $message): void {
    $violations = $this::getContainer()->get(ValidatorInterface::class)->validate("{$this->directory}/{$file}", new MinFileSize(kilobytes: 2));

    expect(array_map(static fn ($violation): string => (string) $violation->getMessage(), iterator_to_array($violations)))->toBe($message === null ? [] : [$message]);
})->with([
    'a file at the floor' => ['floor.bin', null],
    'a file above it' => ['above.bin', null],
    'a file below it' => ['below.bin', 'The file is too small (1 KiB). Minimum size is 2 KiB.'],
    'a missing path' => ['missing.bin', 'The file is too small (0 KiB). Minimum size is 2 KiB.'],
]);

it('passes a blank value and refuses a value that names no path as the wrong type', function (): void {
    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $violations = $validator->validate(['below.bin'], new MinFileSize(kilobytes: 2));

    expect($validator->validate('', new MinFileSize(kilobytes: 2)))->toHaveCount(0)
        ->and($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value should be of type string.');
});
