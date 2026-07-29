<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Twig\Components;

use LogicException;
use stdClass;
use Sylament\Actions\Tests\Fixtures\Twig\Components\ModalsHostFixture;
use Sylament\Actions\Twig\Components\Modals;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\MountedComponent;
use Twig\Runtime\EscaperRuntime;

/*
 * Host resolution is the one place the port leans on ux-twig-component's `@internal`
 * ComponentStack, so it is pinned here against a hand-built stack rather than only
 * through a rendered template.
 */

function pushComponent(ComponentStack $stack, object $component): void
{
    $stack->push(new MountedComponent(
        $component::class,
        $component,
        new ComponentAttributes([], new EscaperRuntime()),
    ));
}

it('resolves the action host from the component stack', function (): void {
    $stack = new ComponentStack();
    $host = new ModalsHostFixture();
    $modals = new Modals($stack);

    pushComponent($stack, $host);
    pushComponent($stack, $modals);

    expect($modals->getLiveComponent())->toBe($host);
});

it('skips stack frames that are not action hosts', function (): void {
    $stack = new ComponentStack();
    $host = new ModalsHostFixture();
    $modals = new Modals($stack);

    pushComponent($stack, $host);
    pushComponent($stack, new stdClass());
    pushComponent($stack, $modals);

    expect($modals->getLiveComponent())->toBe($host);
});

it('resolves the innermost host when hosts are nested', function (): void {
    $stack = new ComponentStack();
    $outer = new ModalsHostFixture();
    $inner = new ModalsHostFixture();
    $modals = new Modals($stack);

    pushComponent($stack, $outer);
    pushComponent($stack, $inner);
    pushComponent($stack, $modals);

    expect($modals->getLiveComponent())->toBe($inner);
});

it('prefers an explicitly mounted host over the stack', function (): void {
    $stack = new ComponentStack();
    $stacked = new ModalsHostFixture();
    $explicit = new ModalsHostFixture();
    $modals = new Modals($stack);

    pushComponent($stack, $stacked);
    pushComponent($stack, $modals);

    $modals->mount($explicit);

    expect($modals->getLiveComponent())->toBe($explicit);
});

it('throws when neither an explicit host nor a stacked one resolves', function (): void {
    $stack = new ComponentStack();
    $modals = new Modals($stack);

    pushComponent($stack, $modals);

    expect(fn (): object => $modals->getLiveComponent())
        ->toThrow(LogicException::class, 'found no action host');
});
