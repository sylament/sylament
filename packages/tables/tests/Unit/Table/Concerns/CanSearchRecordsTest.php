<?php

declare(strict_types=1);

use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;

/*
 * The table-tier half of per-column search — the opt-ins the search row and the
 * session lane read. The global-search settings this trait also carries stay in
 * `tests/Unit/TableTest.php`, which is where they landed with the query lane.
 */

it('persists column searches only once the table asks it to', function (): void {
    $table = Table::make(new TableHost());

    expect($table->persistsColumnSearchesInSession())->toBeFalse()
        ->and($table->persistColumnSearchesInSession()->persistsColumnSearchesInSession())->toBeTrue()
        ->and($table->persistColumnSearchesInSession(fn (): bool => false)->persistsColumnSearchesInSession())->toBeFalse();
});
