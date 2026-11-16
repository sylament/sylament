<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Schemas\ReaderForm;

it('reads the state of the field a path leads to, its own, one up from its container or an absolute one, flagged or written from the root, through that field', function (): void {
    expect($this->createLiveComponent(ReaderForm::class)->render()->crawler())
        ->toContainElement('label', 'Own true')
        ->toContainElement('label', 'Up true, absolute true, from the root true');
});

it('reads a path no field owns from the host', function (): void {
    expect($this->createLiveComponent(ReaderForm::class)->render()->crawler())->toContainElement('label', "Bag 'Kept'");
});

it('reads a path inside its own component from the host, unless told to search that component too', function (): void {
    expect($this->createLiveComponent(ReaderForm::class)->render()->crawler())
        ->toContainElement('legend', 'Skipped 1')
        ->toContainElement('legend', 'Searched true');
});
