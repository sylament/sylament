<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Sylament\Actions\Action;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RecordWriteHost;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The view modal is the edit modal with nothing to submit: the same resolver
 * answers the schema, the action disables it, and the footer keeps only a
 * Close button. The state-carrying cases run in-process on one host object for
 * the reason `EditActionTest` records; no case needs a session, because a view
 * never reaches `callMountedAction()` and so never toasts.
 */

function viewedNote(string $title, ?string $body = null): Note
{
    $note = new Note();
    $note->setTitle($title);
    $note->setBody($body);

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $entityManager->persist($note);
    $entityManager->flush();

    return $note;
}

/**
 * The one `<input>` a schema field rendered for `$name`, so a case can read its
 * attributes without matching the word anywhere else on the page. A miss throws
 * rather than answering `''`, which every `not->toContain()` would pass.
 */
function renderedInput(string $html, string $name): string
{
    preg_match('/<input[^>]+name="' . $name . '"[^>]*>/', $html, $matches);

    return $matches[0] ?? throw new RuntimeException("The render carries no input named [{$name}].");
}

it('fills the modal from the record and disables the schema', function (): void {
    $note = viewedNote('Alpha', 'the body');

    $host = $this->createLiveComponent(RecordWriteHost::class)->component();
    $host->mountAction('view', [], ['recordKey' => (string) $note->getId()]);

    expect($host->getMountedActionData(0))->toBe(['title' => 'Alpha', 'body' => 'the body'])
        ->and($host->getMountedActionSchema()?->isDisabled())->toBeTrue()
        ->and($host->getMountedAction()?->getModalHeading())->toBe('View Alpha')
        ->and($host->mountedActionShouldOpenModal())->toBeTrue();
});

it('offers a Close button and no submit', function (): void {
    $note = viewedNote('Alpha');

    $host = $this->createLiveComponent(RecordWriteHost::class)->component();
    $host->mountAction('view', [], ['recordKey' => (string) $note->getId()]);

    $footerActions = $host->getMountedAction()?->getVisibleModalFooterActions() ?? [];

    expect(array_keys($footerActions))->toBe(['cancel'])
        ->and($footerActions['cancel'])->toBeInstanceOf(Action::class)
        ->and($footerActions['cancel']->getLabel())->toBe('Close');
});

it('renders its fields disabled where the edit modal renders them writable', function (): void {
    $note = viewedNote('Alpha', 'the body');

    // The fixture database lives in memory on a container service, so a kernel
    // reboot between the mount and the render would hand the second request a
    // freshly-migrated, empty one and the record would no longer resolve.
    /** @var KernelBrowser $client */
    $client = $this::getContainer()->get('test.client');
    $client->disableReboot();

    $viewHtml = (string) $this->createLiveComponent(RecordWriteHost::class, client: $client)
        ->call('mountAction', ['name' => 'view', 'arguments' => [], 'context' => ['recordKey' => (string) $note->getId()]])
        ->render();

    $editHtml = (string) $this->createLiveComponent(RecordWriteHost::class, client: $client)
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => ['recordKey' => (string) $note->getId()]])
        ->render();

    expect(renderedInput($viewHtml, 'title'))->toContain('value="Alpha"')->toContain('disabled=""')
        ->and(renderedInput($viewHtml, 'body'))->toContain('disabled=""')
        ->and(renderedInput($editHtml, 'title'))->toContain('value="Alpha"')->not->toContain('disabled')
        ->and(renderedInput($editHtml, 'body'))->not->toContain('disabled');
});

it('writes nothing when the modal is dismissed', function (): void {
    $note = viewedNote('Alpha', 'the body');
    $id = $note->getId();

    $host = $this->createLiveComponent(RecordWriteHost::class)->component();
    $host->mountAction('view', [], ['recordKey' => (string) $id]);
    $host->mountedActionsData[0]['title'] = 'Tampered';
    $host->unmountAction();

    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $entityManager->clear();

    expect($entityManager->find(Note::class, $id)?->getTitle())->toBe('Alpha')
        ->and($host->mountedActions)->toBe([]);
});
