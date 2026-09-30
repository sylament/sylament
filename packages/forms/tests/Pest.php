<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Sylament\Forms\Components\Field;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Sylament\Support\Components\ViewComponent;
use Sylament\Support\Configuration\ComponentManager;
use Sylament\Support\DependencyInjection\ComponentResolver;
use Symfony\Component\DomCrawler\Crawler;

pest()
    ->extend(TestCase::class)
    ->afterEach(function (): void {
        removeDirectory(uploadStorageDir());
    })
    ->in('Integration');

pest()
    ->afterEach(function (): void {
        ComponentManager::unsetInstance();
        ComponentResolver::unsetInstance();
    })
    ->in('Unit');

/**
 * Render a real component through the booted kernel's Twig and return the HTML.
 * A single render exercises the fluent API, state resolution, closure evaluation
 * through the real Invoker, the Twig templates, and the registered extensions at once.
 */
function renderToHtml(ViewComponent $component): string
{
    return $component->render(test()->getContainer()->get('twig')) ?? '';
}

/**
 * Render a form field as the child of a real Schema bound to a `data`-bag host,
 * mirroring how a field is used: the parent Schema is its container, supplying the
 * id/state-path cascade and the live binding context. `->statePath('data')` prefixes
 * the field's absolute path (so `title` becomes `data.title`), and the host's empty
 * `$data` bag lets `getState()` read back null — an unset field renders an empty value.
 */
function renderField(Field $field): string
{
    return renderToHtml(Schema::make(new FieldRenderHost())->statePath('data')->components([$field]));
}

/**
 * Render a repeater as the child of a real Schema over a seeded `data` bag. The
 * items are seeded rather than hydrated, so they stay positionally keyed and the
 * ids the template composes are readable in an assertion; a real mount keys them
 * by uuid, which is why nothing asserts a literal key off a mounted repeater.
 *
 * @param array<string, mixed> $data
 */
function renderRepeater(Repeater $repeater, array $data = []): Crawler
{
    $host = new FieldRenderHost();
    $host->data = $data;

    return new Crawler(renderToHtml(Schema::make($host)->statePath('data')->components([$repeater])));
}

/**
 * Wire a field into a real parent Schema so its child slot schemas can reach the
 * live component through the container chain, as happens in a real render. Returns
 * the field for chaining slot assertions (mirrors schemas' `wireSection()`).
 */
function wireField(Field $field): Field
{
    Schema::make(new FieldRenderHost())->components([$field])->getComponents();

    return $field;
}

/**
 * Where a fixture storage keeps its bytes. The Kernel configures both `local` storages under its
 * cache directory, and `getProjectDir()` resolves that to `packages/forms` — so this is the same
 * path without booting a kernel, which is what lets a test read what flysystem wrote and what the
 * `afterEach` above takes back. Nothing rolls a test's disk writes back on its own. The
 * environment is read in `KernelTestCase`'s own order, `$_ENV` before `$_SERVER`, because a
 * private environment set through phpunit.xml's `<env force>` lands in `$_ENV` alone and the
 * kernel would then compile under a cache directory this function did not name.
 */
function uploadStorageDir(string $storage = ''): string
{
    return mb_rtrim(__DIR__ . '/../var/cache/' . ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test') . '/storage/' . $storage, '/');
}

function removeDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $entries = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    /** @var SplFileInfo $entry */
    foreach ($entries as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($directory);
}
