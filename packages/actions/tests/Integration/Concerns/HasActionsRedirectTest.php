<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Concerns;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * A redirect leaves the pipeline as a RedirectResponse returned from
 * callMountedAction() — the shipped CreateRecord::create() mechanism, which UX
 * Live turns into a 204 + X-Live-Redirect for the browser (and passes through
 * untouched in test mode).
 */

it('returns a redirect response to the action url and unmounts the stack', function (): void {
    $host = $this->createLiveComponent('RedirectActionHost')->component();

    $response = $host->mountAction('save');

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe('/after')
        ->and($host->called)->toBeTrue()
        ->and($host->mountedActions)->toBe([]);
});

it('falls back to the host success hook when the action carries no url', function (): void {
    $host = $this->createLiveComponent('RedirectActionHost')->component();

    $response = $host->mountAction('hostDefault');

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe('/from-host');
});

it('takes the failure url when the action reports failure', function (): void {
    $host = $this->createLiveComponent('RedirectActionHost')->component();

    $response = $host->mountAction('hostFailure');

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe('/from-host-failure');
});

it('returns the closure result and unmounts when no redirect is pending', function (): void {
    $host = $this->createLiveComponent('RedirectActionHost')->component();

    expect($host->mountAction('plain'))->toBe('plain result')
        ->and($host->mountedActions)->toBe([]);
});

it('passes the redirect through the live request as a real redirection', function (): void {
    $response = $this->createLiveComponent('RedirectActionHost')
        ->call('mountAction', ['name' => 'save', 'arguments' => [], 'context' => []])
        ->response();

    expect($response->isRedirection())->toBeTrue()
        ->and($response->headers->get('Location'))->toBe('/after');
});
