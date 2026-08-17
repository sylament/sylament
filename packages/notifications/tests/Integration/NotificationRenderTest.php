<?php

declare(strict_types=1);

use Sylament\Notifications\Notification;

function renderNotification(Notification $notification): string
{
    return $notification->render(test()->getContainer()->get('twig')) ?? '';
}

it('renders the toast chrome with status class, sanitized title and body', function (): void {
    $html = renderNotification(
        Notification::make('n1')
            ->title('Saved <b>fine</b> <script>alert(1)</script>')
            ->body('All <i>good</i>.')
            ->success(),
    );

    expect($html)->toContain('sy-no-notification')
        ->toContain('sy-status-success')
        ->toContain('Saved <b>fine</b>')
        ->not->toContain('<script>')
        ->toContain('<i>good</i>')
        ->not->toContain('role="alert"');
});

it('renders danger toasts as an assertive alert', function (): void {
    expect(renderNotification(Notification::make('n1')->title('Failed')->danger()))
        ->toContain('role="alert"');
});

it('falls back to the status icon and paints it with the status colour', function (): void {
    $html = renderNotification(Notification::make('n1')->title('Saved')->success());

    expect($html)->toContain('sy-no-notification-icon')
        ->toContain('<svg')
        ->toContain('sy-color-success');
});

it('lets an explicit icon win over the status fallback', function (): void {
    $html = renderNotification(Notification::make('n1')->icon('heroicons:x-mark')->success());

    expect($html)->toContain('sy-no-notification-icon')
        ->toContain('M6 18L18 6')
        ->not->toContain('<circle');
});

it('carries the duration to the toast controller and 0 for persistent', function (): void {
    expect(renderNotification(Notification::make('n1')->title('T')))
        ->toContain('data-controller="sylament--notifications--notification"')
        ->toContain('data-sylament--notifications--notification-duration-value="6000"');

    expect(renderNotification(Notification::make('n1')->title('T')->persistent()))
        ->toContain('data-sylament--notifications--notification-duration-value="0"');
});

it('applies an explicit colour through the notification policy', function (): void {
    expect(renderNotification(Notification::make('n1')->title('T')->color('primary')))
        ->toContain('sy-color-primary');
});

it('renders a labelled close button wired to the toast controller', function (): void {
    $html = renderNotification(Notification::make('n1')->title('T'));

    expect($html)->toContain('sy-no-notification-close-btn')
        ->toContain('aria-label="Close notification"')
        ->toContain('sylament--notifications--notification#close')
        ->toContain('sy-size-md')
        ->not->toContain('sy-size-sm');
});
