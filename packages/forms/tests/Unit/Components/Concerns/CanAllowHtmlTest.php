<?php

declare(strict_types=1);

use Sylament\Forms\Tests\Fixtures\Components\FixtureOptionsField;

it('escapes option labels by default', function (): void {
    expect(FixtureOptionsField::make('status')->isHtmlAllowed())->toBeFalse();
});

it('allows html', function (): void {
    expect(FixtureOptionsField::make('status')->allowHtml()->isHtmlAllowed())->toBeTrue()
        ->and(FixtureOptionsField::make('status')->allowHtml(false)->isHtmlAllowed())->toBeFalse();
});

it('evaluates an allow html closure', function (): void {
    expect(FixtureOptionsField::make('status')->allowHtml(fn (): bool => true)->isHtmlAllowed())->toBeTrue();
});
