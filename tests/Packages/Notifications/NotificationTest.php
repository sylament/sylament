<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Notifications\Delivery\SessionNotifications;
use Sylament\Notifications\Notification;
use Sylament\Support\Colors\Color;
use Sylament\Support\Enums\IconSize;
use Sylament\Support\Icons\IconManager;
use Sylament\Tests\Fixtures\Notifications\Selectors;
use Sylament\Tests\Fixtures\Support\Icons\SizedIcon;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('delivers a sent toast with everything it was configured with', function (): void {
    $this->startSessionRequest();

    Notification::make()
        ->title('Publishing failed')
        ->body('The feed is offline.')
        ->danger()
        ->color(Color::Rose)
        ->icon('notifications:bell')
        ->iconColor('info')
        ->seconds(2)
        ->actions([Action::make('retry')->url('/feeds/1')])
        ->send();

    $stack = $this->renderTwigComponent('SylamentNotifications:Notifications')->crawler();
    $toast = $stack->filter(Selectors::TOAST);

    expect($stack)
        ->toContainElement(Selectors::TITLE, 'Publishing failed')
        ->toContainElement(Selectors::BODY, 'The feed is offline.')
        ->toContainElement(Selectors::ICON . SupportSelectors::color('info'), 'Bell')
        ->toContainElement(Selectors::ACTIONS . ' a[href="/feeds/1"]', 'Retry')
        ->and($toast->attr('role'))->toBe('alert')
        ->and($toast->attr('style'))->toContain('--sy-color-500: ' . Color::Rose[500])
        ->and($toast->attr(Selectors::DURATION))->toBe('2000');
});

it('drops a flashed value it cannot type and delivers the rest of the toast', function (): void {
    $this->startSessionRequest()->getFlashBag()->add(SessionNotifications::FLASH_KEY, [
        'title' => ['Saved'],
        'body' => 'All changes are live.',
        'color' => array_replace(Color::Rose, [500 => 7]),
        'duration' => 1.5,
        'actions' => ['retry', ['name' => 'view', 'url' => '/articles/1']],
    ]);

    $stack = $this->renderTwigComponent('SylamentNotifications:Notifications')->crawler();

    expect($stack)
        ->not->toContainElement(Selectors::TITLE)
        ->toContainElement(Selectors::BODY, 'All changes are live.')
        ->toContainElement(Selectors::TOAST . SupportSelectors::GRAY)
        ->toContainElement(Selectors::ACTIONS . ' a[href="/articles/1"]', 'View')
        ->and($stack->filter(Selectors::TOAST)->attr(Selectors::DURATION))->toBe('6000');
});

it("draws a scalable icon at the icon size, and a sent toast's at the default size", function (): void {
    $this->startSessionRequest();
    $notification = Notification::make()->title('Saved')->icon(SizedIcon::Star)->iconSize(IconSize::Large);

    expect($this->renderViewComponent($notification))->toContainElement(Selectors::ICON, 'Star, large');

    $notification->send();

    expect($this->renderTwigComponent('SylamentNotifications:Notifications')->crawler())->toContainElement(Selectors::ICON, 'Star, medium');
});

it('refuses to send from an instance the container did not build', function (): void {
    expect(fn (): Notification => new Notification()->title('Saved')->send())
        ->toThrow(LogicException::class, 'Notification::send() needs the container-built instance');
});

it('interrupts with a danger toast and leaves any other to the polite stack', function (): void {
    expect($this->renderViewComponent(Notification::make()->title('Failed')->danger())->filter(Selectors::TOAST)->attr('role'))->toBe('alert')
        ->and($this->renderViewComponent(Notification::make()->title('Saved')->success())->filter(Selectors::TOAST)->attr('role'))->toBeNull();
});

it('strips unsafe markup and every attribute from the title and body', function (): void {
    $toast = $this->renderViewComponent(Notification::make()
        ->title('Saved <b>fine</b><script>alert(1)</script>')
        ->body('<span class="badge" onclick="steal()">Live</span>'));

    expect($toast)
        ->toContainElement(Selectors::TITLE . ' b', 'fine')
        ->toContainElement(Selectors::BODY . ' span', 'Live')
        ->not->toContainElement('script')
        ->not->toContainElement(Selectors::BODY . ' span[class]');
});

it('shows its icon over the status icon, whatever the status alias names', function (): void {
    $this::getContainer()->get(IconManager::class)->register(['notification.success' => 'notifications:bell-lg']);

    expect($this->renderViewComponent(Notification::make()->title('Saved')->success()->icon('notifications:bell')))
        ->toContainElement(Selectors::ICON, 'Bell');
});

it('paints its icon in the icon colour over the status colour, and gray without either', function (): void {
    expect($this->renderViewComponent(Notification::make()->title('Saved')->success()->iconColor('info')))
        ->toContainElement(Selectors::ICON . SupportSelectors::color('info'))
        ->and($this->renderViewComponent(Notification::make()->title('Saved')->icon('notifications:bell')))
        ->toContainElement(Selectors::ICON . SupportSelectors::GRAY);
});

it('tints the toast in its colour, and leaves it gray without one', function (): void {
    expect($this->renderViewComponent(Notification::make()->title('Saved')->color('primary')))
        ->toContainElement(Selectors::TOAST . SupportSelectors::color('primary'))
        ->and($this->renderViewComponent(Notification::make()->title('Saved')))
        ->toContainElement(Selectors::TOAST . SupportSelectors::GRAY);
});

it('closes from a medium button named "Close notification" that shows the mini x-mark', function (): void {
    $toast = $this->renderViewComponent(Notification::make()->title('Saved'));

    expect($toast)
        ->toContainElement(Selectors::CLOSING_BUTTON . SupportSelectors::MEDIUM)
        ->toContainElement(Selectors::CLOSE_BUTTON . ' svg', 'x-mark-20-solid');
});
