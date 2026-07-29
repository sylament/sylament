<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function dirname;

/**
 * Architectural convention test for where a bundle's Twig templates live.
 *
 * Pest's `arch()` API only inspects PHP classes/namespaces, so it cannot express a
 * filesystem-layout rule — this guards the convention with an expectation instead,
 * but lives in the Architecture suite because it is a project-wide convention, not a
 * test of any single class (hence it does not mirror src/).
 *
 * The rule: a bundle's `templates/` has exactly two halves, and both group per component.
 * `templates/components/<Name>/index.html.twig` is a template invoked as a
 * `<twig:Sylament<Pkg>:<Name> />` tag — anonymous or class-backed, it makes no difference
 * to the layout. `templates/partials/<Name>/<variant>.html.twig` is rendered from PHP: a
 * `ViewComponent::$template`, an `{% include %}` target.
 */

/**
 * @return list<string> paths under the named half of every bundle's templates/, relative to the packages/ dir
 */
function bundleTemplatesIn(string $half): array
{
    $packagesDirectory = dirname(__DIR__, 3);

    $files = [];

    foreach (glob($packagesDirectory . '/*/templates/' . $half) ?: [] as $directory) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with((string) $file->getFilename(), '.twig')) {
                $files[] = str_replace($packagesDirectory . '/', '', $file->getPathname());
            }
        }
    }

    sort($files);

    return $files;
}

it('keeps templates/components/ to tag-invoked components', function (string $template): void {
    expect($template)->toMatch(
        '#^[^/]+/templates/components/[^/]+/index\.html\.twig$#',
        "{$template} is not shaped as components/<Name>/index.html.twig. Everything under "
        . 'templates/components/ is autodiscovered by ux-twig-component (the components/ segment is '
        . 'hardcoded in ComponentTemplateFinder::findAnonymousComponentTemplate()), so it is callable '
        . 'as a <twig:Sylament<Pkg>:<Name> /> tag whether or not it was written to be — which only '
        . 'works for a template that declares its own props. A template rendered from PHP (a '
        . 'ViewComponent::$template, an {% include %} target) depends on a context no tag invocation '
        . 'supplies and belongs in templates/partials/<Name>/.',
    );
})->with(bundleTemplatesIn('components'));

it('groups templates/partials/ per component', function (string $template): void {
    expect($template)->toMatch(
        '#^[^/]+/templates/partials/[^/]+/[^/]+\.html\.twig$#',
        "{$template} does not sit in a per-component directory. templates/partials/ holds only "
        . 'PHP-rendered templates, grouped one directory per component (partials/<Name>/index.html.twig '
        . 'for the default, partials/<Name>/<Variant>.html.twig for the rest), so a component can grow '
        . 'variants without restructuring and no file is left flat among the directories.',
    );
})->with(bundleTemplatesIn('partials'));
