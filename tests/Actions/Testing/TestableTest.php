<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Actions\ToolbarHost;
use Sylament\Tests\Fixtures\Schemas\CodeForm;

it('rethrows what a host action throws, never an error page, while a failed validation answers its re-render', function (): void {
    expect($this->createLiveComponent(CodeForm::class)->call('save')->response()->getStatusCode())->toBe(422)
        ->and(fn () => $this->createLiveComponent(ToolbarHost::class)->call('fail'))->toThrow(RuntimeException::class, 'The toolbar failed.');
});
