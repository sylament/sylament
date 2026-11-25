<?php

declare(strict_types=1);

use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Tests\Fixtures\Entity\Product;

it("holds the container's reader, and its entity manager, in a schema made through make()", function (): void {
    $reader = Schema::make()->getAssociationReader();

    expect($reader)->toBe($this::getContainer()->get(AssociationReader::class))
        ->and($reader->hasEntityManager())->toBeTrue();
});

it('holds a reader with no entity manager in a schema made with new, which refuses what needs one', function (): void {
    $reader = new Schema()->getAssociationReader();

    expect($reader->hasEntityManager())->toBeFalse()
        ->and(fn () => $reader->flush(new Product('Lamp')))->toThrow(LogicException::class, 'The sylament AssociationReader has no Doctrine EntityManager');
});
