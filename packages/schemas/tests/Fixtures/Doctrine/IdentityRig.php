<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\ORM\EntityManager;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Beacon;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placement;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSealNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Zone;
use Sylament\Testing\Doctrine\TestPlatform;

/**
 * The in-memory rig with one record of every identifier flavour seeded: the
 * first post is id 1, and every key below follows from that.
 */
final class IdentityRig
{
    public const string HOSTILE_KEY = 'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fbundles=20all=20the=20way=20down';

    public const string TICKET_ID = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

    /**
     * What a CHAR(36) guid column legitimately holds on a platform with no guid
     * type of its own. PostgreSQL declares a guid type of its own, so the rig
     * seeds no beacon there.
     */
    public const string BEACON_ID = 'legacy-42';

    public static function create(): EntityManager
    {
        $entityManager = EntityManagerFactory::create();

        $post = new Post();
        $post->setTitle('Seven');

        $entityManager->persist($post);
        $entityManager->flush();

        $entities = [];

        foreach ([
            ['www.example.com', '/blog/2026/bundles all the way down', 'hostile'],
            ['example.com', '/clanky/Žofia~Návratová', 'unicode'],
            ['a=b', 'c%2Fd|e,f(g)[h]', 'reserved'],
            ['plain', 'about', 'plain'],
        ] as [$host, $path, $label]) {
            $redirect = new Redirect();
            $redirect->setHost($host);
            $redirect->setPath($path);
            $redirect->setLabel($label);
            $entities[] = $redirect;
        }

        foreach (['en', 'pt-BR', 'en/GB'] as $locale) {
            $translation = new PostTranslation();
            $translation->setPost($post);
            $translation->setLocale($locale);
            $translation->setTitle($locale);
            $entities[] = $translation;
        }

        $seal = new PostSeal();
        $seal->setPost($post);
        $seal->setNote('sealed');
        $entities[] = $seal;

        foreach ([[Zone::Header, 1], [Zone::Footer, 2]] as [$zone, $slot]) {
            $placement = new Placement();
            $placement->setZone($zone);
            $placement->setSlot($slot);
            $placement->setLabel($zone->value);
            $entities[] = $placement;
        }

        $listing = new Listing();
        $listing->setSku(new Sku('AB-123'));
        $listing->setTitle('Listed');
        $entities[] = $listing;

        $listingNote = new ListingNote();
        $listingNote->setListing($listing);
        $listingNote->setCode('n1');
        $listingNote->setBody('noted');
        $entities[] = $listingNote;

        $ticket = new Ticket();
        $ticket->setId(self::TICKET_ID);
        $ticket->setSerial(7);
        $ticket->setSubject('ticketed');
        $entities[] = $ticket;

        if (TestPlatform::current() !== TestPlatform::PostgreSql) {
            $beacon = new Beacon();
            $beacon->setId(self::BEACON_ID);
            $beacon->setLabel('beaconed');
            $entities[] = $beacon;
        }

        EntityManagerFactory::seed($entityManager, ...$entities);

        // A chained identity cannot be persisted in the unit of work that persists its target: Doctrine
        // hashes the new record's identifier before the target's own has been resolved.
        $sealNote = new PostSealNote();
        $sealNote->setSeal($entityManager->find(PostSeal::class, 1));
        $sealNote->setCode('n1');
        $sealNote->setBody('chained');

        EntityManagerFactory::seed($entityManager, $sealNote);

        return $entityManager;
    }
}
