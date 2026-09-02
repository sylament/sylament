<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\CheckboxColumn;
use Sylament\Tables\Columns\SelectColumn;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Columns\TextInputColumn;
use Sylament\Tables\Columns\ToggleColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The write-back seam under a real database: every editable column over one
 * query-lane table, so a live `updateTableColumnState()` round trip can be read
 * back off the container's entity manager rather than off the host instance the
 * next request re-hydrates.
 *
 * The toggle and the checkbox sit on different fields because `columns()` keys
 * by name; the two selects are the nullable and the non-nullable arm of the
 * presence gate. The query keeps the loudest songs out, so a record key the
 * database holds but the table does not reach has something to point at.
 */
#[AsLiveComponent(name: 'EditableSongsTable', template: '@TablesFixtures/components/EditableSongsTable.html.twig')]
final class EditableSongsTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()
                ->select('song')
                ->from(Song::class, 'song')
                ->where('song.plays < 1000'))
            ->columns([
                TextColumn::make('plays'),
                ToggleColumn::make('isFeatured')
                    ->disabled(fn (Song $record): bool => $record->getId() === 3)
                    ->onColor('success')
                    ->onIcon('heroicons:check-circle')
                    ->offIcon('heroicons:x-circle'),
                CheckboxColumn::make('isExplicit'),
                TextInputColumn::make('title')
                    ->constraint(new Length(min: 3))
                    ->placeholder('Untitled')
                    ->prefix('#', isInline: true),
                SelectColumn::make('mood')
                    ->options(['calm' => 'Calm', 'wild' => 'Wild'])
                    ->disableOptionWhen(fn (mixed $value): bool => $value === 'wild'),
                SelectColumn::make('tempo')->options(['slow' => 'Slow', 'fast' => 'Fast']),
            ]);
    }
}
