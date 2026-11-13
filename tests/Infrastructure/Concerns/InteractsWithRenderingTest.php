<?php

declare(strict_types=1);

use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

it('rethrows what a visited page threw, never its error page', function (): void {
    expect(fn () => $this->visit('/_tests/tally/1'))->toThrow(MethodNotAllowedHttpException::class);
});

it('names where a visited page redirected, since it follows no redirect', function (): void {
    expect(fn () => $this->visit('/_tests/redirect'))->toThrow(LogicException::class, '[GET /_tests/redirect] redirected to [http://localhost/_tests/landing].');
});
