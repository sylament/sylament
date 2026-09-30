<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Headband;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Nameplate;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Shortlink;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Spine;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Sku;

it('answers whether a record key still holds a blank component, read as persist() left it', function (string $state, bool $blank): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    $record = match ($state) {
        'a scalar pair with an empty half' => (static function (): Redirect {
            $redirect = new Redirect();
            $redirect->setHost('example.com');

            return $redirect;
        })(),
        'a scalar pair with a whitespace half' => (static function (): Redirect {
            $redirect = new Redirect();
            $redirect->setHost('example.com');
            $redirect->setPath('   ');

            return $redirect;
        })(),
        'a complete scalar pair' => (static function (): Redirect {
            $redirect = new Redirect();
            $redirect->setHost('example.com');
            $redirect->setPath('/old');

            return $redirect;
        })(),
        'a single scalar identifier left blank' => new Shortlink(),
        'a generated identifier still pending' => new Post(),
        'an identifier association onto a record whose key is blank' => (static function (): Headband {
            $headband = new Headband();
            $headband->setSpine(new Spine());

            return $headband;
        })(),
        'an identifier association onto a record whose key is set' => (static function (): Headband {
            $spine = new Spine();
            $spine->setCode('SP-1');

            $headband = new Headband();
            $headband->setSpine($spine);

            return $headband;
        })(),
        'a blank half beside a complete identifier association' => (static function (): ListingNote {
            $listing = new Listing();
            $listing->setSku(new Sku('AB-123'));

            $note = new ListingNote();
            $note->setListing($listing);

            return $note;
        })(),
        'a blank half beside an owner whose generated id is pending' => (static function (): PostTranslation {
            $translation = new PostTranslation();
            $translation->setPost(new Post());

            return $translation;
        })(),
        'a value object printing blank beside a set half' => (static function (): Nameplate {
            $nameplate = new Nameplate();
            $nameplate->setSlot(1);

            return $nameplate;
        })(),
        default => (static function (): PostTranslation {
            $translation = new PostTranslation();
            $translation->setPost(new Post());
            $translation->setLocale('de');

            return $translation;
        })(),
    };

    expect($reader->hasBlankAssignedIdentifier($record))->toBe($blank);
})->with([
    'a scalar pair with an empty half' => ['a scalar pair with an empty half', true],
    'a scalar pair with a whitespace half' => ['a scalar pair with a whitespace half', true],
    'a complete scalar pair' => ['a complete scalar pair', false],
    'a single scalar identifier left blank' => ['a single scalar identifier left blank', false],
    'a generated identifier still pending' => ['a generated identifier still pending', false],
    'an identifier association onto a record whose key is blank' => ['an identifier association onto a record whose key is blank', true],
    'an identifier association onto a record whose key is set' => ['an identifier association onto a record whose key is set', false],
    'a blank half beside a complete identifier association' => ['a blank half beside a complete identifier association', true],
    'a derived identity under an owner whose generated id is pending' => ['a derived identity under an owner whose generated id is pending', false],
    'a blank half beside an owner whose generated id is pending' => ['a blank half beside an owner whose generated id is pending', true],
    'a value object printing blank beside a set half' => ['a value object printing blank beside a set half', true],
]);
