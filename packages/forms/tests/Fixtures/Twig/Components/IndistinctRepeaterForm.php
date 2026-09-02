<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host whose repeater items carry one field per `fixIndistinctState()`
 * arm — a `Checkbox` for the boolean one, a `Select` for the scalar one and a
 * `CheckboxList` for the array one — so a real round trip fires the repair hook
 * and the corrected sibling is visible in both the bag and the rendered control.
 */
#[AsLiveComponent(name: 'IndistinctRepeaterForm', template: '@FormsFixtures/components/IndistinctRepeaterForm.html.twig')]
class IndistinctRepeaterForm implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * The condition every field's `fixIndistinctState()` carries, so one host
     * covers the off arm as well as the on one.
     */
    #[LiveProp]
    public bool $isFixed = true;

    /**
     * @param array<string, mixed>|null $data
     */
    public function mount(?array $data = null, bool $isFixed = true): void
    {
        $this->isFixed = $isFixed;

        $this->fill($data);
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('loose')->options(['a' => 'A', 'b' => 'B'])->fixIndistinctState($this->isFixed),
                Repeater::make('rows')->components([
                    Checkbox::make('primary')->fixIndistinctState($this->isFixed),
                    Select::make('topic')->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])->fixIndistinctState($this->isFixed),
                    CheckboxList::make('roles')->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])->fixIndistinctState($this->isFixed),
                ]),
            ]);
    }
}
