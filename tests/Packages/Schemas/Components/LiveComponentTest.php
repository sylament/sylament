<?php

declare(strict_types=1);

use Sylament\Schemas\Components\LiveComponent;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\ChildEmbeddingHost;
use Sylament\Tests\Fixtures\Schemas\EmbeddedLabelChild;

describe('make', function (): void {
    it('mounts the child it names, by registered name or class-string, with the props its data names', function (string $component): void {
        $child = $this->renderSchemaComponent(LiveComponent::make($component, ['label' => 'Seven seeds'])->key('ledger'))
            ->filter('[data-live-name-value="Schemas:EmbeddedLabelChild"]');

        expect($child->count())->toBe(1)
            ->and($child->text())->toBe('Seven seeds');
    })->with([
        'its registered name' => ['Schemas:EmbeddedLabelChild'],
        'its class-string' => [EmbeddedLabelChild::class],
    ]);
});

describe('mount props', function (): void {
    it("passes the child only the props its data names, never the schema's record", function (): void {
        $child = $this->createLiveComponent(ChildEmbeddingHost::class)->render()->crawler()
            ->filter('[data-live-name-value="Schemas:EmbeddedLabelChild"]');

        expect($child->text())->toBe('Seven seeds');
    });
});

describe('child id', function (): void {
    it('ids a child under no live host by its bare key, each character outside letters, digits and _~=- a hyphen', function (string $key, string $id): void {
        $rendered = $this->renderViewComponent(Schema::make()->components([LiveComponent::make(EmbeddedLabelChild::class)->key($key)]));

        expect($rendered->filter('[data-live-name-value="Schemas:EmbeddedLabelChild"]')->attr('id'))->toBe($id);
    })->with([
        'a dotted key' => ['a.b', 'sy-live-a-b'],
        "a hyphenated key, which mints the dotted key's id" => ['a-b', 'sy-live-a-b'],
        'a composite record key' => ['record-1~zh=20Hans', 'sy-live-record-1~zh=20Hans'],
        'a class-string key' => ['App\RelationManagers\LinesRelationManager', 'sy-live-App-RelationManagers-LinesRelationManager'],
    ]);

    it("ids a child under a live host by the host's id and its absolute key, the same on every request", function (): void {
        $tester = $this->createLiveComponent(ChildEmbeddingHost::class, ['childKey' => 'record-1~zh=20Hans']);
        $id = 'sy-live-' . $tester->component()->getSchemasHostId() . '-schema-record-1~zh=20Hans';
        $child = fn (): string => (string) $tester->render()->crawler()->filter('[data-live-name-value="Schemas:EmbeddedLabelChild"]')->attr('id');

        expect($child())->toBe($id);

        $tester->refresh();

        expect($child())->toBe($id);
    });

    it('refuses to embed a child without a key under no live host', function (): void {
        expect(fn (): mixed => $this->renderViewComponent(Schema::make()->components([LiveComponent::make(EmbeddedLabelChild::class)])))
            ->toThrow('A ' . LiveComponent::class . ' embed needs a key() — an unkeyed child cannot keep a stable identity across renders.');
    });

    it('refuses to embed a child without a key under a live host, an empty or blank key included', function (?string $key): void {
        expect(fn (): mixed => $this->createLiveComponent(ChildEmbeddingHost::class, ['childKey' => $key])->render())
            ->toThrow('A ' . LiveComponent::class . ' embed needs a key() — an unkeyed child cannot keep a stable identity across renders.');
    })->with([
        'no key' => [null],
        'an empty key' => [''],
        'a key of spaces' => ['  '],
    ]);
});

describe('no wrapper', function (): void {
    it('renders the child alone, its own id the only one, whatever id and attributes the embed is given', function (): void {
        $rendered = $this->renderSchemaComponent(
            LiveComponent::make(EmbeddedLabelChild::class, ['label' => 'Seven seeds'])->key('ledger')->id('ledger-wrapper')->extraAttributes(['data-wrapper' => 'ledger']),
        );

        expect($rendered->filter('[data-live-name-value="Schemas:EmbeddedLabelChild"]')->attr('id'))->toStartWith('sy-live-')
            ->and($rendered->filter('#ledger-wrapper')->count())->toBe(0)
            ->and($rendered->filter('[data-wrapper]')->count())->toBe(0);
    });
});
