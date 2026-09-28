<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\ORM\EntityManager;
use LogicException;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Binder;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Callsign;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Carton;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ChilledCarton;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Pennant;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ribbon;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Sheet;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Shortlink;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Sigil;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Slate;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Spine;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Testing\Doctrine\RecordingMiddleware;

/**
 * {@see CompositeOwnerRig} with one stored record of each assigned-key shape
 * the two record-key rules read that it does not seed: a single string key, a
 * guid beside a bigint, a root record of each inheritance hierarchy — so a new
 * subclass record can collide with it — the first binder with one child on
 * every cascading edge, and a call sign carrying constraints of its own. The
 * binders are 1 and 2 and the tags 1 and 2; every key below is a literal.
 */
final class IdentifierGuardRig
{
    public const string SHORTLINK = 'go';

    public const string TICKET_ID = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

    public const int TICKET_SERIAL = 7;

    public const string SIGIL_REALM = 'west';

    public const string SIGIL_MARK = 'gamma';

    public const string CARTON = 'C-1';

    public const string CHILLED_CARTON = 'C-2';

    public const string SHEET = 'intro';

    public const string SLATE = 'S-1';

    public const string SPINE = 'SP-1';

    public const string CALLSIGN = 'OK1';

    public static function create(?RecordingMiddleware $recorder = null): EntityManager
    {
        $entityManager = CompositeOwnerRig::create($recorder);

        $shortlink = new Shortlink();
        $shortlink->setCode(self::SHORTLINK);
        $shortlink->setTarget('/somewhere');

        $ticket = new Ticket();
        $ticket->setId(self::TICKET_ID);
        $ticket->setSerial(self::TICKET_SERIAL);
        $ticket->setSubject('ticketed');

        $sigil = new Sigil();
        $sigil->setRealm(self::SIGIL_REALM);
        $sigil->setMark(self::SIGIL_MARK);
        $sigil->setLabel('a root sigil');

        $carton = new Carton();
        $carton->setCode(self::CARTON);
        $carton->setLabel('a root carton');

        $chilled = new ChilledCarton();
        $chilled->setCode(self::CHILLED_CARTON);
        $chilled->setLabel('a chilled carton');

        $callsign = new Callsign();
        $callsign->setCode(self::CALLSIGN);
        $callsign->setLabel('Station one');

        $tag = CompositeTargetRig::tag($entityManager, 1);

        $first = new Binder();
        $first->setTitle('First');

        $sheet = new Sheet();
        $sheet->setCode(self::SHEET);
        $sheet->setTitle('Introduction');

        $first->addSheet($sheet);

        $slate = new Slate();
        $slate->setCode(self::SLATE);
        $slate->setLabel('stored slate');

        $first->addSlate($slate);

        $ribbon = new Ribbon();
        $ribbon->setTag($tag);
        $ribbon->setLabel('stored ribbon');

        $first->addRibbon($ribbon);

        $pennant = new Pennant();
        $pennant->setTag($tag);
        $pennant->setLabel('stored pennant');

        $first->addPennant($pennant);

        $spine = new Spine();
        $spine->setCode(self::SPINE);
        $spine->setLabel('stored spine');

        $first->setSpine($spine);

        $second = new Binder();
        $second->setTitle('Second');

        EntityManagerFactory::seed($entityManager, $shortlink, $ticket, $sigil, $carton, $chilled, $callsign, $first, $second);

        return $entityManager;
    }

    public static function binder(EntityManager $entityManager, int $id): Binder
    {
        $binder = $entityManager->find(Binder::class, $id);

        return $binder instanceof Binder ? $binder : throw new LogicException(sprintf('The rig seeds no binder [%d].', $id));
    }

    public static function shortlink(EntityManager $entityManager): Shortlink
    {
        $shortlink = $entityManager->find(Shortlink::class, self::SHORTLINK);

        return $shortlink instanceof Shortlink ? $shortlink : throw new LogicException('The rig seeds one shortlink.');
    }

    public static function ticket(EntityManager $entityManager): Ticket
    {
        $ticket = $entityManager->find(Ticket::class, ['id' => self::TICKET_ID, 'serial' => self::TICKET_SERIAL]);

        return $ticket instanceof Ticket ? $ticket : throw new LogicException('The rig seeds one ticket.');
    }

    public static function callsign(EntityManager $entityManager): Callsign
    {
        $callsign = $entityManager->find(Callsign::class, self::CALLSIGN);

        return $callsign instanceof Callsign ? $callsign : throw new LogicException('The rig seeds one call sign.');
    }
}
