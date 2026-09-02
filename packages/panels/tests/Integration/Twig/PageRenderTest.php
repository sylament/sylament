<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Pages\GuardedSettingsPage;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\PreviewProbePage;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Every page this file renders sits on the **probe** panel, which is not the
 * default one: the panel stamp a live request restores is only falsifiable off
 * the default, and the `admin` fixture panel's sidebar label list is pinned
 * exactly elsewhere, so nothing new may register there.
 */

it('renders a plain page with its header actions, its form row and its unsaved-changes sentinel', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/probe/settings');

    $sentinel = $crawler->filter('.sy-pa-unsaved-changes[data-controller="sylament--panels--unsaved-changes"]');
    $submit = $crawler->filter('form.sy-pa-form .sy-sc .sy-sc-actions button[type="submit"]');
    $touch = $crawler->filter('form.sy-pa-form .sy-sc .sy-sc-actions button[data-live-name-param="touch"]');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('.sy-pa-page.probe-settings-page')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-header-actions button[data-live-name-param="ping"]')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-header-actions .sy-dropdown .sy-dropdown-trigger button')->count())->toBe(1)
        ->and($crawler->filter('form.sy-pa-form[data-action="submit->live#action:prevent"][data-live-action-param="save"]')->count())->toBe(1)
        ->and($submit->count())->toBe(1)
        ->and($submit->text())->toContain('Save')
        ->and($touch->count())->toBe(1)
        ->and(json_decode((string) $touch->attr('data-live-context-param'), true, flags: JSON_THROW_ON_ERROR))
            ->toBe(['schemaComponent' => 'formActions.form-actions'])
        ->and($crawler->filter('form.sy-pa-form ~ [data-probe-after-form]')->count())->toBe(1)
        ->and($sentinel->count())->toBe(1)
        ->and($sentinel->attr('data-sylament--panels--unsaved-changes-data-model-value'))->toBe('data')
        ->and($sentinel->attr('data-sylament--panels--unsaved-changes-guard-actions-value'))->toBe('true')
        ->and($sentinel->attr('data-sylament--panels--unsaved-changes-saved-hash-value'))->toHaveLength(32)
        // The sentinel is an inner element: a second `data-controller` on the
        // root would resolve later-source-wins and take the live binding down.
        ->and($crawler->filter('.sy-pa-page')->attr('data-controller'))->toBe('live')
        ->and($crawler->filter('.sy-breadcrumbs')->count())->toBe(0)
        ->and($crawler->filter('.sy-modal')->count())->toBe(0);
});

it('arms the actions guard alone on a page of an alerting panel that hosts no data', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/probe');

    $sentinel = $crawler->filter('.sy-pa-unsaved-changes[data-controller="sylament--panels--unsaved-changes"]');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($sentinel->count())->toBe(1)
        ->and($sentinel->attr('data-sylament--panels--unsaved-changes-data-model-value'))->toBe('')
        ->and($sentinel->attr('data-sylament--panels--unsaved-changes-guard-actions-value'))->toBe('true');
});

it('renders the header of a headingless page for its actions alone', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/probe/wide');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('h1.sy-pa-header-heading')->count())->toBe(0)
        ->and($crawler->filter('.sy-pa-header-actions button')->count())->toBe(1);
});

it('leaves a page of a panel that guards nothing without a sentinel', function (): void {
    $client = $this::getContainer()->get('test.client');
    $crawler = $client->request('GET', '/admin');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('.sy-pa-unsaved-changes')->count())->toBe(0);
});

it('mounts a grouped header action from a live request on the non-default panel', function (): void {
    $client = $this::getContainer()->get('test.client');

    $component = $this->createLiveComponent(GuardedSettingsPage::class, ['panelId' => 'probe'], client: $client)
        ->call('mountAction', ['name' => 'grouped', 'arguments' => [], 'context' => []]);

    expect($component->render()->crawler()->filter('.sy-modal')->count())->toBe(1);

    $component->call('callMountedAction');

    expect($component->render()->crawler()->filter('.sy-modal')->count())->toBe(0);
});

it('mounts a form-row action through the named form-actions schema', function (): void {
    $client = $this::getContainer()->get('test.client');

    $component = $this->createLiveComponent(GuardedSettingsPage::class, ['panelId' => 'probe'], client: $client)
        ->call('mountAction', ['name' => 'touch', 'arguments' => [], 'context' => ['schemaComponent' => 'formActions.form-actions']])
        ->call('callMountedAction');

    expect($component->component()->touched)->toBe(1);
});

/**
 * The fixture database lives in memory on a container service, so a reboot
 * between two requests would hand the second one a freshly-migrated, empty one.
 *
 * @return array{KernelBrowser, string}
 */
function probeArticleClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    EntityManagerFactory::seed($entityManager, new Article()->setTitle('Alpha')->setSlug('alpha'));

    $article = $entityManager->getRepository(Article::class)->findOneBy(['slug' => 'alpha']);

    expect($article)->toBeInstanceOf(Article::class);

    return [$client, (string) $article?->getId()];
}

it('mints a header action url for the panel the page was opened in, not the default one', function (): void {
    [$client, $key] = probeArticleClient();

    $crawler = $this->createLiveComponent(PreviewProbePage::class, ['record' => $key, 'panelId' => 'probe'], client: $client)
        ->render()
        ->crawler();

    expect($crawler->filter('.sy-pa-header-actions a')->attr('href'))->toEndWith('/probe/articles');
});

it('re-marks the saved data across a save', function (): void {
    $client = $this::getContainer()->get('test.client');

    $component = $this->createLiveComponent(GuardedSettingsPage::class, ['panelId' => 'probe'], client: $client);
    $before = $component->component()->savedDataHash;

    $component->set('data', ['name' => 'Changed']);
    $component->call('save');

    expect($before)->toHaveLength(32)
        ->and($component->component()->savedDataHash)->toHaveLength(32)
        ->and($component->component()->savedDataHash)->not->toBe($before);
});
