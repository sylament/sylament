<?php

declare(strict_types=1);

use Sylament\Support\ArrayRecord;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;

it('identifies an array record by its __key entry, or by the entry keyName() names', function (): void {
    $host = new class implements InteractsWithTable {
        use HasTable;

        public function table(Table $table): Table
        {
            return $table->records(fn (): array => [
                ['__key' => 'first', 'code' => 'a'],
                ['__key' => 'second', 'code' => 'b'],
            ]);
        }
    };

    expect(array_keys($host->getTableRecords()))->toBe(['first', 'second']);

    ArrayRecord::keyName('code');

    try {
        $host->flushCachedTableRecords();

        expect(array_keys($host->getTableRecords()))->toBe(['a', 'b']);
    } finally {
        ArrayRecord::keyName('__key');
    }
});
