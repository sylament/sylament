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

    /**
     * Run the validator over the schema's collected constraints against the raw
     * live state. Failures fill the error bag; on the submit path (default) a
     * failure also records the schema key sticky and throws, while success
     * clears both. The silent path only rewrites the bag section.
     */
    public function validateSchema(Schema $schema, bool $shouldThrow = true): void;

    /**
     * Run the validator over the record a schema has just mapped, folding each
     * violation into the same error bag under the state path that owns it — the
     * schema's own path for a violation no declared path claims — recording the
     * schema key sticky and throwing when any exist. A host holding no validator
     * (one built outside the container) skips the run rather than refusing to
     * map at all.
     */
    public function validateSchemaRecord(Schema $schema, object $record): void;

    /**
     * All bag messages recorded for an absolute state path; null returns none.
     *
     * @return list<string>
     */
    public function getErrorsForSchemaStatePath(?string $path): array;

    /**
     * Drop one schema's stickiness and bag section, or all of both when the key
     * is null.
     */
    public function clearSchemaValidation(?string $schemaKey = null): void;
}
