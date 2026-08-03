<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use LogicException;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Concerns\RecordFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\State\Author;
use Sylament\Schemas\Tests\Fixtures\State\InitializedPost;
use Sylament\Schemas\Tests\Fixtures\State\Post;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

it('returns the bound instance and its class', function (): void {
    $record = new RecordFixture();
    $schema = Schema::make(new StateHostFixture())->record($record);

    expect($schema->getRecord())->toBe($record)
        ->and($schema->getRecordClass())->toBe(RecordFixture::class)
        ->and($schema->getRecordInstance())->toBe($record);
});

it('declares a class without binding an instance', function (): void {
    $schema = Schema::make(new StateHostFixture())->record(RecordFixture::class);

    expect($schema->getRecord())->toBeNull()
        ->and($schema->getRecordClass())->toBe(RecordFixture::class)
        ->and($schema->getRecordInstance())->toBeInstanceOf(RecordFixture::class);
});

it('treats an array record as class-less', function (): void {
    $schema = Schema::make(new StateHostFixture())->record(['title' => 'Hello']);

    expect($schema->getRecord())->toBe(['title' => 'Hello'])
        ->and($schema->getRecordClass())->toBeNull();
});

it('replaces the previous binding on a rebind', function (): void {
    $schema = Schema::make(new StateHostFixture())->record(new RecordFixture())->record(RecordFixture::class);

    expect($schema->getRecord())->toBeNull()
        ->and($schema->getRecordClass())->toBe(RecordFixture::class);
});

it('memoizes a closure-bound record at the root tier', function (): void {
    $calls = 0;
    $schema = Schema::make(new StateHostFixture())->record(function () use (&$calls): RecordFixture {
        $calls++;

        return new RecordFixture();
    });

    $schema->getRecordClass();
    $schema->getRecord();

    expect($calls)->toBe(1);
});

it('returns null everywhere when nothing is bound', function (): void {
    $schema = Schema::make(new StateHostFixture());

    expect($schema->getRecord())->toBeNull()
        ->and($schema->getRecordClass())->toBeNull()
        ->and($schema->getRecordInstance())->toBeNull();
});

it('injects the record into schema closures by name and by type', function (): void {
    $record = new RecordFixture();
    $record->setTitle('Bound');

    $schema = Schema::make(new StateHostFixture())->record($record);

    expect($schema->evaluate(fn ($record): string => $record->getTitle()))->toBe('Bound')
        ->and($schema->evaluate(fn (RecordFixture $article): string => $article->getTitle()))->toBe('Bound');
});

it('maps validated state onto the bound record and returns it', function (): void {
    $record = new RecordFixture();
    $record->setTitle('Old');

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('title'), StateField::make('perex')])
        ->record($record);

    $schema->fill(['title' => 'New', 'perex' => 'Fresh']);

    expect($schema->getData())->toBe($record)
        ->and($record->getTitle())->toBe('New')
        ->and($record->getPerex())->toBe('Fresh');
});

it('constructs a fresh instance from a declared class when no instance is bound', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('title')])
        ->record(RecordFixture::class);

    $schema->fill(['title' => 'Built']);

    $data = $schema->getData();

    expect($data)->toBeInstanceOf(RecordFixture::class)
        ->and($data->getTitle())->toBe('Built');
});

it('excludes dehydrated(false) fields from the mapping', function (): void {
    $record = new RecordFixture();
    $record->setTitle('Kept');

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('title')->dehydrated(false), StateField::make('perex')])
        ->record($record);

    $schema->fill(['title' => 'Changed', 'perex' => 'Mapped']);
    $schema->getData();

    expect($record->getTitle())->toBe('Kept')
        ->and($record->getPerex())->toBe('Mapped');
});

it('throws when a state key has no writable target', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('nonexistent')])
        ->record(new RecordFixture());

    $schema->fill(['nonexistent' => 'x']);

    expect(fn (): object => $schema->getData())->toThrow(NoSuchPropertyException::class);
});

