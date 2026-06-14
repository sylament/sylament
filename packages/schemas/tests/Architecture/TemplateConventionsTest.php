<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function dirname;

/**
 * Architectural convention test for the bundle's Twig templates.
 *
 * Pest's `arch()` API only inspects PHP classes/namespaces, so it cannot express a
 * Twig-template-content rule — this guards the convention with an expectation instead,
 * but lives in the Architecture suite because it is a project-wide convention, not a
 * test of any single class (hence it does not mirror src/).
 *
 * The rule: `html_attr()` merges the `class` of its first argument with the `class` of
 * any defaults passed as a second argument (e.g. `grid(...)`, which yields a `class`
 * array). For that merge to combine the class lists rather than clobber them, the
 * `class` value must be an array — but `html_classes()` returns a space-joined string.
 * So any `html_classes()` feeding an `html_attr()` must be piped straight into
 * `|split(' ')`. Splitting is harmless when there is no second argument, so the rule is
 * uniform and future-proofs a template against later gaining a merge source.
 */

/**
 * @return list<string> template paths relative to the bundle's templates/ dir
 */
function twigTemplates(): array
{
    $directory = dirname(__DIR__, 2) . '/templates';
    if (!is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with((string) $file->getFilename(), '.twig')) {
            $files[] = str_replace($directory . '/', '', $file->getPathname());
        }
    }

    sort($files);

    return $files;
}

it('pipes every html_classes() feeding html_attr() into split()', function (string $template): void {
    $content = (string) file_get_contents(dirname(__DIR__, 2) . '/templates/' . $template);

    // Isolate the balanced argument span of every html_attr(...) call.
    preg_match_all('/html_attr\s*(?<call>\((?:[^()]++|(?&call))*+\))/', $content, $htmlAttrCalls);
    $insideHtmlAttr = implode("\n", $htmlAttrCalls['call']);

    $htmlClassesCalls = preg_match_all('/html_classes\s*(?<args>\((?:[^()]++|(?&args))*+\))/', $insideHtmlAttr);
    $splitCalls = preg_match_all('/html_classes\s*(?<args>\((?:[^()]++|(?&args))*+\))\s*\|\s*split\(/', $insideHtmlAttr);

    expect($splitCalls)->toBe(
        $htmlClassesCalls,
        "html_classes() inside html_attr() must be followed by |split(' ') in {$template}",
    );
})->with(twigTemplates());
