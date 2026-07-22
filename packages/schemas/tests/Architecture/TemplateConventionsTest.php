<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function dirname;

/**
 * Architectural convention test for the project's Twig templates.
 *
 * Pest's `arch()` API only inspects PHP classes/namespaces, so it cannot express a
 * Twig-template-content rule — this guards the convention with an expectation instead,
 * but lives in the Architecture suite because it is a project-wide convention, not a
 * test of any single class (hence it does not mirror src/).
 *
 * The rule: templates render attribute sets through attrs() (backed by
 * ComponentAttributeBag, auto-registered by the support bundle) and build class
 * token lists with classes() where a foreign attribute bag renders them
 * (ux-twig-component's attributes.defaults()). The twig/html-extra helpers are
 * banned: html_attr()/html_classes() would reintroduce a second attribute
 * implementation and an extension no consumer app is guaranteed to register.
 */

/**
 * @return list<string> template paths relative to the packages/ dir, across all bundles
 */
function twigTemplates(): array
{
    $packagesDirectory = dirname(__DIR__, 3);

    $files = [];

    foreach (glob($packagesDirectory . '/*/templates') ?: [] as $templatesDirectory) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($templatesDirectory, FilesystemIterator::SKIP_DOTS),
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

it('renders attributes through attrs() and classes() instead of the html-extra helpers', function (string $template): void {
    $content = (string) file_get_contents(dirname(__DIR__, 3) . '/' . $template);

    expect(str_contains($content, 'html_attr'))->toBeFalse(
        "Use attrs() (ComponentAttributeBag-backed) instead of html_attr() in {$template}",
    )->and(str_contains($content, 'html_classes'))->toBeFalse(
        "Use classes() (token list) instead of html_classes() in {$template}",
    );
})->with(twigTemplates());