it('throws when no record is bound at all', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('title')]);

    expect(fn (): object => $schema->getData())->toThrow(LogicException::class);
});

it('maps nested state through the record graph in place', function (): void {
    $author = new Author(name: 'Ada', handle: '@ada');
    $post = new Post();
    $post->setAuthor($author);

    $host = new StateHostFixture();
    $host->data = ['title' => 'Hello', 'author' => ['name' => 'Grace', 'handle' => '@grace']];

    $schema = Schema::make($host)->statePath('data')->record($post)->components([
        StateField::make('title'),
        Section::make('Author')->statePath('author')->components([
            StateField::make('name'),
            StateField::make('handle'),
        ]),
    ]);

    $data = $schema->getData();

    expect($data)->toBe($post)
        ->and($post->getAuthor())->toBe($author)
        ->and($author->getName())->toBe('Grace')
        ->and($author->getHandle())->toBe('@grace')
        ->and($post->getTitle())->toBe('Hello');
});

it('maps nested state onto a fresh instance with an initialized intermediate', function (): void {
    $host = new StateHostFixture();
    $host->data = ['author' => ['name' => 'Grace']];

    $schema = Schema::make($host)->statePath('data')->record(InitializedPost::class)->components([
        Section::make('Author')->statePath('author')->components([
            StateField::make('name'),
        ]),
    ]);

    $data = $schema->getData();

    expect($data)->toBeInstanceOf(InitializedPost::class)
        ->and($data->getAuthor()->getName())->toBe('Grace');
});

it('excludes a non-dehydrated nested leaf from the mapping', function (): void {
    $author = new Author(name: 'Ada', handle: '@ada');
    $post = new Post();
    $post->setAuthor($author);

    $host = new StateHostFixture();
    $host->data = ['author' => ['name' => 'Grace', 'handle' => '@grace']];

    Schema::make($host)->statePath('data')->record($post)->components([
        Section::make('Author')->statePath('author')->components([
            StateField::make('name'),
            StateField::make('handle')->dehydrated(false),
        ]),
    ])->getData();

    expect($author->getName())->toBe('Grace')
        ->and($author->getHandle())->toBe('@ada');
});

it('writes a present-but-null nested leaf', function (): void {
    $author = new Author(name: 'Ada');
    $post = new Post();
    $post->setAuthor($author);

    $host = new StateHostFixture();
    $host->data = ['author' => ['name' => null]];

    Schema::make($host)->statePath('data')->record($post)->components([
        Section::make('Author')->statePath('author')->components([
            StateField::make('name'),
        ]),
    ])->getData();

    expect($author->getName())->toBeNull();
});

it('preserves unmanaged keys in an array-backed branch on mapping', function (): void {
    $post = new Post();
    $post->setMeta(['keep' => 1]);

    $host = new StateHostFixture();
    $host->data = ['meta' => ['seo' => 'Hi']];

    Schema::make($host)->statePath('data')->record($post)->components([
        Section::make('Meta')->statePath('meta')->components([
            StateField::make('seo'),
        ]),
    ])->getData();

    expect($post->getMeta())->toBe(['keep' => 1, 'seo' => 'Hi']);
});

it('constructs the missing nested object through getData', function (): void {
    $post = new Post();

    $host = new StateHostFixture();
    $host->data = ['author' => ['name' => 'Grace']];

    Schema::make($host)->statePath('data')->record($post)->components([
        Section::make('Author')->statePath('author')->components([
            StateField::make('name'),
        ]),
    ])->getData();

    expect($post->getAuthor())->toBeInstanceOf(Author::class)
        ->and($post->getAuthor()?->getName())->toBe('Grace');
});

it('constructs a missing branch and writes its leaves', function (): void {
    $record = new Post();

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            StateField::make('title'),
            StateGroup::make()
                ->statePath('author')
                ->components([StateField::make('name')]),
        ])
        ->record($record);

    $schema->fill(['title' => 'Ports', 'author' => ['name' => 'Ada']]);

    expect($schema->getData())->toBe($record)
        ->and($record->getAuthor())->toBeInstanceOf(Author::class)
        ->and($record->getAuthor()?->getName())->toBe('Ada');
});

