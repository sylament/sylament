<?php

declare(strict_types=1);

use Sylament\Panels\Panel;

it('leaves the unsaved-changes alert off by default', function (): void {
    expect(Panel::make()->hasUnsavedChangesAlerts())->toBeFalse();
});

it('arms the alert bare, by condition and from a closure', function (): void {
    expect(Panel::make()->unsavedChangesAlerts()->hasUnsavedChangesAlerts())->toBeTrue()
        ->and(Panel::make()->unsavedChangesAlerts(false)->hasUnsavedChangesAlerts())->toBeFalse()
        ->and(Panel::make()->unsavedChangesAlerts(fn (): bool => true)->hasUnsavedChangesAlerts())->toBeTrue();
});
