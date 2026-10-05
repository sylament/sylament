<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Locket;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A plain create of a locket whose modal maps the engraving alone, leaving the
 * clasp its constructor built keyed by `''`.
 */
#[AsLiveComponent(name: 'LocketCreateHost', template: '@ActionsFixtures/components/LocketCreateHost.html.twig')]
class LocketCreateHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function createLocketAction(): Action
    {
        return CreateAction::make('createLocket')
            ->recordClass(Locket::class)
            ->schema([
                ActionSchemaField::make('engraving'),
            ]);
    }
}
