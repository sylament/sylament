<?php

declare(strict_types=1);

use Sylament\Panels\Pages\Concerns\HasUnsavedDataChangesAlert;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Panel;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * The alert is a two-level fixture because PHP admits nothing else: a class that
 * composes the trait and redeclares `$hasUnsavedDataChangesAlert` with another
 * default is a fatal at class load, so the property belongs to a *subclass* of a
 * page that already composes it.
 */
abstract class GuardedProbePage extends Page
{
    use HasUnsavedDataChangesAlert;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    public function remember(): void
    {
        $this->rememberData();
    }

    /**
     * A Unit test boots no container, so the base's panel lookup would throw —
     * this page hands back the panel the registry would have resolved, with the
     * knob at its default.
     */
    public function getPanel(): Panel
    {
        return Panel::make();
    }
}

final class UnguardedDataPage extends GuardedProbePage
{
}

final class GuardedDataPage extends GuardedProbePage
{
    protected ?bool $hasUnsavedDataChangesAlert = true;
}

final class OptedOutDataPage extends GuardedProbePage
{
    protected ?bool $hasUnsavedDataChangesAlert = false;

    public function getPanel(): Panel
    {
        return Panel::make()->unsavedChangesAlerts();
    }
}

it('leaves the marker null while the panel knob is off and the page names nothing', function (): void {
    $page = new UnguardedDataPage();
    $page->data = ['name' => 'Probe'];
    $page->remember();

    expect($page->hasUnsavedDataChangesAlert())->toBeFalse()
        ->and($page->savedDataHash)->toBeNull();
});

it('marks the saved state with a hash of the data when the alert is on', function (): void {
    $page = new GuardedDataPage();
    $page->data = ['name' => 'Probe'];
    $page->remember();

    $first = $page->savedDataHash;

    $page->remember();

    expect($page->hasUnsavedDataChangesAlert())->toBeTrue()
        ->and($first)->toMatch('/^[0-9a-f]{32}$/')
        ->and($page->savedDataHash)->toBe($first);
});

it('takes a different marker once the data has changed', function (): void {
    $page = new GuardedDataPage();
    $page->data = ['name' => 'Probe'];
    $page->remember();

    $first = $page->savedDataHash;

    $page->data = ['name' => 'Changed'];
    $page->remember();

    expect($page->savedDataHash)->not->toBe($first);
});

it('lets the page override win over a panel that arms the alert', function (): void {
    $page = new OptedOutDataPage();
    $page->data = ['name' => 'Probe'];
    $page->remember();

    expect($page->hasUnsavedDataChangesAlert())->toBeFalse()
        ->and($page->savedDataHash)->toBeNull();
});

it('arms the alert off the panel knob when the page names nothing', function (): void {
    $page = new class extends GuardedProbePage {
        public function getPanel(): Panel
        {
            return Panel::make()->unsavedChangesAlerts();
        }
    };

    $page->data = ['name' => 'Probe'];
    $page->remember();

    expect($page->hasUnsavedDataChangesAlert())->toBeTrue()
        ->and($page->savedDataHash)->toMatch('/^[0-9a-f]{32}$/');
});
