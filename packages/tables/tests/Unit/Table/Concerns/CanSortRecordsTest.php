<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;

use function Sylament\Tables\Tests\relationshipTable;

it('orders a relation-scoped table by its mapping until a default sort is declared', function (): void {
    $artist = new Artist(1, 'Radiohead');
    $song = new Song(1, 'Airbag', 100);
    $ordered = relationshipTable(fn (): Relationship => new Relationship($artist, 'songs'));
    $declared = relationshipTable(fn (): Relationship => new Relationship($artist, 'songs'), fn (Table $table): Table => $table->defaultSort('plays', 'desc'));
    $unordered = relationshipTable(fn (): Relationship => new Relationship($song, 'genres'));

    $query = $ordered->getQuery();
    assert($query instanceof QueryBuilder);
    $sorted = $ordered->getDefaultSort($query, 'asc');

    expect($sorted)->toBe($query)
        ->and($query->getDQL())->toEndWith('ORDER BY record.title asc')
        ->and($declared->getDefaultSort($declared->getQuery() ?? throw new LogicException(), 'desc'))->toBe('plays')
        ->and($unordered->getDefaultSort($unordered->getQuery() ?? throw new LogicException(), 'asc'))->toBeNull();
});
