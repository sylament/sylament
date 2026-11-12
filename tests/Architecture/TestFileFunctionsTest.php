<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use FilesystemIterator;
use PhpToken;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Sylament\Tests\Infrastructure\WorkerDirectory;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;

/**
 * Convention test for the root suite: a function a *Test.php file declares is called only from that file.
 * Paratest gives each worker a share of the test files, so a call from another file reaches a function its
 * worker may never have loaded, and the suite fails only under --parallel.
 */

/**
 * @param list<PhpToken> $tokens a file's tokens without whitespace and comments
 *
 * @return list<string> the functions the file declares outside any class body
 */
function testFileFunctionDeclarations(array $tokens): array
{
    $names = [];
    $depth = 0;
    $classDepths = [];
    $classPending = false;

    foreach ($tokens as $index => $token) {
        $previous = $tokens[$index - 1] ?? null;

        if ($token->is([T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM]) && !$previous?->is(T_DOUBLE_COLON)) {
            $classPending = true;
        } elseif ($token->is(['{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
            $depth++;

            if ($classPending) {
                $classDepths[] = $depth;
                $classPending = false;
            }
        } elseif ($token->is('}')) {
            if ($depth === array_last($classDepths)) {
                array_pop($classDepths);
            }

            $depth--;
        } elseif ($token->is(T_FUNCTION) && [] === $classDepths && !$previous?->is(T_USE)) {
            $name = $tokens[$index + 1] ?? null;
            $name = true === $name?->is('&') ? $tokens[$index + 2] ?? null : $name;

            if (true === $name?->is(T_STRING)) {
                $names[] = $name->text;
            }
        }
    }

    return $names;
}

/**
 * @return list<string> each call or `use function` import, in any PHP file under the directory, of a function
 *                      that a different *Test.php file declares, paths relative to the directory
 */
function testFileFunctionCalls(string $directory): array
{
    $files = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile() && str_ends_with((string) $file->getFilename(), '.php')) {
            $tokens = PhpToken::tokenize((string) file_get_contents($file->getPathname()));
            $files[str_replace($directory . '/', '', $file->getPathname())] = array_values(array_filter($tokens, static fn (PhpToken $token): bool => !$token->isIgnorable()));
        }
    }

    ksort($files);
    $declarers = [];

    foreach ($files as $file => $tokens) {
        if (str_ends_with($file, 'Test.php')) {
            foreach (testFileFunctionDeclarations($tokens) as $name) {
                $declarers[mb_strtolower($name)][] = $file;
            }
        }
    }

    $calls = [];

    foreach ($files as $file => $tokens) {
        foreach ($tokens as $index => $token) {
            if (!$token->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE])) {
                continue;
            }

            $declaredIn = $declarers[mb_strtolower(array_last(explode('\\', $token->text)))] ?? [];

            if ([] === $declaredIn || in_array($file, $declaredIn, true)) {
                continue;
            }

            $previous = $tokens[$index - 1] ?? null;
            $imported = true === $previous?->is(T_FUNCTION) && true === ($tokens[$index - 2] ?? null)?->is(T_USE);
            $called = true === ($tokens[$index + 1] ?? null)?->is('(')
                && true !== $previous?->is([T_FUNCTION, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW]);

            if ($imported || $called) {
                $calls[] = "{$file}:{$token->line} reaches {$token->text}(), declared in " . implode(', ', $declaredIn);
            }
        }
    }

    return $calls;
}

it("finds a call into another test file's function", function (): void {
    $directory = WorkerDirectory::path() . '/' . uniqid('test-file-functions-', true);
    mkdir($directory . '/Area', recursive: true);
    file_put_contents($directory . '/Area/HelperTest.php', "<?php\n\nfunction plantedHelper(): int\n{\n    return 1;\n}\n\nit('uses its own helper', fn () => expect(plantedHelper())->toBe(1));\n");
    file_put_contents($directory . '/Area/CallerTest.php', "<?php\n\nit('borrows a helper', fn () => expect(plantedHelper())->toBe(1));\n");

    try {
        expect(testFileFunctionCalls($directory))->toBe(['Area/CallerTest.php:3 reaches plantedHelper(), declared in Area/HelperTest.php']);
    } finally {
        new Filesystem()->remove($directory);
    }
});

it("calls each test file's functions only from that file", function (): void {
    $calls = testFileFunctionCalls(dirname(__DIR__));

    expect($calls)->toBe([], "A function declared in a test file is reached from another file, which fails under --parallel; make it a static method or a TestCase method instead (.claude/rules/test-infrastructure.md):\n" . implode("\n", $calls));
});
