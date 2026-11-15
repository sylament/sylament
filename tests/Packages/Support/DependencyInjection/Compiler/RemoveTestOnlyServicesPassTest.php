<?php

declare(strict_types=1);

use Sylament\Actions\Testing\ExceptionRecorder;
use Sylament\Notifications\Testing\NotificationRecorder;
use Sylament\Tests\Fixtures\Kernel;

it('keeps the test-only recorders only in a container compiled with framework.test on', function (): void {
    $testMode = new Kernel('test', true)->getCompiledContainer();
    $production = new Kernel('test_mode_off', true)->getCompiledContainer();

    expect($testMode->has(ExceptionRecorder::class))->toBeTrue()
        ->and($testMode->has(NotificationRecorder::class))->toBeTrue()
        ->and($production->has(ExceptionRecorder::class))->toBeFalse()
        ->and($production->has(NotificationRecorder::class))->toBeFalse();
});
