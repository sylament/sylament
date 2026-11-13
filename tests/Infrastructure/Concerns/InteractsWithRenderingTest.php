<?php

declare(strict_types=1);

use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

it('rethrows what a visited page threw, never its error page', function (): void {
    expect(fn () => $this->visit('/_tests/tally/1'))->toThrow(MethodNotAllowedHttpException::class);
});
