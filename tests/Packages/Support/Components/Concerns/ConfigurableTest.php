<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Html;

it('configures every make() of the class and its subclasses, until the closure it returns removes the registration', function (): void {
    $subclass = new class extends Html {};
    $remove = Html::configureUsing(fn (Html $html): Html => $html->content('<p>Configured</p>'));
    $removeImportant = Html::configureUsing(fn (Html $html): Html => $html->content("{$html->getContent()}<p>Important</p>"), isImportant: true);

    $configured = $this->renderViewComponent(Html::make('<p>Made</p>'));
    $configuredSubclass = $this->renderViewComponent($subclass::make('<p>Made</p>'));
    $remove();
    $removeImportant();

    expect($configured)->toContainElement('p', 'Configured')->toContainElement('p', 'Important')
        ->and($configuredSubclass)->toContainElement('p', 'Configured')
        ->and($this->renderViewComponent(Html::make('<p>Made</p>')))->toContainElement('p', 'Made')->not->toContainElement('p', 'Important');
});
