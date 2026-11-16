<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Repeater items whose fields repair a value a sibling item already holds: `rows` carries
 * a single and a multiple select, a checkbox, an enum-backed select and a select inside a
 * section; `loose` carries a select whose repair is switched off; `solo` repairs outside
 * any repeater. `save()` validates the form.
 */
#[AsLiveComponent(name: 'Forms:IndistinctRepeaterHost', template: '@Fixtures/forms/IndistinctRepeaterHost.html.twig')]
final class IndistinctRepeaterHost implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    private const array OPTIONS = ['a' => 'A', 'b' => 'B', 'c' => 'C'];

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Repeater::make('rows')
                    ->reorderable(false)
                    ->components([
                        Select::make('pick')->options(self::OPTIONS)->fixIndistinctState(),
                        Select::make('picks')->multiple()->options(self::OPTIONS)->fixIndistinctState(),
                        Checkbox::make('primary')->fixIndistinctState(),
                        Select::make('letter')->options(StringBackedLetter::class)->fixIndistinctState(),
                        Section::make()->components([
                            Select::make('nested')->options(self::OPTIONS)->fixIndistinctState(),
                        ]),
                    ]),
                Repeater::make('loose')
                    ->reorderable(false)
                    ->components([
                        Select::make('pick')->options(self::OPTIONS)->fixIndistinctState(static fn (): bool => false),
                    ]),
                Select::make('solo')->options(self::OPTIONS)->fixIndistinctState(),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}
