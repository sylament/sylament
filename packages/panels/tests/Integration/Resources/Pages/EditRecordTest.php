<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditHookedArticle;
use Sylament\Schemas\Components\EmbeddedSchema;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

afterEach(function (): void {
    EditHookedArticle::$haltsBeforeSave = false;
});

/*
 * Kernel reboot is disabled for the same reason `ListRecordsTest` disables it:
 * the fixture database lives in memory on a container service, so a reboot
 * between two requests would hand the second one a freshly-migrated, empty one.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface, string}
 */
function editRecordClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    EntityManagerFactory::seed($entityManager, new Article()->setTitle('Alpha')->setSlug('alpha'));

    $article = $entityManager->getRepository(Article::class)->findOneBy(['slug' => 'alpha']);

    expect($article)->toBeInstanceOf(Article::class);

    return [$client, $entityManager, (string) $article?->getId()];
}

/**
 * @return array<string, mixed>
 */
function editRecordProps(string $html): array
{
    preg_match('/data-live-props-value="([^"]*)"/', $html, $matches);

    $props = json_decode(html_entity_decode($matches[1] ?? ''), true, flags: JSON_THROW_ON_ERROR);

    expect($props)->toBeArray();

    return $props;
}

it('serves the edit page with the record filled in', function (): void {
    [$client, , $key] = editRecordClient();

    $crawler = $client->request('GET', sprintf('/admin/articles/%s/edit', $key));
    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('h1.sy-pa-header-heading')->text())->toBe('Edit Alpha')
        ->and($crawler->filter('.sy-pa-resource-edit-record-page > .sy-pa-page-header-main-ctn > .sy-pa-page-main > .sy-pa-page-content > form')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-resource-edit-record-page input[data-model$="data.title"]')->attr('value'))->toBe('Alpha')
        ->and($crawler->filter('form.sy-pa-form .sy-sc-actions button[type="submit"]')->text())->toContain('Save')
        // The document title is read off the un-mounted page, where the record key
        // is still null, so it carries the record *label* rather than the title.
        ->and($html)->toContain('<title>Edit Article — Test Admin</title>');
});

it('sits a gray cancel link beside the edit submit', function (): void {
    [$client, , $key] = editRecordClient();

    $crawler = $client->request('GET', sprintf('/admin/articles/%s/edit', $key));

    $cancel = $crawler->filter('form.sy-pa-form .sy-sc-actions a');
    $submit = $crawler->filter('form.sy-pa-form .sy-sc-actions button[type="submit"]');

    // `gray` is the Button policy's default and `HasDefaultGrayColor` emits no
    // class for it, so the absence of any `sy-color` token *is* the gray arm —
    // which is what tells the cancel link apart from the primary submit.
    expect($cancel->count())->toBe(1)
        ->and($cancel->text())->toContain('Cancel')
        ->and($cancel->attr('href'))->toEndWith('/admin/articles')
        ->and($cancel->attr('class'))->not->toContain('sy-color')
        ->and($submit->attr('class'))->toContain('sy-color-primary');
});

it('trails the resource and the record above the edit heading', function (): void {
    [$client, , $key] = editRecordClient();

    $crawler = $client->request('GET', sprintf('/admin/articles/%s/edit', $key));

    $items = $crawler->filter('.sy-pa-header-has-breadcrumbs .sy-breadcrumbs-item');

    // The record crumb links to the page that shows it — the resource registers
    // no view page, so its edit page is that page — and only the page's own
    // crumb is plain text.
    expect($items->count())->toBe(3)
        ->and($items->eq(0)->filter('a.sy-breadcrumbs-item-label')->attr('href'))->toEndWith('/admin/articles')
        ->and($items->eq(0)->filter('a.sy-breadcrumbs-item-label')->text())->toBe('Articles')
        ->and($items->eq(1)->filter('a.sy-breadcrumbs-item-label')->attr('href'))->toEndWith(sprintf('/admin/articles/%s/edit', $key))
        ->and($items->eq(1)->filter('a.sy-breadcrumbs-item-label')->text())->toBe('Alpha')
        ->and($items->eq(2)->filter('span.sy-breadcrumbs-item-label')->text())->toBe('Edit')
        ->and($items->eq(2)->filter('span.sy-breadcrumbs-item-label')->attr('aria-current'))->toBe('page');
});

