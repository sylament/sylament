<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Concerns;

use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * A schema host declaring its own writable `$data` bag and binding its schema to
 * it via `->statePath('data')`. A field rendered inside
 * `Schema::make(new FieldRenderHost())->statePath('data')` therefore resolves
 * `getState()` against `$data`: an unset key reads back null (rendering
 * `value=""`) instead of throwing on a missing host property, mirroring how a
 * shipped `#[AsLiveComponent]` form host exposes its state bag.
 */
class FieldRenderHost implements InteractsWithSchemas
{
    use HasSchemas;

    #[LiveProp(writable: true)]
    public array $data = [];

    public function schema(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }
}
