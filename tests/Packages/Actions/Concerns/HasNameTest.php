<?php

declare(strict_types=1);

use Sylament\Actions\Action;

it('refuses to answer a name it was not given', function (): void {
    expect(fn (): string => Action::make()->getName())
        ->toThrow(LogicException::class, 'Action of class [Sylament\Actions\Action] must have a unique name, passed to the [make()] method.');
});
