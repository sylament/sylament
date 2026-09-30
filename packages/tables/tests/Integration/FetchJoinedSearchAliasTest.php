<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ShortcutHit;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Twig\Error\RuntimeError;

uses(InteractsWithLiveComponents::class);

/*
 * A search whose column begins with an alias the table query fetch-joins as a
 * collection would narrow each row's collection instead of choosing rows, with
 * no error anywhere, so the first search refuses it by name. The same join
 * under another alias routes the column into the correlated EXISTS and works.
 */

/**
 * The first artist's two shortcuts: `Almanac` with one `curl/8.7` hit, and
 * `Zenith` with a `curl/8.7` hit and a `Mozilla/5.0` one.
 *
 * @return array{KernelBrowser}
 */
function fetchJoinedAliasSeededClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $ada = new Artist(1, 'Ada');

    $shortcuts = [];

    foreach ([['plain', 'about', 'Zenith'], ['www.example.com', '/blog/2026/a b', 'Almanac']] as [$host, $path, $label]) {
        $shortcut = new Shortcut();
        $shortcut->setHost($host);
        $shortcut->setPath($path);
        $shortcut->setLabel($label);
        $shortcuts[] = $shortcut;
    }

    [$zenith, $almanac] = $shortcuts;

    foreach ([$ada, $zenith, $almanac, new ShortcutHit(1, 'curl/8.7', 5, $zenith), new ShortcutHit(2, 'Mozilla/5.0', 9, $zenith), new ShortcutHit(3, 'curl/8.7', 3, $almanac)] as $entity) {
        $entityManager->persist($entity);
    }

    $ada->addShortcut($zenith)->addShortcut($almanac);

    $entityManager->flush();
    $entityManager->clear();

    return [$client];
}

it("refuses the first search when the fetch join takes the searchable column's head as its alias", function (): void {
    [$client] = fetchJoinedAliasSeededClient();

    expect(fn (): mixed => $this->createLiveComponent('ArtistShortcutsTable', ['joinsHits' => true, 'hitsAlias' => 'hits'], client: $client)->set('tableSearch', 'curl'))
        ->toThrow(RuntimeError::class, 'The searchable column [hits.agent] begins with [hits]');
});

it("renders the fetch join under the column's head until something is searched", function (): void {
    [$client] = fetchJoinedAliasSeededClient();

    expect((string) $this->createLiveComponent('ArtistShortcutsTable', ['joinsHits' => true, 'hitsAlias' => 'hits'], client: $client)->render())
        ->toContain('curl/8.7');
});

it('searches through the fetch join under another alias', function (): void {
    [$client] = fetchJoinedAliasSeededClient();

    expect((string) $this->createLiveComponent('ArtistShortcutsTable', ['joinsHits' => true], client: $client)->set('tableSearch', 'Mozilla')->render())
        ->toContain('Zenith');
});
