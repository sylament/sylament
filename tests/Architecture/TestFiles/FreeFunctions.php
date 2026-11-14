<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\TestFiles;

use PhpToken;
use Symfony\Component\Finder\Finder;

/**
 * The functions a test file declares outside any class body. Paratest gives each worker a share of the test files,
 * so a function one file declares is missing in a worker that never loaded it, and two files declaring one name
 * fatal in a worker that loads both.
 */
final class FreeFunctions
{
    /**
     * @return list<string> the functions the source declares outside any class body
     */
    public static function declaredIn(string $source): array
    {
        $tokens = array_values(array_filter(PhpToken::tokenize($source), static fn (PhpToken $token): bool => !$token->isIgnorable()));
        $names = [];
        $depth = 0;
        $classDepths = [];
        $classPending = false;

        foreach ($tokens as $index => $token) {
            $previous = $tokens[$index - 1] ?? null;

            if ($token->is([T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM]) && true !== $previous?->is(T_DOUBLE_COLON) && true !== ($tokens[$index + 1] ?? null)?->is(':')) {
                $classPending = true;
            } elseif ($token->id === ord('{') || $token->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                $depth++;

                if ($classPending) {
                    $classDepths[] = $depth;
                    $classPending = false;
                }
            } elseif ($token->id === ord('}')) {
                if ($depth === array_last($classDepths)) {
                    array_pop($classDepths);
                }

                $depth--;
            } elseif ($token->is(T_FUNCTION) && [] === $classDepths && true !== $previous?->is(T_USE)) {
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
     * @return list<string> each function a *Test.php file under the directory declares outside any class body, as
     *                      "<path>: <name>()", paths relative to the directory
     */
    public static function inTestFiles(string $directory): array
    {
        $declared = [];

        foreach (Finder::create()->files()->in($directory)->name('*Test.php') as $file) {
            foreach (self::declaredIn($file->getContents()) as $name) {
                $declared[] = $file->getRelativePathname() . ": {$name}()";
            }
        }

        sort($declared);

        return $declared;
    }
}
