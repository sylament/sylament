<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Service\Invoker;
use Symfony\Component\DependencyInjection\Container;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

it('passes the section to configuration closures as $component', function (): void {
    $section = Section::make('Settings');

    $received = null;
    $section->description(function (Section $component) use (&$received): string {
        $received = $component;

        return 'description';
    });

    expect($section->getDescription())->toBe('description')
        ->and($received)->toBe($section);
});

it('passes the section to closures type-hinting the base component class', function (): void {
    $section = Section::make('Settings');

    $received = null;
    $section->description(function (Component $schemaComponent) use (&$received): string {
        $received = $schemaComponent;

        return 'description';
    });
    $section->getDescription();

    expect($received)->toBe($section);
});

it('derives its key from the heading slug', function (): void {
    expect(Section::make('Personal Details')->getKey(isAbsolute: false))->toBe('personal-details::section');
});

it('transliterates the heading when deriving the key', function (): void {
    expect(Section::make('Účet používateľa')->getKey(isAbsolute: false))->toBe('ucet-pouzivatela::section');
});

it('has no key without a heading', function (): void {
    expect(Section::make()->getKey())->toBeNull();
});

it('lets an explicit key override the derived one', function (): void {
    expect(Section::make('Profile')->key('custom')->getKey(isAbsolute: false))->toBe('custom');
});

it('does not prefix child component keys with the section key', function (): void {
    $child = new Component()->key('name');
    $section = Section::make('Profile')->components([$child]);
    Schema::make(new HasSchemasFixture())->components([$section])->getComponents();
    $section->getChildSchema()?->getComponents();

    expect($child->getKey())->toBe('name')
        ->and($section->getKey())->toBe('profile::section');
});
