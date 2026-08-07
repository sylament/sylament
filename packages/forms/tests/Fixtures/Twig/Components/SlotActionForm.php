<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host carrying one field whose action sits in a wrapper slot rather than
 * on the field itself, so `mountAction` has to reach it through the field's child
 * schemas. The hint action is only the first consumer of that path — every one of
 * the ten field-wrapper slots resolves the same way. `$slotActionRuns` counts the
 * runs, so a test reads back whether the mounted action was found *and* called.
 */
#[AsLiveComponent(name: 'SlotActionForm', template: '@FormsFixtures/components/SlotActionForm.html.twig')]
class SlotActionForm implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    #[LiveProp]
    public int $slotActionRuns = 0;

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('title')
                    ->hintAction(Action::make('clear')->action(function (): void {
                        $this->slotActionRuns++;
                    })),
            ]);
    }
}
