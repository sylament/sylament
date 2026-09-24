<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Components;

use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Cutting;
use Sylament\Actions\Tests\Fixtures\Twig\Components\RecordWriteHost;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Over a real 422 rather than a seeded error bag: the failed submit is what
 * files the record's violation onto the mounted schema's own path, and the
 * response body is the re-render the browser morphs in. `TestLiveComponent`
 * forces `catchExceptions(false)`, so the case hands it a client that catches.
 */

it('renders a record-level refusal that names no field above the modal form', function (): void {
    /** @var KernelBrowser $client */
    $client = $this::getContainer()->get('test.client');
    $client->disableReboot();

    $component = $this->createLiveComponent(RecordWriteHost::class, ['recordClass' => Cutting::class], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => []])
        ->set('mountedActionsData.0.title', 'wilted')
        ->set('mountedActionsData.0.body', 'cut too late');

    $client->catchExceptions(true);
    $component->call('callMountedAction');

    $html = (string) $component->response()->getContent();
    $crawler = new Crawler($html);

    expect($component->response()->getStatusCode())->toBe(422)
        ->and($crawler->filter('.sy-modal ul.sy-ac-modal-errors[data-record-errors] > li')->each(static fn (Crawler $node): string => $node->text()))
        ->toBe(['This cutting is refused as a whole.'])
        ->and(mb_strpos($html, 'data-record-errors'))->toBeLessThan(mb_strpos($html, 'name="title"'));
});

it('renders no record-error list while the mounted schema has none', function (): void {
    $html = (string) $this->createLiveComponent(RecordWriteHost::class, ['recordClass' => Cutting::class])
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => []])
        ->render();

    expect($html)->toContain('sy-modal')
        ->toContain('name="title"')
        ->not->toContain('data-record-errors');
});

it('escapes a record-level refusal that carries markup', function (): void {
    /** @var KernelBrowser $client */
    $client = $this::getContainer()->get('test.client');
    $client->disableReboot();

    $component = $this->createLiveComponent(RecordWriteHost::class, ['recordClass' => Cutting::class], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => []])
        ->set('mountedActionsData.0.title', '<b>wilted</b>');

    $client->catchExceptions(true);
    $component->call('callMountedAction');

    $crawler = new Crawler((string) $component->response()->getContent());

    expect($component->response()->getStatusCode())->toBe(422)
        ->and($crawler->filter('.sy-modal ul.sy-ac-modal-errors > li')->each(static fn (Crawler $node): string => $node->html()))
        ->toBe(['The cutting &lt;b&gt;wilted&lt;/b&gt; is refused as a whole.'])
        ->and($crawler->filter('.sy-modal ul.sy-ac-modal-errors b')->count())->toBe(0);
});
