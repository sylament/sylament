<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateImprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateUncheckedImprint;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * A blank key half on the `key` panel's imprint create, with the form's key
 * rules switched off and left on. The half is three spaces: no text field
 * trims by default, and blank() — which the key checks use — trims, so it is
 * blank to every check on every platform. Kernel reboot is disabled because the
 * fixture database lives in memory on a container service.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function pageKeyAdviceClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $request = new Request();
    $request->attributes->set('_sylament_panel', 'key');
    $request->setSession(test()->getContainer()->get('session.factory')->createSession());

    test()->getContainer()->get(RequestStack::class)->push($request);

    return [$client, test()->getContainer()->get(EntityManagerInterface::class)];
}

/**
 * @return array<string, list<string>>
 */
function pageKeyAdviceFieldErrors(string $html): array
{
    $errors = [];

    new Crawler($html)->filter('li.sy-fo-field-error-message')->each(function (Crawler $message) use (&$errors): void {
        $wrapper = $message->ancestors()->filter('[data-field-wrapper]')->first();
        $model = explode('|', $wrapper->filter('[data-model]')->first()->attr('data-model') ?? '');

        $errors[end($model)][] = mb_trim($message->text());
    });

    return $errors;
}

it('names validateRecordKey() in the blank-key refusal when the form switches the key rules off, writing nothing', function (): void {
    [$client, $entityManager] = pageKeyAdviceClient();

    $component = $this->createLiveComponent(CreateUncheckedImprint::class, ['panelId' => 'key'], client: $client);
    $component->set('data', ['house' => 'new.host', 'year' => '   ', 'label' => 'Unchecked']);

    expect(fn (): mixed => $component->call('create'))->toThrow(new LogicException(sprintf(
        'The resource page [%s] built a [%s] whose identifier is still blank after persist(), so it was not saved. Give every identifier field a form field — a Hidden one for a value the user should not type — or fill it in a prePersist listener, in makeRecord(), or in handleRecordCreation() before calling the parent. The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.',
        CreateUncheckedImprint::class,
        Imprint::class,
    )));

    $entityManager->flush();

    expect($entityManager->getConnection()->fetchAllAssociative('SELECT house FROM Imprint'))->toBe([]);
});

it('refuses the same blank key half under its field while the key rules stay on', function (): void {
    [$client, $entityManager] = pageKeyAdviceClient();

    $component = $this->createLiveComponent(CreateImprint::class, ['panelId' => 'key'], client: $client);
    $component->set('data', ['house' => 'new.host', 'year' => '   ', 'label' => 'Checked']);

    $client->catchExceptions(true);

    $component->call('create');

    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(pageKeyAdviceFieldErrors((string) $client->getResponse()->getContent()))->toBe(['data.year' => ['This value should not be blank.']])
        ->and($entityManager->getConnection()->fetchAllAssociative('SELECT house FROM Imprint'))->toBe([]);
});
