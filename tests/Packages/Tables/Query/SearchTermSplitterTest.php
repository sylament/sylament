<?php

declare(strict_types=1);

use Sylament\Tables\Query\SearchTermSplitter;

it('splits a term into the words a search matches one by one', function (string $search, array $words): void {
    expect(SearchTermSplitter::split($search))->toBe($words);
})->with([
    'words between spaces, runs and ends of blanks dropped' => ['  red   lamp ', ['red', 'lamp']],
    'a quoted phrase kept as one word' => ['"red lamp" chair', ['red lamp', 'chair']],
    'the Hangul fillers between words' => ["red\u{3164}lamp\u{1160}chair", ['red', 'lamp', 'chair']],
    'a blank term' => [' ', []],
]);
