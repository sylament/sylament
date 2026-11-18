<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\CheckboxColumn;
use Sylament\Tables\Columns\SelectColumn;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Columns\ToggleColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\ColumnKindSample;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Samples whose `flagged` boolean is edited by the control it is given: a `checkbox`, a
 * `toggle`, or a `select` offering "Flagged" and "Clear", which posts the option's key.
 */
#[AsLiveComponent(name: 'Tables:EditableFlaggedSampleTable', template: '@Fixtures/tables/table.html.twig')]
class EditableFlaggedSampleTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    #[LiveProp]
    public string $control = 'checkbox';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('sample')->from(ColumnKindSample::class, 'sample'))
            ->columns([
                match ($this->control) {
                    'toggle' => ToggleColumn::make('flagged'),
                    'select' => SelectColumn::make('flagged')->options(['1' => 'Flagged', '0' => 'Clear']),
                    default => CheckboxColumn::make('flagged'),
                },
                TextColumn::make('body'),
            ]);
    }
}
