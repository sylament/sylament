<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Twig\Extension;

use RuntimeException;
use stdClass;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RenderActionHost;
use Sylament\Actions\Twig\Extension\ActionExtension;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Twig\Environment;
use Twig\Markup;

uses(InteractsWithLiveComponents::class);

/*
 * Covers `renderAction('name')`, the host-template shorthand mirroring schemas'
 * `renderSchema()`: it resolves the action through the surrounding live
 * component's `getAction()` (binding the host), so templates don't have to call
 * the factory method themselves.
 */

function actionExtension(): ActionExtension
{
    $extension = test()->getContainer()->get('twig')->getExtension(ActionExtension::class);

    assert($extension instanceof ActionExtension);

    return $extension;
}

it('registers the renderAction twig function', function (): void {
    $twig = $this::getContainer()->get('twig');

    expect($twig)->toBeInstanceOf(Environment::class)
        ->and($twig->getFunction('renderAction'))->not->toBeFalse();
});

it('renders the named action resolved through the surrounding host', function (): void {
    $html = (string) $this->createLiveComponent('RenderActionHost')->render();

    expect($html)->toContain('sy sy-btn sy-size-md sy-color sy-color-primary sy-bg-color-400 hover:sy-bg-color-300 dark:sy-bg-color-600 dark:hover:sy-bg-color-500 sy-text-color-900 hover:sy-text-color-800 dark:sy-text-color-950 dark:hover:sy-text-color-950 sy-ac')
        ->toContain('data-live-action-param="mountAction"')
        ->toContain('data-live-name-param="delete"')
        ->toContain('Delete');
});

it('renders nothing for a name the host cannot resolve', function (): void {
    $html = (string) $this->createLiveComponent('RenderActionHost')->render();

    expect($html)->toContain('<span data-test="missing"></span>');
});

it('renders nothing for a hidden action', function (): void {
    $html = (string) $this->createLiveComponent('RenderActionHost')->render();

    expect($html)->toContain('<span data-test="hidden"></span>')
        ->not->toContain('data-live-name-param="hidden"');
});

it('renders serialized arguments when the template passes them', function (): void {
    $html = (string) $this->createLiveComponent('RenderActionHost')->render();

    expect($html)->toContain('data-live-arguments-param="{&quot;id&quot;:5}"');
});

it('binds arguments onto a clone, leaving the cached action argument-free', function (): void {
    $twig = $this::getContainer()->get('twig');
    $host = new RenderActionHost();

    $html = (string) actionExtension()->renderAction($twig, ['this' => $host], 'delete', ['id' => 5]);

    expect($html)->toContain('data-live-arguments-param="{&quot;id&quot;:5}"')
        ->and($host->getAction('delete')?->getArguments())->toBe([])
        ->and($host->getAction('delete')?->getInvokedArguments())->toBeNull();
});

it('throws when the context has no rendering component', function (): void {
    $twig = $this::getContainer()->get('twig');

    expect(fn (): ?Markup => actionExtension()->renderAction($twig, [], 'delete'))
        ->toThrow(RuntimeException::class);
});

it('throws when the rendering component is not an actions host', function (): void {
    $twig = $this::getContainer()->get('twig');

    expect(fn (): ?Markup => actionExtension()->renderAction($twig, ['this' => new stdClass()], 'delete'))
        ->toThrow(RuntimeException::class);
});
