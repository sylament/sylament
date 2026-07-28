<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\Tests\Fixtures\StateFieldFixture;
use Symfony\UX\LiveComponent\LiveResponder;

/**
 * An action host that is not a schema host. Mounting `edit` — whose action
 * declares a schema — has nowhere to build it, which is a wiring bug, not a
 * runtime condition: the host must say so instead of silently mounting a
 * modal with no fields. `note` is the control: a schema-less action mounts
 * fine here, so the guard is provably about the schema and not about the host.
 */
class SchemalessSchemaHostFixture implements InteractsWithActions
{
    use HasActions;

    /**
     * Mounting `note` opens a modal, and that dispatches a browser event, so
     * this fixture needs the responder `ComponentToolsTrait` would otherwise
     * receive by autowiring (the `SchemaActionHost` idiom).
     */
    public function __construct()
    {
        $this->setLiveResponder(new LiveResponder());
    }

    public function editAction(): Action
    {
        return Action::make('edit')->schema([
            StateFieldFixture::make('title'),
        ]);
    }

    public function noteAction(): Action
    {
        return Action::make('note')->modalHeading('Note');
    }
}
