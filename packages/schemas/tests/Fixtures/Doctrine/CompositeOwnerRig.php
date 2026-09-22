<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\ORM\EntityManager;
use LogicException;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Chain;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Enrolment;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Marker;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\MixedFirstMarker;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\MixedLastMarker;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placard;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Sigil;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\SigilChild;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Slip;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Vault;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Zone;

/**
 * {@see CompositeTargetRig} read from the other end: every composite flavour
 * seeded here owns records on both arms, and beside each owner stands one that
 * shares its first identifier half and one that shares its second, so a scope
 * built from a single half hands one owner another's records. It also seeds the
 * candidate hazards — a record with no owner at all and one whose composite
 * foreign key is half NULL, inserted raw because the mapping cannot express it.
 *
 * Every key below is a literal, minted by hand from the seeded values rather
 * than asked of the codec.
 */
final class CompositeOwnerRig
{
    public const string REDIRECT_MINE = 'example=2Ecom~=2Fa';

    public const string REDIRECT_SHARES_HOST = 'example=2Ecom~=2Fb';

    public const string REDIRECT_SHARES_PATH = 'other=2Ecom~=2Fa';

    public const string REDIRECT_APART = 'other=2Ecom~=2Fc';

    public const string TRANSLATION_MINE = '1~en';

    public const string TRANSLATION_SHARES_POST = '1~de';

    public const string TRANSLATION_SHARES_LOCALE = '2~en';

    public const string ENROLMENT_MINE = '1~1';

    public const string ENROLMENT_SHARES_POST = '1~2';

    public const string ENROLMENT_SHARES_TAG = '2~1';

    public const string LISTING_MINE = 'AB-123';

    public const string LISTING_APART = 'CD-456';

    public const string NOTE_MINE = 'AB-123~x';

    public const string NOTE_SHARES_LISTING = 'AB-123~y';

    public const string NOTE_SHARES_CODE = 'CD-456~x';

    public const string VAULT_MINE = 'zeta~north';

    public const string VAULT_SHARES_CIPHER = 'zeta~south';

    public const string VAULT_SHARES_ZONE = 'theta~north';

    public const string PLACARD_MINE = 'header';

    public const string PLACARD_APART = 'footer';

    public const string SIGIL_MINE = 'north~alpha';

    public const string SIGIL_SHARES_REALM = 'north~beta';

    public const string SIGIL_SHARES_MARK = 'south~alpha';

    public const string CHAIN_MINE = '1~1';

    public const string CHAIN_SHARES_SEAL = '1~2';

    public const string CHAIN_SHARES_LINK = '2~1';

    public static function create(?RecordingMiddleware $recorder = null): EntityManager
    {
        $entityManager = CompositeTargetRig::create($recorder);

        $posts = [1 => CompositeTargetRig::post($entityManager, 1), 2 => CompositeTargetRig::post($entityManager, 2), 3 => CompositeTargetRig::post($entityManager, 3)];
        $tags = [1 => CompositeTargetRig::tag($entityManager, 1), 2 => CompositeTargetRig::tag($entityManager, 2)];

        self::seedRedirects($entityManager, $posts, $tags);
        self::seedTranslations($entityManager, $posts);
        self::seedEnrolments($entityManager, $tags);
        self::seedListings($entityManager, $tags);
        self::seedVaults($entityManager, $tags);
        self::seedPlacards($entityManager, $tags);
        self::seedSigils($entityManager, $tags);
        self::seedChains($entityManager, $tags);

        $entityManager->flush();

        // The mapping cannot express half a composite foreign key, so the row a
        // candidate query has to keep is written past Doctrine.
        $entityManager->getConnection()->executeStatement(
            'INSERT INTO Marker (label, redirect_host, redirect_path) VALUES (?, ?, ?)',
            ['half an owner', 'example.com', null],
        );

        $entityManager->clear();

        return $entityManager;
    }

    public static function findRedirect(EntityManager $entityManager, string $host, string $path): Redirect
    {
        return CompositeTargetRig::findRedirect($entityManager, $host, $path);
    }

