<?php

declare(strict_types=1);

use Sylament\Panels\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Tests\Fixtures\Resources\UntitledResource;

/*
 * The setter cases build a bare `Panel::make()` — container-built inside a
 * booted kernel, so `evaluate()` reaches the real Invoker and the `#[Required]`
 * `ResourceRegistry` setter fires — while the two `isGlobalSearchEnabled()`
 * cases that need a *searchable* resource want the fixture panel's registered
 * ones, so they go through `PanelRegistry`.
 */

function globalSearchPanel(): Panel
{
    test()->getContainer();

    return Panel::make();
}

function registeredGlobalSearchPanel(): Panel
{
    $registry = test()->getContainer()->get(PanelRegistry::class);

    assert($registry instanceof PanelRegistry);

    $panel = $registry->get('admin');

    assert($panel instanceof Panel);

    return $panel;
}

it('defaults to the default provider, a 500ms debounce, no key bindings and no suffix', function (): void {
    $panel = globalSearchPanel();

    expect($panel->getGlobalSearchProviderClass())->toBe(DefaultGlobalSearchProvider::class)
        ->and($panel->getGlobalSearchDebounce())->toBe('500ms')
        ->and($panel->getGlobalSearchDebounceMilliseconds())->toBe(500)
        ->and($panel->getGlobalSearchKeyBindings())->toBe([])
        ->and($panel->getGlobalSearchFieldSuffix())->toBeNull();
});

it('resolves the provider class from the globalSearch() argument', function (): void {
    expect(globalSearchPanel()->globalSearch(false)->getGlobalSearchProviderClass())->toBeNull()
        ->and(globalSearchPanel()->globalSearch()->getGlobalSearchProviderClass())->toBe(DefaultGlobalSearchProvider::class)
        ->and(globalSearchPanel()->globalSearch(DefaultGlobalSearchProvider::class)->getGlobalSearchProviderClass())
            ->toBe(DefaultGlobalSearchProvider::class);
});

it('rejects a provider class that does not implement the contract', function (): void {
    globalSearchPanel()->globalSearch(stdClass::class);
})->throws(InvalidArgumentException::class, stdClass::class);

it('translates the debounce string into milliseconds', function (string $debounce, int $milliseconds): void {
    $panel = globalSearchPanel()->globalSearchDebounce($debounce);

    expect($panel->getGlobalSearchDebounce())->toBe($debounce)
        ->and($panel->getGlobalSearchDebounceMilliseconds())->toBe($milliseconds);
})->with([
    ['750ms', 750],
    ['2s', 2000],
    ['0.5s', 500],
    ['300', 300],
    ['oops', 500],
]);

it('evaluates a closure debounce', function (): void {
    $panel = globalSearchPanel()->globalSearchDebounce(fn (): string => '1s');

    expect($panel->getGlobalSearchDebounce())->toBe('1s')
        ->and($panel->getGlobalSearchDebounceMilliseconds())->toBe(1000);
});

it('keeps the key bindings verbatim and unevaluated', function (): void {
    expect(globalSearchPanel()->globalSearchKeyBindings(['mod+k', 'ctrl+k'])->getGlobalSearchKeyBindings())
        ->toBe(['mod+k', 'ctrl+k']);
});

it('evaluates the field suffix', function (): void {
    expect(globalSearchPanel()->globalSearchFieldSuffix('⌘K')->getGlobalSearchFieldSuffix())->toBe('⌘K')
        ->and(globalSearchPanel()->globalSearchFieldSuffix(fn (): string => '⌃K')->getGlobalSearchFieldSuffix())->toBe('⌃K');
});

it('is enabled when a registered resource is globally searchable', function (): void {
    expect(registeredGlobalSearchPanel()->isGlobalSearchEnabled())->toBeTrue();
});

it('is disabled when the provider is switched off', function (): void {
    expect(registeredGlobalSearchPanel()->globalSearch(false)->isGlobalSearchEnabled())->toBeFalse();
});

it('is disabled when no registered resource is globally searchable', function (): void {
    expect(globalSearchPanel()->resources([UntitledResource::class])->isGlobalSearchEnabled())->toBeFalse();
});

it('is disabled when the panel registers no resources at all', function (): void {
    expect(globalSearchPanel()->isGlobalSearchEnabled())->toBeFalse();
});
