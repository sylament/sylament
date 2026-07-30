<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Schemas\Exceptions\ValidationException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Drives the shipped field templates against the host error bag. The render
 * cases seed the sticky `validatedSchemas` prop rather than calling the throwing
 * `save` action: TestLiveComponent forces catchExceptions(false), so the
 * ValidationException escapes before BrowserKit stores the 422 response and
 * render() would replay the stale pre-submit HTML. Seeding stickiness is the
 * state a failed submit leaves behind, so it renders what the browser morphs in.
 *
 * `aria-invalid` is asserted with its value: the attribute bag maps booleans on
 * an `aria-*` name to the strings "true"/"false" rather than dropping a false,
 * so a valid field carries the explicit `aria-invalid="false"`.
 */

it('reads the host error bag for its own state path', function (): void {
    $host = $this->createLiveComponent('ValidatedArticleForm')->component();

    try {
        $host->getSchema()->validate();
    } catch (ValidationException) {
    }

    expect($host->getSchema()->getComponentByStatePath('data.title')->getErrorMessages())
        ->toBe(['This value should not be blank.'])
        ->and($host->getSchema()->getComponentByStatePath('data.excerpt')->getErrorMessages())
        ->toBe([]);
});

it('renders field errors and aria-invalid for a sticky invalid schema', function (): void {
    $component = $this->createLiveComponent('ValidatedArticleForm', ['validatedSchemas' => ['schema']]);

    $rendered = $component->render();
    $crawler = $rendered->crawler();

    expect((string) $rendered)->toContain('sy-fo-field-error-message')
        ->toContain('This value should not be blank.')
        ->and($crawler->filter('input[name="title"]')->attr('aria-invalid'))->toBe('true')
        ->and($crawler->filter('input[name="isFeatured"]')->attr('aria-invalid'))->toBe('true')
        ->and($crawler->filter('textarea[name="excerpt"]')->attr('aria-invalid'))->toBe('false');
});

it('renders the length violation message once the field is over the cap', function (): void {
    $component = $this->createLiveComponent('ValidatedArticleForm', ['validatedSchemas' => ['schema']]);
    $component->set('data', ['title' => str_repeat('a', 20), 'excerpt' => null, 'isFeatured' => true]);

    expect((string) $component->render())->toContain('This value is too long.');
});

it('renders no error markup while valid', function (): void {
    $component = $this->createLiveComponent('ValidatedArticleForm', ['validatedSchemas' => ['schema']]);
    $component->set('data', ['title' => 'Hi', 'excerpt' => null, 'isFeatured' => true]);

    $rendered = $component->render();

    expect((string) $rendered)
        ->not->toContain('data-validation-error')
        ->not->toContain('aria-invalid="true"')
        ->and($rendered->crawler()->filter('input[name="isFeatured"]')->attr('aria-invalid'))->toBe('false');
});
