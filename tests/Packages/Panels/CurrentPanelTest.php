<?php

declare(strict_types=1);

use Sylament\Panels\CurrentPanel;
use Symfony\Component\HttpFoundation\Request;

it("answers the panel the current request's _sylament_panel names, and none for a request without it", function (): void {
    $current = $this::getContainer()->get(CurrentPanel::class);
    $requests = $this::getContainer()->get('request_stack');

    $outside = $current->get();
    $requests->push(new Request(attributes: ['_sylament_panel' => 'admin']));
    $inside = $current->get();
    $requests->push(new Request());

    expect($outside)->toBeNull()
        ->and($inside?->getId())->toBe('admin')
        ->and($current->get())->toBeNull();
});

it('refuses a request naming a panel no provider registers', function (): void {
    $this::getContainer()->get('request_stack')->push(new Request(attributes: ['_sylament_panel' => 'missing']));

    expect(fn (): mixed => $this::getContainer()->get(CurrentPanel::class)->get())
        ->toThrow(LogicException::class, 'The request carries panel "missing" but no such panel is registered.');
});
