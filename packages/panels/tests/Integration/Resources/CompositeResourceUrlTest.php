<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Edition;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\ColophonResource;
use Sylament\Panels\Tests\Fixtures\Resources\EditionResource;
use Sylament\Panels\Tests\Fixtures\Resources\FlatStampResource;
use Sylament\Panels\Tests\Fixtures\Resources\ImprintResource;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditImprint;
use Sylament\Panels\Tests\Fixtures\Resources\StampResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Every URL here is written as a literal. Asking the codec for the expected key
 * would pass against any grammar at all, which is the one thing these cases
 * exist to hold still.
 *
 * The fixtures sit on the `key` panel rather than the default one, so every
 * call names its panel: a resource read outside a kernel request resolves the
 * default panel, which carries none of them.
 */

/**
 * @param class-string<Resource> $class
 */
function compositeUrlResource(string $class): Resource
{
    $registry = test()->getContainer()->get(ResourceRegistry::class);

    expect($registry)->toBeInstanceOf(ResourceRegistry::class);
    assert($registry instanceof ResourceRegistry);

    return $registry->get($class);
}

function compositeUrlEntityManager(): EntityManagerInterface
{
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    expect($entityManager)->toBeInstanceOf(EntityManagerInterface::class);
    assert($entityManager instanceof EntityManagerInterface);

    return $entityManager;
}

/**
 * One record of every identifier flavour, read back after the seed cleared the
 * identity map — so each key is minted from the value the record hydrates
 * rather than the value the test handed it, which is the whole of the
 * converting code type's point.
 *
 * @return array{Catalogue, Imprint, Stamp, Colophon, Edition}
 */
function compositeUrlRecords(EntityManagerInterface $entityManager): array
{
    $catalogue = new Catalogue()->setTitle('First');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        new Imprint()->setHouse('www.example.com')->setYear('/blog 2026')->setLabel('Escaped')->setCatalogue($catalogue),
        new Stamp()->setCatalogue($catalogue)->setCode('A/B C')->setCaption('Alpha'),
        new Colophon()->setCatalogue($catalogue)->setNote('Note'),
        new Edition()->setId(5)->setLocale('en-GB')->setTitle('Fifth'),
    );

    $seeded = $entityManager->getRepository(Catalogue::class)->findOneBy(['title' => 'First']);
    $imprint = $entityManager->getRepository(Imprint::class)->findOneBy(['label' => 'Escaped']);
    $stamp = $entityManager->getRepository(Stamp::class)->findOneBy(['caption' => 'Alpha']);
    $colophon = $entityManager->getRepository(Colophon::class)->findOneBy(['note' => 'Note']);
    $edition = $entityManager->getRepository(Edition::class)->findOneBy(['title' => 'Fifth']);

    expect($seeded)->toBeInstanceOf(Catalogue::class)
        ->and($seeded?->getId())->toBe(1)
        ->and($imprint)->toBeInstanceOf(Imprint::class)
        ->and($stamp)->toBeInstanceOf(Stamp::class)
        // The column holds `a/b c`; a key is minted from the PHP value, so the
        // literals below carry the upper-case spelling.
        ->and($stamp?->getCode())->toBe('A/B C')
        ->and($colophon)->toBeInstanceOf(Colophon::class)
        ->and($edition)->toBeInstanceOf(Edition::class);

    assert($seeded instanceof Catalogue);
    assert($imprint instanceof Imprint);
    assert($stamp instanceof Stamp);
    assert($colophon instanceof Colophon);
    assert($edition instanceof Edition);

    return [$seeded, $imprint, $stamp, $colophon, $edition];
}

/**
 * A page read outside a kernel request resolves the default panel, so a case
 * reading one in-process stands a request carrying this panel up first — the
 * page's own prop is what restores it on a live request in a browser.
 */
function compositeUrlPushKeyPanelRequest(): void
{
    $request = new Request();
    $request->attributes->set('_sylament_panel', 'key');

    test()->getContainer()->get(RequestStack::class)->push($request);
}

it('keys a scalar-pair record passed as an object at the record slot', function (): void {
    [, $imprint, , , $edition] = compositeUrlRecords(compositeUrlEntityManager());

    expect(compositeUrlResource(ImprintResource::class)->getUrl('edit', ['record' => $imprint], isAbsolute: false, panel: 'key'))
        ->toBe('/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/edit')
        // The pair whose first field is named `id`: the key is still both
        // fields, which is what the mounted-frame mismatch is about elsewhere.
        ->and(compositeUrlResource(EditionResource::class)->getUrl('edit', ['record' => $edition], isAbsolute: false, panel: 'key'))
        ->toBe('/key/editions/5~en-GB/edit');
});

