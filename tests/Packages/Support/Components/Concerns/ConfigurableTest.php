<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Html;

it('configures every make() of the class and its subclasses, until the closure it returns removes the registration', function (): void {
    $subclass = new class extends Html {};
    $remove = Html::configureUsing(fn (Html $html): Html => $html->content('<p>Configured</p>'));

    $configured = $this->renderViewComponent(Html::make('<p>Made</p>'));
    $configuredSubclass = $this->renderViewComponent($subclass::make('<p>Made</p>'));
    $remove();

    expect($configured)->toContainElement('p', 'Configured')
        ->and($configuredSubclass)->toContainElement('p', 'Configured')
        ->and($this->renderViewComponent(Html::make('<p>Made</p>')))->toContainElement('p', 'Made');
});
