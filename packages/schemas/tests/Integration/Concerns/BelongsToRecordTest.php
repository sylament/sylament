<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Tests\Fixtures\Concerns\RecordFixture;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

it('does not touch the record when validation fails', function (): void {
    $host = $this->createLiveComponent('RecordHost')->component();
    $record = $host->getSchema()->getRecord();

    expect(fn (): object => $host->getSchema()->getData())->toThrow(ValidationException::class)
        ->and($record->getTitle())->toBe('Seeded');
});

it('round-trips fill(object) → edit bag → getData()', function (): void {
    $host = $this->createLiveComponent('RecordHost')->component();
    $schema = $host->getSchema();

    $schema->fill($schema->getRecord());
    $host->data['title'] = 'Edited';

    expect($schema->getData()->getTitle())->toBe('Edited');
});

it('round-trips a nested record path in place', function (): void {
    $host = $this->createLiveComponent('NestedRecordHost')->component();
    $schema = $host->getSchema();
    $post = $schema->getRecord();
    $author = $post->getAuthor();

    $schema->fill($post);
    $host->data['author']['name'] = 'Grace';

    $data = $schema->getData();

    expect($data)->toBe($post)
        ->and($data->getAuthor())->toBe($author)
        ->and($author->getName())->toBe('Grace');
});

it('does not touch the nested object when validation fails', function (): void {
    $host = $this->createLiveComponent('NestedRecordHost')->component();
    $schema = $host->getSchema();
    $author = $schema->getRecord()->getAuthor();

    $schema->fill($schema->getRecord());
    $host->data['author']['name'] = '';

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($author->getName())->toBe('Ada');
});

it('maps a supplied state array instead of re-reading the validated state', function (): void {
    $host = $this->createLiveComponent('RecordHost')->component();
    $schema = $host->getSchema();
    $host->data = ['title' => 'typed'];

    $record = $schema->getData(['title' => 'mutated after validation']);

    expect($record->getTitle())->toBe('mutated after validation');
});

it('reads a record as the array the form would be filled with', function (): void {
    $record = new RecordFixture();
    $record->setTitle('Alpha');

    $schema = $this->createLiveComponent('RecordHost')->component()->getSchema();

    expect($schema->getStateFromRecord($record))->toBe(['title' => 'Alpha', 'perex' => null]);
});
