<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration;

use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Drives real TextInput/Textarea fields through the T2 live-state loop on a
 * shipped-shape #[AsLiveComponent] host. Mirrors schemas' LiveStateTest, with two
 * deliberate deltas forced by the shipped field surface:
 *
 *  - The field templates bind via Symfony UX `data-model` (not Livewire's
 *    `wire:model`, which auto-hydrates a server-side `value`), so an input carries
 *    NO rendered `value` attribute. The round-tripped state is therefore read from
 *    the live component's props rather than an input `value` attr.
 *  - The `afterStateUpdated` hook slugifies the *current* title, so it is
 *    deterministic; the "does not re-fire" case hand-edits the derived slug and
 *    proves an unchanged title leaves that edit intact (a re-fire would clobber it).
 */

it('binds each field with its data-model modifier', function (): void {
    $html = (string) $this->createLiveComponent('ArticleForm')->render();

    expect($html)
        ->toContain('data-model="on(change)|data.title"')   // live(onBlur: true) -> blur/change
        ->toContain('data-model="norender|data.slug"')      // default -> syncs, no re-render
        ->toContain('data-model="norender|data.excerpt"');  // Textarea, default binding
});

it('slugifies the new title into slug via afterStateUpdated', function (): void {
    $component = $this->createLiveComponent('ArticleForm')
        ->set('data.title', 'Hello World');

    // The round-trip fired title's afterStateUpdated, which slugified the new
    // value into the sibling slug; the re-render assembles the whole schema again.
    $component->render();

    expect($component->component()->data)->toBe([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'excerpt' => null,
    ]);
});

it('does not re-fire afterStateUpdated when the title is unchanged', function (): void {
    $component = $this->createLiveComponent('ArticleForm')
        ->set('data.title', 'Hello World')    // fires -> slug = 'hello-world'
        ->set('data.slug', 'manual-override') // hand-edit the derived slug
        ->set('data.title', 'Hello World');   // title unchanged -> hook must NOT fire

    // A re-firing hook would clobber the manual slug back to 'hello-world'.
    expect($component->component()->data['slug'])->toBe('manual-override');
});

it('assembles getState from the field bag on a live action', function (): void {
    $component = $this->createLiveComponent('ArticleForm')
        ->set('data.title', 'Hello World')
        ->call('save');

    expect($component->component()->savedState)->toBe([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'excerpt' => null,
    ]);
});