it('keys a derived-identity record at the record slot', function (): void {
    [, , $stamp] = compositeUrlRecords(compositeUrlEntityManager());

    expect(compositeUrlResource(FlatStampResource::class)->getUrl('edit', ['record' => $stamp], isAbsolute: false, panel: 'key'))
        ->toBe('/key/flat-stamps/1~A=2FB=20C/edit');
});

it('keys a single derived identity record at the record slot', function (): void {
    [, , , $colophon] = compositeUrlRecords(compositeUrlEntityManager());

    // One association and nothing else: Doctrine calls the identifier
    // non-composite, so the key is the target's own key verbatim.
    expect(compositeUrlResource(ColophonResource::class)->getUrl('edit', ['record' => $colophon], isAbsolute: false, panel: 'key'))
        ->toBe('/key/colophons/1/edit');
});

it('keys a nested composite record and the ancestor it is passed beside', function (): void {
    [$catalogue, , $stamp] = compositeUrlRecords(compositeUrlEntityManager());

    $resource = compositeUrlResource(StampResource::class);

    expect($resource->getUrl('edit', ['record' => $stamp, 'catalogue' => $catalogue], isAbsolute: false, panel: 'key'))
        ->toBe('/key/catalogues/1/stamps/1~A=2FB=20C/edit')
        // The ancestor the caller left out is walked off the record itself —
        // here through the very association that is half the record's key.
        ->and($resource->getUrl('edit', ['record' => $stamp], isAbsolute: false, panel: 'key', shouldGuessMissingParameters: true))
        ->toBe('/key/catalogues/1/stamps/1~A=2FB=20C/edit');
});

/**
 * Green before the seam too: a nested resource's listing is its parent's page,
 * so the only record an index URL keys is the single-identifier parent. The
 * case is here so the seam cannot start keying the child into it.
 */
it('sends a nested composite index url to the parent page keyed by the relationship', function (): void {
    [$catalogue] = compositeUrlRecords(compositeUrlEntityManager());

    expect(compositeUrlResource(StampResource::class)->getIndexUrl(['catalogue' => $catalogue], isAbsolute: false, panel: 'key'))
        ->toBe('/key/catalogues/1/stamps');
});

it('mints a composite url for the panel the caller names rather than the default one', function (): void {
    [, $imprint] = compositeUrlRecords(compositeUrlEntityManager());

    $panels = test()->getContainer()->get(PanelRegistry::class);

    expect($panels)->toBeInstanceOf(PanelRegistry::class);
    assert($panels instanceof PanelRegistry);

    $key = $panels->get('key');

    expect($key)->toBeInstanceOf(Panel::class);
    assert($key instanceof Panel);

    $resource = compositeUrlResource(ImprintResource::class);

    expect($resource->getUrl('edit', ['record' => $imprint], isAbsolute: false, panel: $key))
        ->toBe('/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/edit')
        ->and($resource->getUrl('edit', ['record' => $imprint], isAbsolute: false, panel: 'key'))
        ->toBe('/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/edit')
        // The record is keyed before the route name is composed, so naming no
        // panel outside a request reaches the *default* panel's route table —
        // which carries no imprints resource at all.
        ->and(fn (): string => $resource->getUrl('edit', ['record' => $imprint], isAbsolute: false))
        ->toThrow(RouteNotFoundException::class, 'sylament.admin.resources.imprints.edit');
});

it('trails a composite record crumb under the url that shows it', function (): void {
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    compositeUrlRecords(compositeUrlEntityManager());

    $host = $this->createLiveComponent(EditImprint::class, [
        'record' => 'www=2Eexample=2Ecom~=2Fblog=202026',
        'panelId' => 'key',
    ], client: $client)->component();

    compositeUrlPushKeyPanelRequest();

    // The trail is keyed *by URL*: a string key is the crumb's link and an int
    // key is plain text, so the escaped key has to survive as an array key.
    expect($host->getBreadcrumbs())->toBe([
        'http://localhost/key/imprints' => 'Imprints',
        'http://localhost/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/edit' => 'Escaped',
        0 => 'Edit',
    ]);
});
