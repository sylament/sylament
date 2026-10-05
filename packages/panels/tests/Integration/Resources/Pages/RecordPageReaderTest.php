<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Watermark;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateWatermark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditWatermark;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\State\AssociationReader;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The record pages' key reads ask the form's own reader, rebound to the
 * manager the page writes through. The form's reader is replaced by a subclass
 * whose constructor takes an argument the service's does not — so only a
 * clone can rebind it — and which logs every key question with the reader
 * that answered. The create cases hand the page a second manager, over a
 * database of its own, through its own setRecordPersistence(): the split the
 * binding exists for. In-process on one host object, because the spy sits on
 * the host's cached form schema and a live request would build that schema
 * again. Every expectation is a platform-neutral string.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function pageReaderClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $request = new Request();
    $request->attributes->set('_sylament_panel', 'make');
    $request->setSession(test()->getContainer()->get('session.factory')->createSession());

    test()->getContainer()->get(RequestStack::class)->push($request);

    return [$client, test()->getContainer()->get(EntityManagerInterface::class)];
}

/**
 * A second manager over a database of its own, holding the pair arches/laid.
 */
function pageReaderAnnex(): EntityManager
{
    $annex = EntityManagerFactory::create();

    EntityManagerFactory::seed($annex, new Watermark('arches', 'laid')->setNote('Annex'));

    return $annex;
}

/**
 * @return array{AssociationReader, ArrayObject<int, array{string, AssociationReader}>}
 */
function pageReaderSpy(EntityManagerInterface $entityManager): array
{
    /** @var ArrayObject<int, array{string, AssociationReader}> $log */
    $log = new ArrayObject();

    $spy = new class ($log, $entityManager) extends AssociationReader {
        /**
         * @param ArrayObject<int, array{string, AssociationReader}> $log
         */
        public function __construct(private readonly ArrayObject $log, EntityManagerInterface $entityManager)
        {
            parent::__construct($entityManager);
        }

        public function revertIdentifierChanges(object $record): array
        {
            $this->log[] = [__FUNCTION__, $this];

            return parent::revertIdentifierChanges($record);
        }

        public function findBlankAssignedIdentifiers(object $record): array
        {
            $this->log[] = [__FUNCTION__, $this];

            return parent::findBlankAssignedIdentifiers($record);
        }

        public function findTakenIdentifiers(object $record): array
        {
            $this->log[] = [__FUNCTION__, $this];

            return parent::findTakenIdentifiers($record);
        }

        public function hasBlankAssignedIdentifier(object $record): bool
        {
            $this->log[] = [__FUNCTION__, $this];

            return parent::hasBlankAssignedIdentifier($record);
        }
    };

    return [$spy, $log];
}

/**
 * Each logged question's name, once every reader that answered it is a clone
 * of the spy — its class, never the spy itself — bound to the manager the page
 * writes through.
 *
 * @param ArrayObject<int, array{string, AssociationReader}> $log
 *
 * @return list<string>
 */
function pageReaderQuestions(ArrayObject $log, AssociationReader $spy, EntityManagerInterface $writeManager): array
{
    $boundManager = new ReflectionProperty(AssociationReader::class, 'entityManager');

    foreach ($log as [, $reader]) {
        expect($reader)->toBeInstanceOf($spy::class)
            ->not->toBe($spy)
            ->and($boundManager->getValue($reader))->toBe($writeManager);
    }

    return array_column($log->getArrayCopy(), 0);
}

/**
 * @return array<string, list<string>>
 */
function pageReaderRefusal(CreateWatermark $host): array
{
    try {
        $host->create();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    test()->fail('Expected the create to be refused with a ValidationException.');
}

it("asks the form's reader, rebound to the manager the page writes through, for a create on a key only that manager holds", function (): void {
    [$client, $default] = pageReaderClient();
    $annex = pageReaderAnnex();
    [$spy, $log] = pageReaderSpy($default);

    $host = $this->createLiveComponent(CreateWatermark::class, ['panelId' => 'make'], client: $client)->component();
    assert($host instanceof CreateWatermark);

    $host->setRecordPersistence($annex);
    $host->getSchema('form')->setAssociationReader($spy);
    $host->data = ['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Duplicate'];

    expect(pageReaderRefusal($host))->toBe([
        'data.mill' => ['This value is already used.'],
        'data.pattern' => ['This value is already used.'],
    ])
        ->and(pageReaderQuestions($log, $spy, $annex))->toContain('findTakenIdentifiers')
        ->and($annex->getConnection()->fetchAllAssociative('SELECT mill, pattern, note FROM Watermark'))->toBe([['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Annex']])
        ->and($default->getConnection()->fetchAllAssociative('SELECT mill FROM Watermark'))->toBe([]);
});

it("asks the form's reader, rebound to the page's manager, for the record check and the blank-key read of a create it saves", function (): void {
    [$client, $default] = pageReaderClient();
    $annex = pageReaderAnnex();
    [$spy, $log] = pageReaderSpy($default);

    $host = $this->createLiveComponent(CreateWatermark::class, ['panelId' => 'make'], client: $client)->component();
    assert($host instanceof CreateWatermark);

    $host->setRecordPersistence($annex);
    $host->getSchema('form')->setAssociationReader($spy);
    $host->data = ['mill' => 'fabriano', 'pattern' => 'wove', 'note' => 'Annexed'];
    $host->create();

    expect(pageReaderQuestions($log, $spy, $annex))->toContain('findTakenIdentifiers', 'hasBlankAssignedIdentifier')
        ->and($annex->getConnection()->fetchAllAssociative('SELECT mill, pattern, note FROM Watermark ORDER BY mill'))->toBe([
            ['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Annex'],
            ['mill' => 'fabriano', 'pattern' => 'wove', 'note' => 'Annexed'],
        ])
        ->and($default->getConnection()->fetchAllAssociative('SELECT mill FROM Watermark'))->toBe([]);
});

it("asks the form's reader, rebound to the page's manager, for an edit", function (): void {
    [$client, $default] = pageReaderClient();
    EntityManagerFactory::seed($default, new Watermark('arches', 'laid')->setNote('Stored'));
    [$spy, $log] = pageReaderSpy($default);

    $host = $this->createLiveComponent(EditWatermark::class, ['record' => 'arches~laid', 'panelId' => 'make'], client: $client)->component();
    assert($host instanceof EditWatermark);

    $host->getSchema('form')->setAssociationReader($spy);
    $host->data = ['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Edited'];
    $host->save();

    expect(pageReaderQuestions($log, $spy, $default))->toContain('revertIdentifierChanges', 'findTakenIdentifiers')
        ->and($default->getConnection()->fetchAllAssociative('SELECT mill, pattern, note FROM Watermark'))->toBe([['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Edited']]);
});
