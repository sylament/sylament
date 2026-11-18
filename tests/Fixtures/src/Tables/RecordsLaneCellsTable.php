<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Closure;
use LogicException;
use RuntimeException;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\SelectColumn;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Columns\TextInputColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Two shelves listed as plain arrays, under columns a user may reorder, whose editable
 * cells write through closures that record what they were handed in the {@see HookLog}:
 * `title` has no such closure, `note` records its before, write and after hooks and
 * refuses an `explode`, `code` and `shelf` declare constraints (the shelf only on a shelved
 * row), `motto` a message option its constraint lacks, `secret` is hidden, `aside` toggled
 * hidden by default, `stamp` tags what it casts and records its before hook, `grade`
 * offers integer keys one of which is disabled, and `tint` offers the north shelf red and
 * pink and the south one blue and teal. The columns that open something are `opener` (a
 * closure), `hiddenOpener` (hidden), `shyOpener` (toggled hidden by default), `peek` (an
 * `Action`) and `plain`, which carries no action and is not editable.
 */
#[AsLiveComponent(name: 'Tables:RecordsLaneCellsTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class RecordsLaneCellsTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function __construct(
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                ['__key' => 'north', 'title' => 'North shelf', 'note' => 'Dry', 'code' => 'AB', 'shelf' => 'Top', 'shelved' => true, 'motto' => 'Up', 'secret' => 'Key', 'aside' => 'Cold', 'stamp' => 'Old', 'grade' => 2, 'tint' => 'red', 'opener' => 'Open', 'plain' => 'North'],
                ['__key' => 'south', 'title' => 'South shelf', 'note' => 'Damp', 'code' => 'CD', 'shelf' => '', 'shelved' => false, 'motto' => 'Down', 'secret' => 'Lock', 'aside' => 'Warm', 'stamp' => 'New', 'grade' => 1, 'tint' => 'blue', 'opener' => 'Open', 'plain' => 'South'],
            ])
            ->reorderableColumns()
            ->columns([
                TextInputColumn::make('title'),
                TextInputColumn::make('note')
                    ->beforeStateUpdated(function (array $record, mixed $state): void {
                        $this->hookLog->record(sprintf('before note of %s: %s', self::keyOf($record), var_export($state, true)));
                    })
                    ->updateStateUsing(function (array $record, mixed $state): string {
                        if ($state === 'explode') {
                            throw new RuntimeException('The ledger is locked.');
                        }

                        $this->hookLog->record(sprintf('write note of %s: %s', self::keyOf($record), var_export($state, true)));

                        return 'written';
                    })
                    ->afterStateUpdated(function (array $record, mixed $state): void {
                        $this->hookLog->record(sprintf('after note of %s: %s', self::keyOf($record), var_export($state, true)));
                    }),
                TextInputColumn::make('code')
                    ->constraints([new Length(max: 4), new Regex(pattern: '/^[A-Z]+$/')])
                    ->validationMessages([
                        Length::class => ['maxMessage' => 'Keep the code to {{ limit }} letters.'],
                        Regex::class => 'Write the code in capitals.',
                    ])
                    ->updateStateUsing($this->recorder('code')),
                TextInputColumn::make('shelf')
                    ->constraint(new NotBlank(message: 'Name the shelf.'), static fn (array $record): bool => ($record['shelved'] ?? null) === true)
                    ->updateStateUsing($this->recorder('shelf')),
                TextInputColumn::make('motto')
                    ->constraint(new Length(max: 3))
                    ->validationMessages([Length::class => 'Keep it short.'])
                    ->updateStateUsing($this->recorder('motto')),
                TextInputColumn::make('secret')->hidden()->updateStateUsing($this->recorder('secret')),
                TextInputColumn::make('aside')->toggleable(isToggledHiddenByDefault: true)->updateStateUsing($this->recorder('aside')),
                StampingTextInputColumn::make('stamp')
                    ->constraint(new Length(max: 5))
                    ->beforeStateUpdated($this->recorder('stamp', hook: 'before'))
                    ->updateStateUsing($this->recorder('stamp')),
                SelectColumn::make('grade')
                    ->options([1 => 'One', 2 => 'Two', 3 => 'Three'])
                    ->disableOptionWhen(static fn (int|string $value): bool => (int) $value === 3)
                    ->updateStateUsing($this->recorder('grade')),
                SelectColumn::make('tint')
                    ->options(static fn (array $record): array => self::keyOf($record) === 'north' ? ['red' => 'Red', 'pink' => 'Pink'] : ['blue' => 'Blue', 'teal' => 'Teal'])
                    ->updateStateUsing($this->recorder('tint')),
                TextColumn::make('opener')->action(function (array $record): string {
                    $this->hookLog->record('open ' . self::keyOf($record));

                    return 'discarded';
                }),
                TextColumn::make('hiddenOpener')->state('Open')->hidden()->action(function (array $record): void {
                    $this->hookLog->record('open hidden ' . self::keyOf($record));
                }),
                TextColumn::make('shyOpener')->state('Open')->toggleable(isToggledHiddenByDefault: true)->action(function (array $record): void {
                    $this->hookLog->record('open shy ' . self::keyOf($record));
                }),
                TextColumn::make('peek')->state('Peek')->action(Action::make('peek')->action(function (): void {
                    $this->hookLog->record('peek');
                })),
                TextColumn::make('plain'),
            ]);
    }

    private function recorder(string $column, string $hook = 'write'): Closure
    {
        return function (array $record, mixed $state) use ($column, $hook): void {
            $this->hookLog->record(sprintf('%s %s of %s: %s', $hook, $column, self::keyOf($record), var_export($state, true)));
        };
    }

    /**
     * @param array<mixed> $record
     */
    private static function keyOf(array $record): string
    {
        return is_string($record['__key'] ?? null) ? $record['__key'] : throw new LogicException('A shelf row carries no key.');
    }
}