    public static function findTranslation(EntityManager $entityManager, int $postId, string $locale): PostTranslation
    {
        return CompositeTargetRig::findTranslation($entityManager, $postId, $locale);
    }

    public static function findEnrolment(EntityManager $entityManager, int $postId, int $tagId): Enrolment
    {
        return CompositeTargetRig::findEnrolment($entityManager, $postId, $tagId);
    }

    public static function findListing(EntityManager $entityManager, string $sku): Listing
    {
        $listing = $entityManager->find(Listing::class, new Sku($sku));

        return $listing instanceof Listing ? $listing : throw new LogicException(sprintf('The rig seeds no listing [%s].', $sku));
    }

    public static function findNote(EntityManager $entityManager, string $sku, string $code): ListingNote
    {
        $note = $entityManager->find(ListingNote::class, ['listing' => new Sku($sku), 'code' => $code]);

        return $note instanceof ListingNote ? $note : throw new LogicException(sprintf('The rig seeds no listing note [%s~%s].', $sku, $code));
    }

    public static function findVault(EntityManager $entityManager, string $cipher, string $zone): Vault
    {
        $vault = $entityManager->find(Vault::class, ['cipher' => new Cipher($cipher), 'zone' => $zone]);

        return $vault instanceof Vault ? $vault : throw new LogicException(sprintf('The rig seeds no vault [%s~%s].', $cipher, $zone));
    }

    public static function findPlacard(EntityManager $entityManager, Zone $zone): Placard
    {
        $placard = $entityManager->find(Placard::class, $zone);

        return $placard instanceof Placard ? $placard : throw new LogicException(sprintf('The rig seeds no placard [%s].', $zone->value));
    }

    public static function findSigil(EntityManager $entityManager, string $realm, string $mark): SigilChild
    {
        $sigil = $entityManager->find(Sigil::class, ['realm' => $realm, 'mark' => $mark]);

        return $sigil instanceof SigilChild ? $sigil : throw new LogicException(sprintf('The rig seeds no sigil [%s~%s].', $realm, $mark));
    }

    /**
     * The seal is read first on purpose: a chained derived identity is hydrated
     * through its own identifier association, which Doctrine cannot resolve
     * against a cold identity map.
     */
    public static function findChain(EntityManager $entityManager, int $postId, int $link): Chain
    {
        CompositeTargetRig::findSeal($entityManager, $postId);

        $chain = $entityManager->createQuery(sprintf('SELECT chain FROM %s chain WHERE chain.seal = :seal AND chain.link = :link', Chain::class))
            ->setParameter('seal', $postId)
            ->setParameter('link', $link)
            ->getOneOrNullResult();

        return $chain instanceof Chain ? $chain : throw new LogicException(sprintf('The rig seeds no chain [%d~%d].', $postId, $link));
    }

    /**
     * @param array<int, Post> $posts
     * @param array<int, Tag>  $tags
     */
    private static function seedRedirects(EntityManager $entityManager, array $posts, array $tags): void
    {
        $mine = CompositeTargetRig::findRedirect($entityManager, 'example.com', '/a');
        $sharesHost = CompositeTargetRig::findRedirect($entityManager, 'example.com', '/b');
        $apart = CompositeTargetRig::findRedirect($entityManager, 'other.com', '/c');

        $sharesPath = new Redirect();
        $sharesPath->setHost('other.com');
        $sharesPath->setPath('/a');
        $sharesPath->setLabel('other.com/a');

        $entityManager->persist($sharesPath);

        self::marker($entityManager, 'mine', redirect: $mine);
        self::marker($entityManager, 'shares host', redirect: $sharesHost);
        self::marker($entityManager, 'shares path', redirect: $sharesPath);

        $mine->addPost($posts[1]);
        $mine->addPost($posts[2]);

        $sharesPath->addPost($posts[3]);

        $tags[1]->addRedirect($mine);
        $tags[2]->addRedirect($sharesHost);
        $tags[1]->addRedirect($sharesPath);
        $tags[2]->addRedirect($sharesPath);

        $sharesHost->setParent($mine);
        $sharesPath->setParent($mine);
        $apart->setParent($sharesHost);

        $first = new MixedFirstMarker();
        $first->setLabel('mixed first');
        $first->setRedirect($mine);

        $entityManager->persist($first);

        $last = new MixedLastMarker();
        $last->setLabel('mixed last');
        $last->setRedirect($mine);

        $entityManager->persist($last);
    }

