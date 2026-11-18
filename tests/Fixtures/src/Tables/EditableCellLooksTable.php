<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Closure;
use LogicException;
use Sylament\Support\Colors\Color;
use Sylament\Tables\Columns\CheckboxColumn;
use Sylament\Tables\Columns\SelectColumn;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Columns\TextInputColumn;
use Sylament\Tables\Columns\ToggleColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Forms\PlainSuit;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

/**
 * A jar, listed as a plain array under a record link, whose editable cells each show one way
 * a developer dresses the four editable columns: text inputs by type, input mode, step,
 * placeholder, affixes and their icons; selects by option map, groups, placeholder, enum (a
 * pure one with `pureEnumSeal`) and keys that read as one number; checkboxes and switches
 * ticked, unticked, disabled and coloured; and a read-only label beside them. A second row
 * adds its own bin to the `bin` and `crate` selects, whose option closures name the row's
 * bin and record each read, `bin` remembering its first answer.
 */
#[AsLiveComponent(name: 'Tables:EditableCellLooksTable', template: '@Fixtures/tables/table.html.twig')]
class EditableCellLooksTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    #[LiveProp]
    public bool $pureEnumSeal = false;

    public function __construct(
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                [
                    '__key' => 'north',
                    'label' => 'Plum jam',
                    'name' => 'Plum',
                    'locked' => 'Sealed',
                    'email' => '',
                    'weight' => 250,
                    'volume' => 1,
                    'depth' => 12,
                    'stock' => 'Full',
                    'shape' => 'square',
                    'size' => 'xs',
                    'lid' => 'tin',
                    'fill' => 'jam',
                    'seal' => JarSeal::Screw,
                    'code' => '1',
                    'ticked' => true,
                    'unticked' => false,
                    'frozen' => true,
                    'lit' => true,
                    'dim' => false,
                    'glow' => true,
                    'stuck' => false,
                    'bin' => 'oak',
                    'crate' => 'oak',
                ],
                ['__key' => 'south', 'bin' => 'elm', 'crate' => 'elm'],
            ])
            ->recordUrl('/larder/north')
            ->columns([
                TextColumn::make('label'),
                TextInputColumn::make('name')->tooltip('Rename the jar')->inline()->alignEnd(),
                TextInputColumn::make('locked')->disabled(),
                TextInputColumn::make('email')
                    ->type('email')
                    ->inputMode('email')
                    ->placeholder('jar@example.com')
                    ->extraInputAttributes(['data-probe' => 'email']),
                TextInputColumn::make('weight')->type('number')->step(5)->prefix('kg')->suffix('net', isInline: true),
                TextInputColumn::make('volume')->inlinePrefix()->prefix('l'),
                TextInputColumn::make('depth')->prefix('cm')->inlinePrefix(),
                TextInputColumn::make('stock')
                    ->prefixIcon('support:heart')
                    ->suffixIcon('support:star', isInline: true)
                    ->suffixIconColor('success'),
                SelectColumn::make('shape')
                    ->options(['round' => 'Round', 'square' => 'Square'])
                    ->tooltip('Pick a shape')
                    ->inline()
                    ->alignEnd()
                    ->extraInputAttributes(['data-probe' => 'shape']),
                SelectColumn::make('size')->options(['Small' => ['s' => 'S', 'xs' => 'XS'], 'Large' => ['l' => 'L']]),
                SelectColumn::make('lid')->options(['tin' => 'Tin', 'cork' => 'Cork'])->disabled(),
                SelectColumn::make('fill')->options(['jam' => 'Jam', 'honey' => 'Honey'])->selectablePlaceholder(false),
                SelectColumn::make('seal')->enum($this->pureEnumSeal ? PlainSuit::class : JarSeal::class),
                SelectColumn::make('code')->options(['01' => 'Zero one', '1' => 'One']),
                SelectColumn::make('bin')->options($this->bins('bin'))->rememberOptions(),
                SelectColumn::make('crate')->options($this->bins('crate')),
                CheckboxColumn::make('ticked')
                    ->tooltip('Tick when stocked')
                    ->inline()
                    ->alignCenter()
                    ->extraInputAttributes(['data-probe' => 'ticked']),
                CheckboxColumn::make('unticked')->label(new Markup('<em>Unticked</em> jar', 'UTF-8')),
                CheckboxColumn::make('frozen')->disabled(),
                ToggleColumn::make('lit')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('support:star')
                    ->offIcon('support:heart')
                    ->tooltip('Light the shelf')
                    ->inline()
                    ->alignCenter(),
                ToggleColumn::make('dim'),
                ToggleColumn::make('glow')->onColor(Color::Rose)->offColor(Color::Zinc),
                ToggleColumn::make('stuck')->disabled(),
            ]);
    }

    /**
     * Options naming the row's own bin, each read recorded in the {@see HookLog}.
     */
    private function bins(string $column): Closure
    {
        return function (array $record) use ($column): array {
            $key = $record['__key'] ?? null;
            $bin = $record['bin'] ?? null;

            if (!is_string($key) || !is_string($bin)) {
                throw new LogicException('A jar row names no bin.');
            }

            $this->hookLog->record("{$column} options for {$key}");

            return [$bin => ucfirst($bin)];
        };
    }
}
