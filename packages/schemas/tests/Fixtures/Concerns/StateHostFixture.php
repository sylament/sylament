<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Concerns;

use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * A schema host declaring its own `$data` bag and binding its default schema to
 * it via `->statePath('data')`. Two flat leaf fields let `fill()` / `getState()`
 * be exercised end-to-end; tests needing bespoke fields build a schema inline
 * via `Schema::make($host)->statePath('data')->components([...])`.
 */
class StateHostFixture implements InteractsWithSchemas
{
    use HasSchemas;

    #[LiveProp(writable: true)]
    public array $data = [];

    public function schema(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            StateField::make('title'),
            StateField::make('subtitle'),
        ]);
    }
}
