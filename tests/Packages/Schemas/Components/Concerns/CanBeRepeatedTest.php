<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Schemas\RepeaterForm;

it('answers the repeater and the position of the item a field repeats in, through a layout component, and none outside one', function (): void {
    expect($this->createLiveComponent(RepeaterForm::class)->render()->crawler())
        ->toContainElement('label', 'Member 1')
        ->toContainElement('label', 'Member 2')
        ->toContainElement('label', 'Not repeated');
});
