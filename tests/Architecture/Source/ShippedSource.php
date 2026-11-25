<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Source;

use PhpToken;
use Symfony\Component\Finder\Finder;

/**
 * Reads every package's shipped PHP under src/ for the scans that hold it to a rule no tool checks.
 */
final class ShippedSource
{
    /**
     * @return list<string> every shipped PHP source file, relative to the packages/ dir
     */
    public static function files(): array
    {
        $packagesDirectory = dirname(__DIR__, 3) . '/packages';

        $files = [];

        foreach (glob($packagesDirectory . '/*/src') ?: [] as $sourceDirectory) {
            foreach (Finder::create()->files()->in($sourceDirectory)->name('*.php') as $file) {
                $files[] = str_replace($packagesDirectory . '/', '', $file->getPathname());
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Whether the source constructs an `AssociationReader`, by its short or its qualified name.
     */
    public static function constructsAssociationReader(string $source): bool
    {
        return preg_match('/\bnew\s+\\\\?(?:\w+\\\\)*AssociationReader\s*\(/', $source) === 1;
    }

    /**
     * Whether the source asks a registry for its default: a `getManager()` or `resetManager()` call with no
     * argument, which only a registry has, or a no-argument `getConnection()` on a variable or property whose name
     * contains `registry`, since only types could tell a registry's `getConnection()` from a manager's. Read through
     * the tokenizer, so a comment or a string never counts.
     */
    public static function callsRegistryDefault(string $source): bool
    {
        $tokens = array_values(array_filter(PhpToken::tokenize($source), static fn (PhpToken $token): bool => !$token->isIgnorable()));

        foreach ($tokens as $index => $token) {
            $isCall = $token->is(T_STRING)
                && ($tokens[$index - 1] ?? null)?->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])
                && ($tokens[$index + 1] ?? null)?->text === '('
                && ($tokens[$index + 2] ?? null)?->text === ')';

            if (!$isCall) {
                continue;
            }

            $method = mb_strtolower($token->text);

            if ('getmanager' === $method || 'resetmanager' === $method) {
                return true;
            }

            $receiver = $tokens[$index - 2] ?? null;

            if ('getconnection' === $method && null !== $receiver && $receiver->is([T_VARIABLE, T_STRING]) && str_contains(mb_strtolower($receiver->text), 'registry')) {
                return true;
            }
        }

        return false;
    }
}
