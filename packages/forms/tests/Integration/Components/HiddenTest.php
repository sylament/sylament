<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\Hidden;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;

use function Sylament\Forms\Tests\renderField;
use function Sylament\Forms\Tests\renderToHtml;

/**
 * Wire a hidden field into a `data`-bag-bound schema and hydrate it, so a
 * `default()` reaches the host bag exactly as a live form host's `mount()` does.
 * `renderField()` deliberately skips hydration, so a rendered-value assertion
 * needs this instead.
 */
function hydratedHiddenSchema(Hidden $field): Schema
{
    $schema = Schema::make(new FieldRenderHost())->statePath('data')->components([$field]);
    $schema->hydrateState();

    return $schema;
}

it('renders one bare hidden input with no field wrapper', function (): void {
    $crawler = new Crawler(renderField(Hidden::make('token')->label('Token')));

    $input = $crawler->filter('input.sy.sy-fo-hidden');

    expect($input->count())->toBe(1)
        ->and($input->attr('type'))->toBe('hidden')
        ->and($input->attr('id'))->toBe('token')
        ->and($input->attr('data-model'))->toBe('norender|data.token')
        ->and($crawler->filter('.sy-fo-field')->count())->toBe(0)
        ->and($crawler->filter('label')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-field-error-message')->count())->toBe(0);
});

it('renders an empty value attribute for unset state rather than omitting it', function (): void {
    $input = new Crawler(renderField(Hidden::make('token')))->filter('input.sy-fo-hidden');

    expect($input->attr('value'))->toBe('');
});

it('round-trips the state through the value attribute', function (): void {
    $crawler = new Crawler(renderToHtml(hydratedHiddenSchema(Hidden::make('token')->default('abc123'))));

    expect($crawler->filter('input.sy-fo-hidden')->attr('value'))->toBe('abc123');
});

it('collapses its grid column so the schema slot renders nothing', function (): void {
    $crawler = new Crawler(renderField(Hidden::make('token')));

    $slot = $crawler->filter('div.sy-grid-col.sy-hidden');

    expect(Hidden::make('token')->getColumnSpan('default'))->toBe('hidden')
        ->and($slot->count())->toBe(1)
        ->and($slot->filter('input.sy-fo-hidden')->count())->toBe(1);
});

it('merges extra attributes onto the input', function (): void {
    $input = new Crawler(renderField(
        Hidden::make('token')->extraAttributes(['data-role' => 'csrf', 'class' => 'app-token']),
    ))->filter('input.sy-fo-hidden');

    expect($input->count())->toBe(1)
        ->and($input->attr('data-role'))->toBe('csrf')
        ->and($input->attr('type'))->toBe('hidden')
        ->and($input->attr('class'))->toContain('app-token');
});

it('emits no name attribute, matching Filament', function (): void {
    $input = new Crawler(renderField(Hidden::make('token')))->filter('input.sy-fo-hidden');

    expect($input->attr('name'))->toBeNull();
});
