<?php

declare(strict_types=1);

namespace Sylament\Schemas\Contracts;

use Sylament\Schemas\Schema;

interface InteractsWithSchemas
{
    /**
     * @return array<string>
     */
    public function schemas(): array;

    public function getSchema(string $name): Schema;

    public function makeSchema(): Schema;

    /**
     * Register a schema instance under a name, or unset it by passing null.
     * The registered instance is keyed with `->key($name)`, exactly as the
     * `{$name}()` / `{$name}Schema()` discovery path keys what it builds.
     */
    public function cacheSchema(string $name, ?Schema $schema): ?Schema;

    /**
     * Whether a schema is already registered under this name. Deliberately does
     * not trigger discovery: callers use it to tell a cache-only schema (a
     * mounted action's, minted server-side) from a method-declared one.
     */
    public function hasCachedSchema(string $name): bool;

    /**
     * The pre-change raw state snapshotted for the given absolute state path at
     * the end of the previous render, used by the `afterStateUpdated` diff.
     */
    public function getOldSchemaState(?string $path): mixed;
}