    /**
     * @param array<int, Post> $posts
     */
    private static function seedTranslations(EntityManager $entityManager, array $posts): void
    {
        $mine = CompositeTargetRig::findTranslation($entityManager, 1, 'en');
        $sharesPost = CompositeTargetRig::findTranslation($entityManager, 1, 'de');

        $sharesLocale = new PostTranslation();
        $sharesLocale->setPost($posts[2]);
        $sharesLocale->setLocale('en');
        $sharesLocale->setTitle('en of two');

        $entityManager->persist($sharesLocale);

        self::marker($entityManager, 'mine', translation: $mine);
        self::marker($entityManager, 'shares post', translation: $sharesPost);
        self::marker($entityManager, 'shares locale', translation: $sharesLocale);

        $mine->addPost($posts[1]);
        $mine->addPost($posts[2]);

        $sharesLocale->addPost($posts[3]);
    }

    /**
     * @param array<int, Tag> $tags
     */
    private static function seedEnrolments(EntityManager $entityManager, array $tags): void
    {
        $mine = CompositeTargetRig::findEnrolment($entityManager, 1, 1);
        $sharesPost = CompositeTargetRig::findEnrolment($entityManager, 1, 2);
        $sharesTag = CompositeTargetRig::findEnrolment($entityManager, 2, 1);

        self::marker($entityManager, 'mine', enrolment: $mine);
        self::marker($entityManager, 'shares post', enrolment: $sharesPost);
        self::marker($entityManager, 'shares tag', enrolment: $sharesTag);

        $mine->addTopic($tags[1]);
        $mine->addTopic($tags[2]);

        $sharesPost->addTopic($tags[1]);
        $sharesTag->addTopic($tags[2]);
    }

    /**
     * @param array<int, Tag> $tags
     */
    private static function seedListings(EntityManager $entityManager, array $tags): void
    {
        $mine = new Listing();
        $mine->setSku(new Sku(self::LISTING_MINE));
        $mine->setTitle('mine');
        $mine->addTopic($tags[1]);

        $entityManager->persist($mine);

        $apart = new Listing();
        $apart->setSku(new Sku(self::LISTING_APART));
        $apart->setTitle('apart');
        $apart->addTopic($tags[2]);

        $entityManager->persist($apart);

        self::slip($entityManager, 'mine', listing: $mine);
        self::slip($entityManager, 'apart', listing: $apart);
        self::slip($entityManager, 'loose');

        $noteMine = self::note($entityManager, $mine, 'x', [$tags[1]]);
        $noteSharesListing = self::note($entityManager, $mine, 'y', [$tags[2]]);
        $noteSharesCode = self::note($entityManager, $apart, 'x', [$tags[1], $tags[2]]);

        self::slip($entityManager, 'note mine', note: $noteMine);
        self::slip($entityManager, 'note shares listing', note: $noteSharesListing);
        self::slip($entityManager, 'note shares code', note: $noteSharesCode);
    }

    /**
     * @param array<int, Tag> $tags
     */
    private static function seedVaults(EntityManager $entityManager, array $tags): void
    {
        $mine = self::vault($entityManager, 'zeta', 'north', [$tags[1]]);
        self::vault($entityManager, 'zeta', 'south', [$tags[2]]);
        self::vault($entityManager, 'theta', 'north', [$tags[1], $tags[2]]);

        self::slip($entityManager, 'vault mine', vault: $mine);
        self::slip($entityManager, 'vault loose');
    }

    /**
     * @param array<int, Tag> $tags
     */
    private static function seedPlacards(EntityManager $entityManager, array $tags): void
    {
        $mine = new Placard();
        $mine->setZone(Zone::Header);
        $mine->setLabel('mine');
        $mine->addTopic($tags[1]);

        $entityManager->persist($mine);

        $apart = new Placard();
        $apart->setZone(Zone::Footer);
        $apart->setLabel('apart');
        $apart->addTopic($tags[2]);

        $entityManager->persist($apart);

        self::slip($entityManager, 'placard mine', placard: $mine);
        self::slip($entityManager, 'placard apart', placard: $apart);
    }

