<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Tests\Fixtures\Schemas\Record\ManuscriptStateBag;

it("holds the container's one accessor in a component made through make()", function (): void {
    expect(Section::make()->getPropertyAccessor())->toBe($this::getContainer()->get(PropertyAccessor::class));
});

it('builds a working accessor of its own, once, for a component made with new', function (): void {
    $section = new Section();
    $accessor = $section->getPropertyAccessor();
    $bag = new ManuscriptStateBag();

    $accessor->set($bag, 'data.writer.name', 'Ada');

    expect($accessor)->not->toBe($this::getContainer()->get(PropertyAccessor::class))
        ->and($section->getPropertyAccessor())->toBe($accessor)
        ->and($accessor->get($bag, 'data.writer.name'))->toBe('Ada');
});
