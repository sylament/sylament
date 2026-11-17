<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Actions\HydrationRefusingHost;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

it("rethrows a request's own exception on a kernel kept across requests, never one a request the tester did not send left behind", function (): void {
    $client = $this::getContainer()->get('test.client');
    $client->disableReboot();
    $client->request('GET', '/no-such-page');

    expect($client->getResponse()->getStatusCode())->toBe(404)
        ->and(fn () => $this->createLiveComponent(HydrationRefusingHost::class, ['refusing' => true], client: $client)->response())
        ->toThrow(UnprocessableEntityHttpException::class, 'Refused while hydrating.');
});