it('declines to link a record-bound edit action to the page already showing it', function (): void {
    [$client, , $key] = editRecordClient();

    $crawler = $client->request('GET', sprintf('/admin/articles/%s/edit', $key));
    $headerAction = $crawler->filter('.sy-pa-header-actions button');

    expect($crawler->filter('.sy-pa-header-actions a')->count())->toBe(0)
        ->and($headerAction->text())->toContain('Edit')
        ->and($headerAction->attr('data-live-name-param'))->toBe('edit');
});

it('404s for an unknown record', function (): void {
    [$client] = editRecordClient();

    $client->request('GET', '/admin/articles/999999/edit');

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

it('keeps the record key on the wire, not the entity', function (): void {
    [$client, , $key] = editRecordClient();

    $component = $this->createLiveComponent(EditArticle::class, ['record' => $key], client: $client);

    expect(editRecordProps((string) $component->render())['record'])->toBe($key);
});

it('saves in place, stays on the page and dispatches the toast browser event', function (): void {
    [$client, $entityManager, $key] = editRecordClient();

    $component = $this->createLiveComponent(EditArticle::class, ['record' => $key], client: $client);
    $component->set('data', ['title' => 'Alpha, edited', 'slug' => 'alpha']);
    $component->call('save');

    expect($component->response()->isRedirection())->toBeFalse();

    $this->assertComponentDispatchBrowserEvent($component, 'sylament:notification');

    $toast = (string) ($component->getDispatchedBrowserEvent($component->render(), 'sylament:notification')['payload']['html'] ?? '');

    $entityManager->clear();
    $reloaded = $entityManager->getRepository(Article::class)->find((int) $key);

    expect($toast)->toContain('Saved')
        ->and($reloaded)->toBeInstanceOf(Article::class)
        ->and($reloaded?->getTitle())->toBe('Alpha, edited')
        ->and($entityManager->getRepository(Article::class)->count([]))->toBe(1);
});

/*
 * The cases below run in-process on one host object: a `->call()` is a fresh
 * request that rebuilds the page and its cached actions, so the mounted stack a
 * case sets up would not be the one it then calls, and the page-state a case
 * asserts (the refilled form, the recorded hooks) would be read off a different
 * instance. A success notification is a session flash — `Notification::send()`
 * reads the session off the `RequestStack` — so those cases push a request
 * carrying one first, the way actions' own modal suites do.
 */

function pushRequestWithSession(): void
{
    $request = new Request();
    $request->setSession(test()->getContainer()->get('session.factory')->createSession());

    test()->getContainer()->get(RequestStack::class)->push($request);
}

it('opens its own edit modal over the page form itself', function (): void {
    [$client, , $key] = editRecordClient();

    $host = $this->createLiveComponent(EditArticle::class, ['record' => $key], client: $client)->component();
    $host->mountAction('edit', [], []);

    $schema = $host->getMountedActionSchema();
    $components = array_values($schema?->getComponents() ?? []);
    $embedded = $components[0] ?? null;

    // One tree, not two: the modal embeds the page's own `form` schema — the
    // same object the page beneath it is bound to — so there is no second state
    // path and no second error bag to keep in step.
    expect($components)->toHaveCount(1)
        ->and($embedded)->toBeInstanceOf(EmbeddedSchema::class)
        ->and($embedded?->getName())->toBe('form')
        ->and($embedded?->getLiveComponent()->getSchema('form'))->toBe($host->getSchema('form'));
});

it('renders the page form inside its own edit modal', function (): void {
    [$client, , $key] = editRecordClient();

    $crawler = $this->createLiveComponent(EditArticle::class, ['record' => $key], client: $client)
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => []])
        ->render()
        ->crawler();

    // The modal's inputs write to the page's own state path, which is what makes
    // the two views the same form rather than two copies of it.
    expect($crawler->filter('.sy-modal input[data-model$="data.title"]')->count())->toBe(1)
        ->and($crawler->filter('.sy-modal input[data-model$="data.title"]')->attr('value'))->toBe('Alpha');
});

it('leaves the writing to the page save when its own edit modal submits', function (): void {
    [$client, $entityManager, $key] = editRecordClient();

    pushRequestWithSession();

    $host = $this->createLiveComponent(EditArticle::class, ['record' => $key], client: $client)->component();
    $host->mountAction('edit', [], []);

    // The modal's inputs are the page form's, so this is what typing in the
    // modal does.
    $host->data['title'] = 'Alpha, edited';
    $host->callMountedAction();

    // Read without clearing: the page holds the managed entity, and clearing
    // would detach the one its own save is about to write.
    $afterModal = $entityManager->find(Article::class, (int) $key)?->getTitle();

    $host->save();

    $entityManager->clear();

    // The embed carries no state of its own, so the action's own schema is empty
    // and its write maps nothing onto the record: the page's `save()` is the
    // writer, exactly as in Filament, where this arm is the same one line.
    expect($host->mountedActions)->toBe([])
        ->and($afterModal)->toBe('Alpha')
        ->and($entityManager->find(Article::class, (int) $key)?->getTitle())->toBe('Alpha, edited');
});

