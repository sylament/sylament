<?php

declare(strict_types=1);

use Sylament\Testing\Doctrine\RecordingMiddleware;

it('records what runs, and nothing while suspended', function (): void {
    $recorder = new RecordingMiddleware();
    $recorder->record('SELECT 1');

    $result = $recorder->withoutRecording(static function () use ($recorder): string {
        $recorder->record('CREATE TABLE t (id INTEGER)');

        return 'built';
    });

    $recorder->record('SELECT 2');

    expect($result)->toBe('built')
        ->and($recorder->statements())->toBe(['SELECT 1', 'SELECT 2']);
});

it('resumes recording when the suspended callback throws', function (): void {
    $recorder = new RecordingMiddleware();

    expect(fn (): mixed => $recorder->withoutRecording(static fn (): never => throw new RuntimeException('boom')))
        ->toThrow(RuntimeException::class, 'boom');

    $recorder->record('SELECT 1');

    expect($recorder->statements())->toBe(['SELECT 1']);
});

it("answers the recorded statements with Doctrine's minted names renumbered", function (): void {
    $recorder = new RecordingMiddleware();
    $recorder->record('SELECT s7_.id FROM Post s7_');

    expect($recorder->normalizedStatements())->toBe(['SELECT a0_.id FROM Post a0_'])
        ->and($recorder->statements())->toBe(['SELECT s7_.id FROM Post s7_']);
});
