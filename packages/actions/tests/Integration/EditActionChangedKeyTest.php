<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Membership;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Actions\Tests\Fixtures\Twig\Components\CupboardKeyHost;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * An edit whose before() hook moves a key half the modal never shows, so the
 * refusal names no field of the form and lands on the mounted schema's own
 * path. A real live request that keeps the exception as its 422 response, so
 * the case reads what the modal re-renders, with the reboot disabled so the
 * record the request refused is the one the case holds.
 */

/**
 * @param class-string $class
 */
function editChangedKeyOriginal(string $class): object
{
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $record = match ($class) {
        Redirect::class => (static function (): Redirect {
            $redirect = new Redirect();
            $redirect->setHost('example.com');
            $redirect->setPath('/old');
            $redirect->setTarget('/new');

            return $redirect;
        })(),
        Membership::class => (static function () use ($entityManager): Membership {
            $note = new Note();
            $note->setTitle('owner');

            $entityManager->persist($note);

            $membership = new Membership();
            $membership->setNote($note);
            $membership->setRole('editor');
            $membership->setLabel('Editors');

            return $membership;
        })(),
        Ticket::class => (static function (): Ticket {
            $ticket = new Ticket();
            $ticket->setCode('T-1');
            $ticket->setTitle('Alpha');

            return $ticket;
        })(),
    };

    $entityManager->persist($record);
    $entityManager->flush();

    return $record;
}

it('refuses an edit whose before() hook moved a key half, above the form, putting the key back', function (string $class, string $recordKey, string $field, string $value, Closure $keyOf, string $keptKey, string $rows, array $stored): void {
    $record = editChangedKeyOriginal($class);

    /** @var KernelBrowser $client */
    $client = $this::getContainer()->get('test.client');
    $client->disableReboot();

    $component = $this->createLiveComponent(CupboardKeyHost::class, ['recordClass' => $class], client: $client)
        ->call('mountAction', ['name' => 'editMoved', 'arguments' => [], 'context' => ['recordKey' => $recordKey]])
        ->set(sprintf('mountedActionsData.0.%s', $field), $value);

    $client->catchExceptions(true);
    $component->call('callMountedAction');

    $crawler = new Crawler((string) $component->response()->getContent());

    expect($component->response()->getStatusCode())->toBe(422)
        ->and($crawler->filter('.sy-modal [data-record-errors] li')->each(static fn (Crawler $node): string => $node->text()))->toBe(['This value cannot be changed.'])
        ->and($keyOf($record))->toBe($keptKey)
        ->and($this::getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchAllAssociative($rows))->toBe($stored);
})->with([
    'a scalar pair' => [Redirect::class, 'example=2Ecom~=2Fold', 'target', '/newer', static fn (Redirect $redirect): string => $redirect->getPath(), '/old', 'SELECT host, path, target FROM Redirect', [['host' => 'example.com', 'path' => '/old', 'target' => '/new']]],
    'an association beside a scalar' => [Membership::class, '1~editor', 'label', 'Staff', static fn (Membership $membership): string => $membership->getRole(), 'editor', 'SELECT note_id, role, label FROM Membership', [['note_id' => 1, 'role' => 'editor', 'label' => 'Editors']]],
    'a single string key' => [Ticket::class, 'T-1', 'title', 'Beta', static fn (Ticket $ticket): string => $ticket->getCode(), 'T-1', 'SELECT code, title FROM Ticket', [['code' => 'T-1', 'title' => 'Alpha']]],
]);
