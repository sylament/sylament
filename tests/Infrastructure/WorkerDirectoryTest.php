<?php

declare(strict_types=1);

use Sylament\Tests\Infrastructure\WorkerDirectory;

it('puts every run beside the others, the unnamed one as default, so no name lands inside another run', function (): void {
    $before = getenv('SYLAMENT_TEST_RUN');

    try {
        putenv('SYLAMENT_TEST_RUN');
        $unnamed = WorkerDirectory::path();

        putenv('SYLAMENT_TEST_RUN=cache');

        expect(WorkerDirectory::path())->toBe(dirname(__DIR__, 2) . '/var/tests/cache')
            ->and($unnamed)->toBe(dirname(__DIR__, 2) . '/var/tests/default');
    } finally {
        putenv($before === false ? 'SYLAMENT_TEST_RUN' : 'SYLAMENT_TEST_RUN=' . $before);
    }
});

it('refuses a run name that is not a short lowercase word', function (string $run): void {
    $before = getenv('SYLAMENT_TEST_RUN');

    try {
        putenv('SYLAMENT_TEST_RUN=' . $run);

        expect(WorkerDirectory::path(...))->toThrow(LogicException::class, 'SYLAMENT_TEST_RUN');
    } finally {
        putenv($before === false ? 'SYLAMENT_TEST_RUN' : 'SYLAMENT_TEST_RUN=' . $before);
    }
})->with([
    'a character outside [a-z0-9_]' => ['../lane'],
    'longer than 16 characters' => [str_repeat('a', 17)],
]);

it('refuses a second claim on the worker directory this process already holds', function (): void {
    expect(WorkerDirectory::claim(...))->toThrow(RuntimeException::class, 'SYLAMENT_TEST_RUN');
});