it('nulls a declared branch when every leaf is blank', function (): void {
    $record = new Post();
    $record->setAuthor(new Author());

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            StateField::make('title'),
            StateGroup::make()
                ->statePath('author')
                ->nullWhenBlank()
                ->components([StateField::make('name')]),
        ])
        ->record($record);

    $schema->fill(['title' => 'Ports', 'author' => ['name' => null]]);
    $schema->getData();

    expect($record->getAuthor())->toBeNull();
});

it('keeps a declared branch when one leaf is filled', function (): void {
    $record = new Post();

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            StateGroup::make()
                ->statePath('author')
                ->nullWhenBlank()
                ->components([StateField::make('name')]),
        ])
        ->record($record);

    $schema->fill(['author' => ['name' => 'Ada']]);
    $schema->getData();

    expect($record->getAuthor()?->getName())->toBe('Ada');
});

it('leaves an undeclared branch constructed even when blank', function (): void {
    $record = new Post();

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            StateGroup::make()
                ->statePath('author')
                ->components([StateField::make('name')]),
        ])
        ->record($record);

    $schema->fill(['author' => ['name' => null]]);
    $schema->getData();

    expect($record->getAuthor())->toBeInstanceOf(Author::class);
});

it('skips only the paths beneath a nulled branch, not a sibling sharing its prefix', function (): void {
    $record = new Post();

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            StateGroup::make()
                ->statePath('meta')
                ->components([
                    StateGroup::make()
                        ->statePath('seo')
                        ->nullWhenBlank()
                        ->components([StateField::make('title')]),
                    StateField::make('seoScore'),
                ]),
        ])
        ->record($record);

    $schema->fill(['meta' => ['seo' => ['title' => null], 'seoScore' => 42]]);
    $schema->getData();

    expect($record->getMeta())->toBe(['seo' => null, 'seoScore' => 42]);
});

it('short-circuits a nested branch beneath a nulled outer branch', function (): void {
    $record = new Post();
    $record->setMeta(['seo' => ['title' => 'Old']]);

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            StateGroup::make()
                ->statePath('meta')
                ->nullWhenBlank()
                ->components([
                    StateGroup::make()
                        ->statePath('seo')
                        ->nullWhenBlank()
                        ->components([StateField::make('title')]),
                ]),
        ])
        ->record($record);

    $schema->fill(['meta' => ['seo' => ['title' => null]]]);
    $schema->getData();

    expect($record->getMeta())->toBeNull();
});

it('leaves a hidden branch alone rather than nulling what it never showed', function (): void {
    $record = new Post();
    $record->setAuthor(new Author(name: 'Ada'));

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            StateGroup::make()
                ->statePath('author')
                ->nullWhenBlank()
                ->hidden()
                ->components([StateField::make('name')]),
        ])
        ->record($record);

    $schema->fill(['author' => ['name' => 'Ada']]);
    $schema->getData();

    expect($record->getAuthor()?->getName())->toBe('Ada');
});

it('leaves a branch alone when the dehydration walk pruned its only leaf', function (): void {
    $record = new Post();
    $record->setAuthor(new Author(name: 'Ada'));

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            StateGroup::make()
                ->statePath('author')
                ->nullWhenBlank()
                ->components([StateField::make('name')->hidden()]),
        ])
        ->record($record);

    $schema->fill(['author' => ['name' => 'Ada']]);
    $schema->getData();

    expect($record->getAuthor()?->getName())->toBe('Ada');
});

it('leaves a nullWhenBlank() leaf alone, since a leaf declares no branch', function (): void {
    $record = new Post();
    $record->setTitle('Kept');

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('title')->nullWhenBlank()->dehydrated(false)])
        ->record($record);

    $schema->fill(['title' => null]);
    $schema->getData();

    expect($record->getTitle())->toBe('Kept');
});
