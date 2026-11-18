<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Columns\IconColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Enums\FiltersLayout;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A shelf of one sealed and one open jar, whose one column flags the seal as a boolean icon,
 * whose filters fold above the rows and whose column manager reorders its column.
 */
#[AsLiveComponent(name: 'Tables:SealFlagShelfTable', template: '@Fixtures/tables/table.html.twig')]
class SealFlagShelfTable implements InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasSchemas;
    use HasTable;

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(static fn (): array => [
                ['__key' => 'plum', 'sealed' => true],
                ['__key' => 'fig', 'sealed' => false],
            ])
            ->columns([
                IconColumn::make('sealed')->boolean(),
            ])
            ->filters([Filter::make('sealedOnly')], FiltersLayout::AboveContentCollapsible)
            ->reorderableColumns();
    }
}
