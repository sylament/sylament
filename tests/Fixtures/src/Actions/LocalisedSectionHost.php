<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function Symfony\Component\Translation\t;

/**
 * Answers on the localised live route, with one action, `stamp`, opening a modal, registered on a section
 * whose key is slugged from a heading the catalogue translates differently per locale.
 */
#[AsLiveComponent(name: 'Actions:LocalisedSectionHost', template: '@Fixtures/actions/LocalisedSectionHost.html.twig', route: 'sylament_tests_actions_localised_live')]
class LocalisedSectionHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(t('section', [], 'fixtures-actions'))
                ->registerActions([
                    Action::make('stamp')->modalHeading('Stamp')->action(function (): void {
                        $this->hookLog->record('stamped');
                    }),
                ]),
        ]);
    }
}
