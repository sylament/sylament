<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\RedirectingHost;

it("redirects after it runs to its own URL for the outcome, else to the host's", function (string $action, string $url): void {
    $this->createLiveComponent(RedirectingHost::class)
        ->callAction($action)
        ->assertRedirect($url);
})->with([
    "the host's after a success" => ['home', '/home'],
    'its own after a success' => ['onward', '/onward'],
    'its own after a failure' => ['retry', '/try-again'],
]);

it('stays put after an outcome neither it nor its host names a URL for', function (): void {
    $this->createLiveComponent(RedirectingHost::class)
        ->callAction('stay')
        ->assertNoRedirect();
});

it('redirects nowhere outside a host', function (): void {
    expect(Action::make('home')->dispatchSuccessRedirect()->dispatchFailureRedirect()->getPendingRedirectUrl())->toBeNull();
});
