<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
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
