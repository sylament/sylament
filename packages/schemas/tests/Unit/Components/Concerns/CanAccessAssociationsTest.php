<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Concerns\CanAccessAssociations;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;

$makeConsumer = static fn (): object => new class {
    use CanAccessAssociations;
};

it('falls back to a reader with no entity manager when none was injected', function () use ($makeConsumer): void {
    expect($makeConsumer()->getAssociationReader()->hasEntityManager())->toBeFalse();
});

it('answers the injected reader unchanged', function () use ($makeConsumer): void {
    $consumer = $makeConsumer();
    $reader = new AssociationReader(EntityManagerFactory::create());

    $consumer->setAssociationReader($reader);

    expect($consumer->getAssociationReader())->toBe($reader);
});

it('memoises the fallback reader', function () use ($makeConsumer): void {
    $consumer = $makeConsumer();

    expect($consumer->getAssociationReader())->toBe($consumer->getAssociationReader());
});
