<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tests\Architecture\Templates\BundleTemplates;

/**
 * A tables template reaches its host through `component.getLiveComponent`, which
 * is typed `InteractsWithTable`. Every method read off it must therefore be
 * declared on the contract: a method that only exists on the `HasTable` trait
 * renders fine for a trait-using host and fatals in Twig for any other host
 * satisfying the published interface.
 */

it('reads only contract methods off the table host', function (string $template, string $method): void {
    expect(method_exists(InteractsWithTable::class, $method))->toBeTrue(
        "{$template} calls {$method}() on component.getLiveComponent, which is typed "
        . 'InteractsWithTable, but the contract does not declare it. Declare it there, or read the '
        . 'value off something the template already has.',
    );
})->with(BundleTemplates::tablesHostCalls());
