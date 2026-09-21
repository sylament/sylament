<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Sylament\Actions\Action;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\AssignedNote;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Caption;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Cover;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Manifest;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Parcel;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Roster;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Seat;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Actions\Tests\Fixtures\UnmappedRecord;

/*
 * Integration rather than Unit because the question is what the injected
 * manager registry says about a record's class, and booting the kernel is what
 * puts the container behind `make()`. The last case is the other side of it: an
 * action built with `new`, which is what a component outside the container is.
 */

beforeEach(function (): void {
    $this::getContainer();
});

/**
 * @return array<string, mixed>
 */
function contextOfRecord(mixed $record): array
{
    return Action::make('edit')->record($record)->getContext();
}

function keyedCaption(int $id, string $locale): Caption
{
    $caption = new Caption();
    $caption->setId($id);
    $caption->setLocale($locale);

    return $caption;
}

it('mints the literal id for a record that id alone identifies', function (): void {
    $note = new AssignedNote();
    $note->setId(7);

    expect(contextOfRecord($note))->toBe(['recordKey' => '7']);
});

it('mints the literal id for a single scalar identifier under another name', function (): void {
    $parcel = new Parcel();
    $parcel->setSku('SKU-1');

    expect(contextOfRecord($parcel))->toBe(['recordKey' => 'SKU-1']);
});

it('mints nothing for a record carrying no readable id', function (): void {
    $ticket = new Ticket();
    $ticket->setCode('T-1');

    expect(contextOfRecord($ticket))->toBe([]);
});

it('mints nothing for a composite identifier one of whose fields is called id', function (): void {
    expect(contextOfRecord(keyedCaption(5, 'en-GB')))->toBe([]);
});

it('mints nothing for a composite identifier beside a plain id column', function (): void {
    $redirect = new Redirect();
    $redirect->setHost('example.com');
    $redirect->setPath('/blog');
    $redirect->setId(42);

    expect(contextOfRecord($redirect))->toBe([]);
});

it('mints nothing for a derived identity, single or composite', function (): void {
    $roster = new Roster();

    $cover = new Cover();
    $cover->setRoster($roster);

    $seat = new Seat();
    $seat->setRoster($roster);
    $seat->setCode('A1');

    expect(contextOfRecord($cover))->toBe([])
        ->and(contextOfRecord($seat))->toBe([]);
});

it('mints nothing for a derived identity beside a plain id column', function (): void {
    $manifest = new Manifest();
    $manifest->setRoster(new Roster());
    $manifest->setId(42);

    expect(contextOfRecord($manifest))->toBe([]);
});

it('mints the literal id for an anonymous record class', function (): void {
    $record = new class {
        public int $id = 9;
    };

    expect(contextOfRecord($record))->toBe(['recordKey' => '9']);
});

it('mints the literal id for a named class no Doctrine manager maps', function (): void {
    expect(contextOfRecord(new UnmappedRecord()))->toBe(['recordKey' => '9']);
});

it('mints the key entry of an array record', function (): void {
    expect(contextOfRecord(['__key' => 5, 'title' => 'Alpha']))->toBe(['recordKey' => '5']);
});

it('keeps the literal reading for an action built outside the container', function (): void {
    expect(new Action()->record(keyedCaption(5, 'en-GB'))->getContext())->toBe(['recordKey' => '5']);
});
