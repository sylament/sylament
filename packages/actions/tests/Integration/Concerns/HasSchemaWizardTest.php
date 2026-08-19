<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Concerns;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

function mountWizardAction(string $name): string
{
    return (string) test()->createLiveComponent('WizardActionHost')
        ->call('mountAction', ['name' => $name, 'arguments' => [], 'context' => []])
        ->render();
}

it('renders the mounted wizard schema uncontained inside the modal', function (): void {
    $html = mountWizardAction('onboard');

    expect($html)->toContain('sy-sc-wizard')
        ->and($html)->not->toContain('sy-contained')
        ->and($html)->toContain('sy-modal')
        ->and($html)->toContain('data-model="norender|mountedActionsData.0.title"');
});

it('renders the modal cancel and submit actions in the wizard footer slots', function (): void {
    $crawler = new Crawler(mountWizardAction('onboard'));

    $cancelSlot = $crawler->filter('[data-sylament--schemas--wizard-target="cancelSlot"]');
    $submitSlot = $crawler->filter('[data-sylament--schemas--wizard-target="submitSlot"]');

    expect($cancelSlot->count())->toBe(1)
        ->and($cancelSlot->text())->toContain('Cancel')
        ->and($submitSlot->count())->toBe(1)
        ->and($submitSlot->text())->toContain('Submit')
        ->and($submitSlot->filter('button[type="submit"]')->count())->toBe(1);
});

it('suppresses the modal footer actions for a wizard action', function (): void {
    $html = mountWizardAction('onboard');

    expect($html)->not->toContain('sy-modal-footer-actions');
});

it('reaches the render with the modifyWizardUsing() hook applied', function (): void {
    expect(mountWizardAction('onboard'))
        ->not->toContain('data-sylament--schemas--wizard-skippable-value')
        ->and(mountWizardAction('modified'))
        ->toContain('data-sylament--schemas--wizard-skippable-value="true"');
});

it('keeps the modal footer actions for a non-wizard schema action', function (): void {
    $html = mountWizardAction('edit');

    expect($html)->toContain('sy-modal-footer-actions')
        ->and($html)->toContain('Submit')
        ->and($html)->toContain('Cancel')
        ->and($html)->not->toContain('sy-sc-wizard');
});
