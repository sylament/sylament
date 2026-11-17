<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Support\Enums\IconPosition;
use Sylament\Support\Enums\IconSize;
use Sylament\Support\Enums\Size;
use Sylament\Tests\Fixtures\Actions\MountedActionsHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Support\Icons\SizedIcon;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

describe('trigger rendering', function (): void {
    it('renders each look through its support primitive, a button unless told otherwise, marked as an action', function (Closure $action, string $look): void {
        expect($this->renderViewComponent($action()))->toContainButtons(Selectors::TRIGGER . $look, ['Save']);
    })->with([
        'a button by default' => [fn (): Action => Action::make('save'), SupportSelectors::BUTTON],
        'a badge' => [fn (): Action => Action::make('save')->badge(), SupportSelectors::BADGE],
        'a dropdown item' => [fn (): Action => Action::make('save')->grouped(), '.sy-dropdown-item'],
        'an icon button' => [fn (): Action => Action::make('save')->iconButton(), SupportSelectors::ICON_BUTTON],
        'a link' => [fn (): Action => Action::make('save')->link(), SupportSelectors::LINK],
    ]);

    it('keeps the look it was given inside a group, which turns its other actions into dropdown items', function (): void {
        $render = $this->renderViewComponent(ActionGroup::make([Action::make('save')->link(), Action::make('drop')]));

        expect($render)->toContainElement(Selectors::TRIGGER . SupportSelectors::LINK, 'Save')
            ->toContainElement(Selectors::TRIGGER . '.sy-dropdown-item', 'Drop');
    });

    it('renders an uncoloured action in primary', function (Closure $action, string $look): void {
        expect($this->renderViewComponent($action()))->toContainButtons($look . SupportSelectors::COLORED . SupportSelectors::color('primary'), ['Save']);
    })->with([
        'a badge' => [fn (): Action => Action::make('save')->badge(), SupportSelectors::BADGE],
        'a button' => [fn (): Action => Action::make('save'), SupportSelectors::BUTTON],
        'an icon button' => [fn (): Action => Action::make('save')->iconButton(), SupportSelectors::ICON_BUTTON],
        'a link' => [fn (): Action => Action::make('save')->link(), SupportSelectors::LINK],
    ]);

    it('renders an uncoloured dropdown item gray', function (): void {
        expect($this->renderViewComponent(Action::make('save')->grouped()))->toContainElement('.sy-dropdown-item' . SupportSelectors::GRAY, 'Save')
            ->and($this->renderViewComponent(Action::make('save')->grouped()->color('danger')))->toContainElement('.sy-dropdown-item' . SupportSelectors::color('danger'), 'Save');
    });

    it("titles its trigger with its tooltip, over an icon button's own label", function (): void {
        expect($this->renderViewComponent(Action::make('save')->tooltip('Keeps a copy')))
            ->toContainElement(SupportSelectors::BUTTON . '[title="Keeps a copy"]', 'Save')
            ->and($this->renderViewComponent(Action::make('save')->iconButton()))
            ->toContainElement(SupportSelectors::ICON_BUTTON . '[aria-label="Save"][title="Save"]')
            ->and($this->renderViewComponent(Action::make('save')->iconButton()->tooltip('Keeps a copy')))
            ->toContainElement(SupportSelectors::ICON_BUTTON . '[aria-label="Save"][title="Keeps a copy"]');
    });

    it('links to its url, in a new tab when told, with no click binding of its own', function (): void {
        $link = $this->renderViewComponent(Action::make('docs')->url('/docs')->action('save'));

        expect($link)->toContainElement('a[href="/docs"]:not([target])' . Selectors::TRIGGER, 'Docs')
            ->and($link->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
            ->and($this->renderViewComponent(Action::make('docs')->url('/docs', shouldOpenInNewTab: true)))
            ->toContainElement('a[href="/docs"][target="_blank"]', 'Docs')
            ->and($this->renderViewComponent(Action::make('docs')->action('save')))
            ->toContainElement('button' . Selectors::LIVE_CLICK . Selectors::liveParameter('action', 'save'), 'Docs');
    });

    it("renders its extra attributes on an icon button, its classes beside the trigger's own", function (): void {
        expect($this->renderViewComponent(Action::make('save')->iconButton()->extraAttributes(['class' => 'custom-hook', 'data-x' => '1'])))
            ->toContainElement(SupportSelectors::ICON_BUTTON . Selectors::TRIGGER . '.custom-hook[data-x="1"][aria-label="Save"]');
    });

    it("dispatches its event on click even inside a parent's modal", function (Closure $action, string $look): void {
        $render = $this->renderViewComponent($action());

        expect($render)->toContainElement($look . Selectors::dispatchedEvent('saved'))
            ->and($render->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
            ->and($this->renderViewComponent($action()->dispatch(null)))
            ->toContainElement($look . Selectors::LIVE_CLICK . Selectors::liveParameter('action', 'callMountedAction'));
    })->with([
        'a button' => [fn (): Action => Action::make('notify')->dispatch('saved')->callParent('callMountedAction'), SupportSelectors::BUTTON],
        'an icon button' => [fn (): Action => Action::make('notify')->iconButton()->dispatch('saved')->callParent('callMountedAction'), SupportSelectors::ICON_BUTTON],
    ]);

    it('submits a form with no click binding of its own, keeping its dismiss when it closes', function (): void {
        $submit = $this->renderViewComponent(Action::make('submit')->submit('callMountedAction'));
        $closingSubmit = $this->renderViewComponent(Action::make('submit')->submit('callMountedAction')->close());

        expect($submit)->toContainElement('button[type="submit"]', 'Submit')
            ->and($submit->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
            ->and($closingSubmit)->toContainElement('button[type="submit"]' . Selectors::DISMISS, 'Submit')
            ->and($closingSubmit->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
            ->and($this->renderViewComponent(Action::make('submit')))->toContainElement('button[type="button"]' . Selectors::LIVE_CLICK, 'Submit');
    });

    it('dismisses its modal from a closing icon button, unless it links away or is disabled', function (): void {
        expect($this->renderViewComponent(Action::make('cancel')->iconButton()->close()))
            ->toContainElement(SupportSelectors::ICON_BUTTON . Selectors::DISMISS)
            ->and($this->renderViewComponent(Action::make('cancel')->iconButton()->close()->url('/back'))->filter(Selectors::DISMISS))->toHaveCount(0)
            ->and($this->renderViewComponent(Action::make('cancel')->iconButton()->close()->url('/back')))->toContainElement('a[href="/back"]' . SupportSelectors::ICON_BUTTON)
            ->and($this->renderViewComponent(Action::make('cancel')->iconButton()->close()->disabled())->filter(Selectors::DISMISS))->toHaveCount(0)
            ->and($this->renderViewComponent(Action::make('cancel')->iconButton()->close()->disabled()))->toContainElement('button[disabled]' . SupportSelectors::ICON_BUTTON);
    });
});

describe('serialization', function (): void {
    it('restores the trigger it serialized, without the behaviour behind it', function (): void {
        $payload = Action::make('notify')
            ->label('Notify')
            ->badge()
            ->color('danger')
            ->size(Size::Small)
            ->icon('support:star')
            ->iconPosition(IconPosition::After)
            ->tooltip('Tells the team')
            ->url('/inbox', shouldOpenInNewTab: true)
            ->extraAttributes(['data-x' => '1'])
            ->markAsRead()
            ->action(static fn (): string => 'ran')
            ->toArray();

        $restored = Action::fromArray($payload);

        expect($restored->toArray())->toBe($payload)
            ->and($restored->call())->toBeNull()
            ->and($this->renderViewComponent($restored))->toContainElement(
                'a[href="/inbox"][target="_blank"][title="Tells the team"][data-x="1"]' . SupportSelectors::BADGE . SupportSelectors::color('danger') . SupportSelectors::size('sm'),
                'NotifyStar',
            );
    });

    it('writes its icon position and sizes as scalars, and keeps a scalable icon to resolve at the size it renders', function (): void {
        $payload = Action::make('star')->icon(SizedIcon::Star)->iconSize(IconSize::Large)->iconPosition(IconPosition::After)->size(Size::Small)->toArray();

        expect($payload)->toMatchArray(['icon' => SizedIcon::Star, 'iconPosition' => 'after', 'iconSize' => 'lg', 'size' => 'sm'])
            ->and($this->renderViewComponent(Action::fromArray($payload)))
            ->toContainElement(SupportSelectors::BUTTON . SupportSelectors::size('sm'), 'StarStar, large');
    });

    it('restores only its own templates, a button for any other', function (): void {
        expect($this->renderViewComponent(Action::fromArray(['name' => 'save', 'template' => '@Evil/anything.html.twig'])))
            ->toContainElement(SupportSelectors::BUTTON, 'Save')
            ->and($this->renderViewComponent(Action::fromArray(['name' => 'save', 'template' => Action::GROUPED_TEMPLATE])))
            ->toContainElement('.sy-dropdown-item', 'Save');
    });

    it('dispatches a restored event to the component it names, and plainly when it names none', function (): void {
        $untargeted = $this->renderViewComponent(Action::fromArray(['name' => 'notify', 'event' => 'saved', 'dispatchDirection' => 'to']));

        expect($untargeted)->toContainElement(Selectors::dispatchedEvent('saved'), 'Notify')
            ->and($untargeted->filter('[data-sylament-action-dispatch-to]'))->toHaveCount(0)
            ->and($this->renderViewComponent(Action::fromArray(['name' => 'notify', 'event' => 'saved', 'dispatchDirection' => 'to', 'dispatchToComponent' => 'Inbox'])))
            ->toContainElement(Selectors::dispatchedEvent('saved') . '[data-sylament-action-dispatch-to="Inbox"]', 'Notify');
    });
});

describe('call()', function (): void {
    it('runs its action with the parameters it is called with and answers what it returns', function (): void {
        expect(Action::make('greet')->action(static fn (string $tone): string => "said {$tone}")->call(['tone' => 'loudly']))->toBe('said loudly');
    });
});

describe('the mounted modal', function (): void {
    it('submits its form to the call of the mounted action', function (): void {
        $modal = $this->createLiveComponent(MountedActionsHost::class)->mountAction('rename')->render()->crawler();

        expect($modal)->toContainElement('form' . SupportSelectors::MODAL_WINDOW . Selectors::liveParameter('action', 'callMountedAction'))
            ->toContainElement(SupportSelectors::MODAL_WINDOW . ' button[type="submit"]', 'Submit');
    });
});
