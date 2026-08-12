<?php

declare(strict_types=1);

use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Tests\Fixtures\Pages\SettingsPage;

it('carries the page class and its path', function (): void {
    $registration = new PageRegistration(SettingsPage::class, '/{record}/edit');

    expect($registration->page)->toBe(SettingsPage::class)
        ->and($registration->path)->toBe('/{record}/edit');
});