    /**
     * @param array<int, Tag> $tags
     */
    private static function seedSigils(EntityManager $entityManager, array $tags): void
    {
        $mine = self::sigil($entityManager, 'north', 'alpha', [$tags[1], $tags[2]]);
        $sharesRealm = self::sigil($entityManager, 'north', 'beta', [$tags[1]]);
        $sharesMark = self::sigil($entityManager, 'south', 'alpha', [$tags[2]]);

        self::slip($entityManager, 'sigil mine', sigil: $mine);
        self::slip($entityManager, 'sigil shares realm', sigil: $sharesRealm);
        self::slip($entityManager, 'sigil shares mark', sigil: $sharesMark);
    }

    /**
     * @param array<int, Tag> $tags
     */
    private static function seedChains(EntityManager $entityManager, array $tags): void
    {
        $first = CompositeTargetRig::findSeal($entityManager, 1);
        $second = CompositeTargetRig::findSeal($entityManager, 2);

        self::chain($entityManager, $first, 1, [$tags[1]]);
        self::chain($entityManager, $first, 2, [$tags[2]]);
        self::chain($entityManager, $second, 1, [$tags[1], $tags[2]]);
    }

    /**
     * @param list<Tag> $topics
     */
    private static function note(EntityManager $entityManager, Listing $listing, string $code, array $topics): ListingNote
    {
        $note = new ListingNote();
        $note->setListing($listing);
        $note->setCode($code);
        $note->setBody($code);

        foreach ($topics as $topic) {
            $note->addTopic($topic);
        }

        $entityManager->persist($note);

        return $note;
    }

    /**
     * @param list<Tag> $topics
     */
    private static function vault(EntityManager $entityManager, string $cipher, string $zone, array $topics): Vault
    {
        $vault = new Vault();
        $vault->setCipher(new Cipher($cipher));
        $vault->setZone($zone);
        $vault->setLabel($cipher . '/' . $zone);

        foreach ($topics as $topic) {
            $vault->addTopic($topic);
        }

        $entityManager->persist($vault);

        return $vault;
    }

    /**
     * @param list<Tag> $topics
     */
    private static function sigil(EntityManager $entityManager, string $realm, string $mark, array $topics): SigilChild
    {
        $sigil = new SigilChild();
        $sigil->setRealm($realm);
        $sigil->setMark($mark);
        $sigil->setLabel($realm . '/' . $mark);

        foreach ($topics as $topic) {
            $sigil->addTopic($topic);
        }

        $entityManager->persist($sigil);

        return $sigil;
    }

    /**
     * @param list<Tag> $topics
     */
    private static function chain(EntityManager $entityManager, PostSeal $seal, int $link, array $topics): Chain
    {
        $chain = new Chain();
        $chain->setSeal($seal);
        $chain->setLink($link);
        $chain->setLabel($seal->getNote() . '/' . $link);

        foreach ($topics as $topic) {
            $chain->addTopic($topic);
        }

        $entityManager->persist($chain);

        return $chain;
    }

    private static function marker(EntityManager $entityManager, string $label, ?Redirect $redirect = null, ?PostTranslation $translation = null, ?Enrolment $enrolment = null): Marker
    {
        $marker = new Marker();
        $marker->setLabel($label);
        $marker->setRedirect($redirect);
        $marker->setTranslation($translation);
        $marker->setEnrolment($enrolment);

        $entityManager->persist($marker);

        return $marker;
    }

    private static function slip(EntityManager $entityManager, string $label, ?Listing $listing = null, ?ListingNote $note = null, ?Vault $vault = null, ?Placard $placard = null, ?Sigil $sigil = null): Slip
    {
        $slip = new Slip();
        $slip->setLabel($label);
        $slip->setListing($listing);
        $slip->setNote($note);
        $slip->setVault($vault);
        $slip->setPlacard($placard);
        $slip->setSigil($sigil);

        $entityManager->persist($slip);

        return $slip;
    }
}
