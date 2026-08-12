<?php

declare(strict_types=1);

it('serves the dashboard inside the shell', function (): void {
    $client = $this::getContainer()->get('test.client');
    $client->request('GET', '/admin');

    $response = $client->getResponse();
    $html = (string) $response->getContent();

    expect($response->getStatusCode())->toBe(200)
        ->and($html)->toContain('id="sy-pa-sidebar"')
        ->and($html)->toContain('Test Admin')
        ->and($html)->toContain('<title>Dashboard — Test Admin</title>')
        ->and($html)->toContain('sy-pa-page')
        ->and($html)->toContain('aria-current="page"')
        ->and($html)->toContain('data-live-name-value="Sylament:Panels:Pages:Dashboard"');
});

it('serves a second page with its own navigation state', function (): void {
    $client = $this::getContainer()->get('test.client');
    $client->request('GET', '/admin/settings-page');

    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($html)->toContain('Settings Page')
        ->and(mb_substr_count($html, 'aria-current="page"'))->toBe(1);
});
