<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\HasLifecycleHooksFixture;

it('runs the before and after hooks when set', function (): void {
    $log = [];
    $fixture = new HasLifecycleHooksFixture()
        ->before(function () use (&$log): void {
            $log[] = 'before';
        })
        ->after(function () use (&$log): void {
            $log[] = 'after';
        });

    $fixture->callBefore();
    $fixture->callAfter();

    expect($log)->toBe(['before', 'after']);
});

it('is a safe no-op when hooks are unset', function (): void {
    $fixture = new HasLifecycleHooksFixture();

    expect($fixture->callBefore())->toBeNull()
        ->and($fixture->callAfter())->toBeNull();
});

it('runs the beforeFormFilled and afterFormFilled hooks when set', function (): void {
    $log = [];
    $fixture = new HasLifecycleHooksFixture()
        ->beforeFormFilled(function () use (&$log): void {
            $log[] = 'beforeFormFilled';
        })
        ->afterFormFilled(function () use (&$log): void {
            $log[] = 'afterFormFilled';
        });

    $fixture->callBeforeFormFilled();
    $fixture->callAfterFormFilled();

    expect($log)->toBe(['beforeFormFilled', 'afterFormFilled']);
});

it('is a safe no-op when the form-filled hooks are unset', function (): void {
    $fixture = new HasLifecycleHooksFixture();

    expect($fixture->callBeforeFormFilled())->toBeNull()
        ->and($fixture->callAfterFormFilled())->toBeNull();
});

it('runs the beforeFormValidated and afterFormValidated hooks when set', function (): void {
    $log = [];
    $fixture = new HasLifecycleHooksFixture()
        ->beforeFormValidated(function () use (&$log): void {
            $log[] = 'beforeFormValidated';
        })
        ->afterFormValidated(function () use (&$log): void {
            $log[] = 'afterFormValidated';
        });

    $fixture->callBeforeFormValidated();
    $fixture->callAfterFormValidated();

    expect($log)->toBe(['beforeFormValidated', 'afterFormValidated']);
});

it('is a safe no-op when the form-validated hooks are unset', function (): void {
    $fixture = new HasLifecycleHooksFixture();

    expect($fixture->callBeforeFormValidated())->toBeNull()
        ->and($fixture->callAfterFormValidated())->toBeNull();
});