it('deletes the record from the header and redirects to the index', function (): void {
    [$client, $entityManager, $key] = editRecordClient();

    pushRequestWithSession();

    $host = $this->createLiveComponent(EditArticle::class, ['record' => $key], client: $client)->component();
    $host->mountAction('delete', [], []);

    $redirect = $host->callMountedAction();

    $entityManager->clear();

    expect($redirect)->toBeInstanceOf(RedirectResponse::class)
        ->and($redirect instanceof RedirectResponse ? $redirect->getTargetUrl() : null)->toEndWith('/admin/articles')
        ->and($entityManager->find(Article::class, (int) $key))->toBeNull();
});

it('fills and saves through the mutators and hooks in Filament order', function (): void {
    [$client, $entityManager, $key] = editRecordClient();

    pushRequestWithSession();

    $host = $this->createLiveComponent(EditHookedArticle::class, ['record' => $key], client: $client)->component();

    expect($host->calls)->toBe(['beforeFill', 'mutateFill', 'afterFill']);

    $host->calls = [];
    $host->data = ['title' => 'alpha, edited', 'slug' => 'alpha'];
    $host->save();

    $entityManager->clear();

    // `beforeSave` fires *inside* the state read, before the mutator — a
    // normalised order would be a silent divergence from Filament.
    expect($host->calls)->toBe(['beforeValidate', 'afterValidate', 'beforeSave', 'mutateSave', 'afterSave'])
        ->and($host->titleSeenBeforeSave)->toBe('alpha, edited')
        ->and($entityManager->find(Article::class, (int) $key)?->getTitle())->toBe('ALPHA, EDITED');
});

it('keeps a create modal on the edit page anonymous', function (): void {
    [$client, , $key] = editRecordClient();

    $host = $this->createLiveComponent(EditArticle::class, ['record' => $key], client: $client)->component();
    $host->mountAction('create', [], []);

    $schema = $host->getMountedActionSchema();

    // A create modal on a record page must not inherit the page's record, or the
    // form would open as an edit of it — the class alone is what it needs.
    expect($schema?->getRecord())->toBeNull()
        ->and($schema?->getRecordClass())->toBe(Article::class);
});

it('404s when a mounted frame names a record the resource cannot resolve', function (): void {
    [$client, , $key] = editRecordClient();

    $host = $this->createLiveComponent(EditArticle::class, ['record' => $key], client: $client)->component();

    // The page is no table host, so the frame's key reaches the resource's
    // scoped binding — which answers nothing for a record that never existed.
    expect(fn (): mixed => $host->mountAction('edit', [], ['recordKey' => '999999']))
        ->toThrow(NotFoundHttpException::class, 'No record matching [999999]');
});

it('aborts the save when a hook halts the ladder', function (): void {
    [$client, $entityManager, $key] = editRecordClient();

    EditHookedArticle::$haltsBeforeSave = true;

    $host = $this->createLiveComponent(EditHookedArticle::class, ['record' => $key], client: $client)->component();

    $host->calls = [];
    $host->data = ['title' => 'alpha, edited', 'slug' => 'alpha'];

    $result = $host->save();

    $entityManager->clear();

    // The ladder stops where the hook threw: no mutator, no write, no
    // notification and no redirect — and today a `Halt` from here was a 500.
    expect($result)->toBeNull()
        ->and($host->calls)->toBe(['beforeValidate', 'afterValidate', 'beforeSave'])
        ->and($entityManager->find(Article::class, (int) $key)?->getTitle())->toBe('Alpha');
});

it('leaves the saved-data marker null while the panel knob is off', function (): void {
    [$client, , $key] = editRecordClient();

    $host = $this->createLiveComponent(EditArticle::class, ['record' => $key], client: $client)->component();

    // `admin` never arms `unsavedChangesAlerts()`, so the page composing the
    // trait still marks nothing — the gate is the panel's, not the trait's.
    expect($host->savedDataHash)->toBeNull()
        ->and($host->hasUnsavedDataChangesAlert())->toBeFalse();
});
