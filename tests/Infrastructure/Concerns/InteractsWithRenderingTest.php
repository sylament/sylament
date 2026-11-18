<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Notifications\NotificationsPageController;
use Sylament\Tests\Fixtures\Notifications\NotifyingHost;
use Sylament\Tests\Fixtures\Notifications\Selectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

it('rethrows what a visited page threw, never its error page', function (): void {
    expect(fn () => $this->visit('/_tests/tally/1'))->toThrow(MethodNotAllowedHttpException::class);
});

it('names where a visited page redirected, since it follows no redirect', function (): void {
    expect(fn () => $this->visit('/_tests/redirect'))->toThrow(LogicException::class, '[GET /_tests/redirect] redirected to [http://localhost/_tests/landing].');
});

it("opens the page on a given client, with the session that client's earlier requests left", function (): void {
    $client = $this::getContainer()->get('test.client');
    $this->createLiveComponent(NotifyingHost::class, client: $client)->call('saveAndLeave');

    $titles = fn (Crawler $page): array => $page->filter(Selectors::STACK . ' ' . Selectors::TITLE)->each(fn (Crawler $title): string => $title->text());

    expect($titles($this->visit(NotificationsPageController::URL, client: $client)))->toBe(['Saved', 'Welcome']);
});

it('leaves a given client catching exceptions as it did, even after a page that threw', function (): void {
    $catching = $this::getContainer()->get('test.client');
    $rethrowing = $this::getContainer()->get('test.client');
    $rethrowing->catchExceptions(false);

    expect(fn () => $this->visit('/_tests/tally/1', client: $catching))->toThrow(MethodNotAllowedHttpException::class);
    $catching->request('GET', '/_tests/tally/1');

    expect($catching->getResponse()->getStatusCode())->toBe(405)
        ->and(fn () => $this->visit('/_tests/tally/1', client: $rethrowing))->toThrow(MethodNotAllowedHttpException::class)
        ->and(fn () => $rethrowing->request('GET', '/_tests/tally/1'))->toThrow(MethodNotAllowedHttpException::class);
});
