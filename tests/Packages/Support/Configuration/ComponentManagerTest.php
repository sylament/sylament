<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Html;
use Sylament\Support\Components\ViewComponent;
use Sylament\Support\Configuration\ComponentManager;

it('applies a during: registration to that call alone and returns its result, removing it even when the call throws', function (): void {
    $configure = fn (Html $html): Html => $html->content('<p>Configured</p>');

    $made = Html::configureUsing($configure, during: fn (): Html => Html::make('<p>Made</p>'));

    expect(fn (): mixed => Html::configureUsing($configure, during: fn (): never => throw new RuntimeException('The call failed.')))
        ->toThrow(RuntimeException::class, 'The call failed.')
        ->and($this->renderViewComponent($made))->toContainElement('p', 'Configured')
        ->and($this->renderViewComponent(Html::make('<p>Made</p>')))->toContainElement('p', 'Made');
});

it('configures root class first, setUp() at the class declaring it and each class after its closures, important closures last', function (): void {
    $append = fn (string $step): Closure => fn (Html $html): Html => $html->content("{$html->getContent()} {$step}");
    $subclass = new class extends Html {
        protected function setUp(): void
        {
            parent::setUp();

            $this->content("{$this->getContent()} setUp");
        }
    };

    ViewComponent::configureUsing($append('important'), isImportant: true);
    Html::configureUsing($append('html'));
    Component::configureUsing($append('schema-component'));
    ViewComponent::configureUsing($append('view-component'));
    $subclass::configureUsing($append('subclass'));

    expect($this->renderViewComponent($subclass::make('made'))->text())->toBe('made view-component schema-component html setUp subclass important');
});

it('resolves the booted manager, and creates one when none is booted', function (): void {
    $booted = $this::getContainer()->get(ComponentManager::class);

    expect(ComponentManager::resolve())->toBe($booted);

    ComponentManager::unsetInstance();
    $created = ComponentManager::resolve();

    expect($created)->not->toBe($booted)
        ->and(ComponentManager::resolve())->toBe($created);
});
