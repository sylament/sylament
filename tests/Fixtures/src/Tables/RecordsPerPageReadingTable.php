<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Enums\PaginationMode;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Seven array rows a `records()` closure returns whole, paginated over 5, 10 or all a page, the
 * closure recording in the request's {@see HookLog} the `recordsPerPage` it was handed. Each
 * variant configures one setting: `unpaginated`, `noOptions` (`paginationPageOptions([])`) and
 * `simple` (`PaginationMode::Simple`).
 */
#[AsLiveComponent(name: 'Tables:RecordsPerPageReadingTable', template: '@Fixtures/tables/table.html.twig')]
class RecordsPerPageReadingTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    /**
     * @var list<string>
     */
    #[LiveProp]
    public array $variants = [];

    public function __construct(
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        $table
            ->records(function (int|string|null $recordsPerPage): array {
                $this->hookLog->record('recordsPerPage ' . var_export($recordsPerPage, true));

                return array_map(static fn (string $name): array => ['__key' => mb_strtolower($name), 'name' => $name], ['Ash', 'Birch', 'Cedar', 'Elm', 'Fir', 'Larch', 'Oak']);
            })
            ->columns([
                TextColumn::make('name'),
            ])
            ->paginated([5, 10, 'all']);

        if (in_array('unpaginated', $this->variants, true)) {
            $table->paginated(false);
        }

        if (in_array('noOptions', $this->variants, true)) {
            $table->paginationPageOptions([]);
        }

        if (in_array('simple', $this->variants, true)) {
            $table->paginationMode(PaginationMode::Simple);
        }

        return $table;
    }
}
