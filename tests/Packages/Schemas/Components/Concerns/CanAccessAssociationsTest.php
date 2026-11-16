<?php

declare(strict_types=1);

use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;

it("holds the container's reader, and its entity manager, in a schema made through make()", function (): void {
    $container = $this::getContainer();
    $reader = Schema::make()->getAssociationReader();

    expect($reader)->toBe($container->get(AssociationReader::class))
        ->and($container->get('sylament_schemas.association_reader'))->toBe($reader)
        ->and($reader->hasEntityManager())->toBeTrue();
});

it('holds a reader with no entity manager in a schema made with new, which refuses what needs one', function (): void {
    $reader = new Schema()->getAssociationReader();

    expect($reader->hasEntityManager())->toBeFalse()
        ->and(fn () => $reader->flush())->toThrow(LogicException::class, 'The sylament AssociationReader has no Doctrine EntityManager');
});
