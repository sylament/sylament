<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\SelectColumn;
use Sylament\Tables\Columns\TextInputColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Editable cells over identifier fields, one flavour per `$over`: both halves
 * of the scalar pair {@see Shortcut} (`path` typed, `host` picked), the scalar
 * half and the association half of the derived {@see Residency}, and the single
 * assigned id of {@see Song}. Each flavour also carries an editable column that
 * is no identifier, so a write the key rule must leave alone sits beside the
 * ones it refuses.
 */
#[AsLiveComponent(name: 'IdentifierCellsTable', template: '@TablesFixtures/components/IdentifierCellsTable.html.twig')]
final class IdentifierCellsTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    #[LiveProp]
    public string $over = 'shortcut';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return match ($this->over) {
            'residency' => $table
                ->query($this->entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency'))
                ->columns([
                    TextInputColumn::make('label'),
                    TextInputColumn::make('season'),
                    SelectColumn::make('venue')->options(['north.hall' => 'North Hall', 'astra-hall' => 'Astra Hall']),
                ]),
            'song' => $table
                ->query($this->entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
                ->columns([
                    TextInputColumn::make('title'),
                    TextInputColumn::make('id'),
                ]),
            default => $table
                ->query($this->entityManager->createQueryBuilder()->select('shortcut')->from(Shortcut::class, 'shortcut'))
                ->columns([
                    TextInputColumn::make('label'),
                    TextInputColumn::make('path'),
                    SelectColumn::make('host')->options(['example.org' => 'example.org', 'sylament.test' => 'sylament.test', 'moved.test' => 'moved.test']),
                ]),
        };
    }
}
