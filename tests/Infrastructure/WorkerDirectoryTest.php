<?php

declare(strict_types=1);

use Sylament\Tests\Infrastructure\WorkerDirectory;

beforeEach(function (): void {
    $this->environment = ['SYLAMENT_TEST_RUN' => getenv('SYLAMENT_TEST_RUN'), 'TEST_TOKEN' => getenv('TEST_TOKEN')];
});

afterEach(function (): void {
    foreach ($this->environment as $name => $value) {
        putenv($value === false ? $name : $name . '=' . $value);
    }
});

it('puts every run beside the others, the unnamed one as default, so no name lands inside another run', function (): void {
    putenv('TEST_TOKEN');
    putenv('SYLAMENT_TEST_RUN');
    $unnamed = WorkerDirectory::path();

    putenv('SYLAMENT_TEST_RUN=cache');

    expect(WorkerDirectory::path())->toBe(dirname(__DIR__, 2) . '/var/tests/cache')
        ->and($unnamed)->toBe(dirname(__DIR__, 2) . '/var/tests/default');
});

it("puts a parallel worker under its run's workers/, a name no run writes in its own directory and none takes beside it", function (): void {
    putenv('SYLAMENT_TEST_RUN');
    putenv('TEST_TOKEN=1');
    $worker = WorkerDirectory::path();

    putenv('TEST_TOKEN');
    putenv('SYLAMENT_TEST_RUN=workers');

    expect($worker)->toBe(dirname(__DIR__, 2) . '/var/tests/default/workers/1')
        ->and(WorkerDirectory::path())->toBe(dirname(__DIR__, 2) . '/var/tests/workers');
});

it('refuses a run name that is not a short lowercase word', function (string $run): void {
    putenv('SYLAMENT_TEST_RUN=' . $run);

    expect(WorkerDirectory::path(...))->toThrow(LogicException::class, 'SYLAMENT_TEST_RUN');
})->with([
    'a character outside [a-z0-9_]' => ['../lane'],
    'longer than 16 characters' => [str_repeat('a', 17)],
]);

it('refuses a worker token that is not the number ParaTest gives', function (): void {
    putenv('TEST_TOKEN=../1');

    expect(WorkerDirectory::path(...))->toThrow(LogicException::class, 'TEST_TOKEN');
});

it('refuses a second claim on the worker directory this process already holds', function (): void {
    expect(WorkerDirectory::claim(...))->toThrow(RuntimeException::class, 'SYLAMENT_TEST_RUN');
});
