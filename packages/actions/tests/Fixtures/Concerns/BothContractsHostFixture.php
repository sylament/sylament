<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * A host implementing both the actions and schemas contracts — the shape the
 * action-side live-component chain resolves to — with a `$data` bag for
 * schemas bound via `->statePath('data')`.
 */
class BothContractsHostFixture implements InteractsWithActions, InteractsWithSchemas
{
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];
}
