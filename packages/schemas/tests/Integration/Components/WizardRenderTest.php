<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Wizard;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

use function Sylament\Schemas\Tests\renderToHtml;

/**
 * Renders a Wizard as a child of a real Schema, the arrangement it ships in: the parent
 * supplies the container chain each Step's own child schema reads, so one render covers
 * the header, the panels, the footer slots and the two registered footer actions.
 */
function renderWizard(Wizard $wizard): string
{
    return renderToHtml(Schema::make(new HasSchemasFixture())->key('schema')->components([$wizard]));
}

function crawlWizard(Wizard $wizard): Crawler
{
    return new Crawler(renderWizard($wizard));
}

function threeStepWizard(): Wizard
{
    return Wizard::make([
        Step::make('Basics')->components([StateField::make('title')]),
        Step::make('Details')->components([StateField::make('body')]),
        Step::make('Done'),
    ]);
}

it('registers Step as a non-shared container service', function (): void {
    expect($this::getContainer()->get(Step::class))
        ->not->toBe($this::getContainer()->get(Step::class));
});

it('emits the controller, its values and its window listeners on the root', function (): void {
    $crawler = crawlWizard(threeStepWizard());
    $root = $crawler->filter('.sy-sc-wizard');

    expect($root->attr('class'))->toContain('sy')->toContain('sy-contained')
        ->and($root->attr('id'))->toBe('schema.wizard')
        ->and($root->attr('data-controller'))->toContain('sylament--schemas--wizard')
        ->and($root->attr('data-sylament--schemas--wizard-key-value'))->toBe('schema.wizard')
        ->and($root->attr('data-sylament--schemas--wizard-schema-key-value'))->toBe('schema')
        ->and($root->attr('data-sylament--schemas--wizard-start-step-value'))->toBe('1')
        ->and($root->attr('data-sylament--schemas--wizard-skippable-value'))->toBeNull()
        ->and($root->attr('data-sylament--schemas--wizard-query-string-key-value'))->toBeNull()
        ->and($root->attr('data-action'))
        ->toContain('next-wizard-step@window->sylament--schemas--wizard#onNextStepEvent')
        ->toContain('go-to-wizard-step@window->sylament--schemas--wizard#onGoToStepEvent')
        ->toContain('reset-schema-component-state@window->sylament--schemas--wizard#resetState')
        ->toContain('keydown->sylament--schemas--wizard#onKeydown');
});

it('renders the header as an ordered list of steps, the first one current', function (): void {
    $crawler = crawlWizard(threeStepWizard());
    $header = $crawler->filter('ol.sy-sc-wizard-header');
    $items = $header->filter('li.sy-sc-wizard-header-step');
    $buttons = $header->filter('button.sy-sc-wizard-header-step-btn');

    expect($header->attr('role'))->toBe('list')
        ->and($header->attr('data-sylament--schemas--wizard-target'))->toBe('header')
        ->and($items->count())->toBe(3)
        ->and($items->eq(0)->attr('class'))->toContain('sy-active')
        ->and($items->eq(0)->attr('data-step-key'))->toBe('schema.basics::wizard-step')
        ->and($items->eq(0)->attr('data-sylament--schemas--wizard-target'))->toBe('headerStep')
        ->and($items->eq(1)->attr('class'))->not->toContain('sy-active')
        ->and($buttons->eq(0)->attr('id'))->toBe('schema.basics::wizard-step-tab')
        ->and($buttons->eq(0)->attr('aria-controls'))->toBe('schema.basics::wizard-step')
        ->and($buttons->eq(0)->attr('aria-current'))->toBe('step')
        ->and($buttons->eq(0)->attr('disabled'))->not->toBeNull()
        ->and($buttons->eq(0)->attr('data-sylament--schemas--wizard-target'))->toBe('headerButton')
        ->and($buttons->eq(0)->attr('data-action'))->toBe('click->sylament--schemas--wizard#goToStepFromHeader')
        ->and($buttons->eq(1)->attr('disabled'))->not->toBeNull()
        ->and($buttons->eq(2)->attr('disabled'))->not->toBeNull()
        ->and($buttons->eq(1)->attr('aria-current'))->toBeNull();
});

it('numbers the steps, keeps a completed icon in every one and announces their status', function (): void {
    $crawler = crawlWizard(threeStepWizard());
    $items = $crawler->filter('li.sy-sc-wizard-header-step');
    $numbers = $crawler->filter('.sy-sc-wizard-header-step-number');
    $statuses = $crawler->filter('.sy-sc-wizard-header-step-status');

    expect($numbers->extract(['_text']))->toBe(['01', '02', '03'])
        ->and($crawler->filter('.sy-sc-wizard-header-step-completed-icon')->count())->toBe(3)
        ->and($items->eq(0)->filter('.sy-sc-wizard-header-step-completed-icon')->count())->toBe(1)
        ->and($statuses->extract(['_text']))->toBe(['', 'Not completed', 'Not completed'])
        ->and($statuses->eq(0)->attr('data-sylament--schemas--wizard-target'))->toBe('stepStatus')
        ->and($crawler->filter('svg.sy-sc-wizard-header-step-separator')->count())->toBe(2);
});

it('renders one grouped panel per step, the start step active', function (): void {
    $crawler = crawlWizard(threeStepWizard());
    $panels = $crawler->filter('.sy-sc-wizard-step');

    expect($panels->count())->toBe(3)
        ->and($panels->eq(0)->attr('role'))->toBe('group')
        ->and($panels->eq(0)->attr('class'))->toContain('sy-active')
        ->and($panels->eq(0)->attr('id'))->toBe('schema.basics::wizard-step')
        ->and($panels->eq(0)->attr('aria-labelledby'))->toBe('schema.basics::wizard-step-tab')
        ->and($panels->eq(0)->attr('aria-label'))->toBeNull()
        ->and($panels->eq(0)->attr('tabindex'))->toBe('0')
        ->and($panels->eq(0)->attr('data-step-key'))->toBe('schema.basics::wizard-step')
        ->and($panels->eq(0)->attr('data-sylament--schemas--wizard-target'))->toBe('step')
        ->and($panels->eq(0)->attr('data-action'))->toBe('expand->sylament--schemas--wizard#expand')
        ->and($panels->eq(1)->attr('class'))->not->toContain('sy-active');
});

it('shows the cancel and next slots on the first step', function (): void {
    $crawler = crawlWizard(threeStepWizard());
    $footer = $crawler->filter('.sy-sc-wizard-footer > div');

    expect($footer->count())->toBe(4)
        ->and($footer->eq(0)->attr('data-sylament--schemas--wizard-target'))->toBe('previousSlot')
        ->and($footer->eq(0)->attr('class'))->toContain('sy-hidden')
        ->and($footer->eq(0)->attr('data-action'))->toBe('click->sylament--schemas--wizard#goToPreviousStep')
        ->and($footer->eq(1)->attr('data-sylament--schemas--wizard-target'))->toBe('cancelSlot')
        ->and($footer->eq(1)->attr('class'))->toBeNull()
        ->and($footer->eq(2)->attr('data-sylament--schemas--wizard-target'))->toBe('nextSlot')
        ->and($footer->eq(2)->attr('class'))->toBeNull()
        ->and($footer->eq(2)->attr('data-loading'))->toBe('addClass(sy-disabled)')
        ->and($footer->eq(2)->attr('data-action'))->toBe('click->sylament--schemas--wizard#requestNextStep')
        ->and($footer->eq(3)->attr('data-sylament--schemas--wizard-target'))->toBe('submitSlot')
        ->and($footer->eq(3)->attr('class'))->toContain('sy-hidden');
});

it('renders both footer actions as inert buttons', function (): void {
    $crawler = crawlWizard(threeStepWizard());
    $footer = $crawler->filter('.sy-sc-wizard-footer > div');

    expect($footer->eq(0)->filter('button.sy-btn')->text())->toContain('Back')
        ->and($footer->eq(2)->filter('button.sy-btn')->text())->toContain('Next')
        ->and($footer->eq(2)->filter('button.sy-btn')->attr('data-action'))->toBeNull();
});

it('marks earlier steps completed and unlocks their buttons when starting later', function (): void {
    $crawler = crawlWizard(threeStepWizard()->startOnStep(2));
    $items = $crawler->filter('li.sy-sc-wizard-header-step');
    $buttons = $crawler->filter('button.sy-sc-wizard-header-step-btn');
    $footer = $crawler->filter('.sy-sc-wizard-footer > div');

    expect($items->eq(0)->attr('class'))->toContain('sy-completed')->not->toContain('sy-active')
        ->and($items->eq(1)->attr('class'))->toContain('sy-active')
        ->and($buttons->eq(0)->attr('disabled'))->toBeNull()
        ->and($buttons->eq(1)->attr('disabled'))->not->toBeNull()
        ->and($buttons->eq(2)->attr('disabled'))->not->toBeNull()
        ->and($crawler->filter('.sy-sc-wizard-header-step-status')->extract(['_text']))
        ->toBe(['Completed', '', 'Not completed'])
        ->and($crawler->filter('.sy-sc-wizard-step')->eq(1)->attr('class'))->toContain('sy-active')
        ->and($footer->eq(0)->attr('class'))->toBeNull()
        ->and($footer->eq(1)->attr('class'))->toContain('sy-hidden')
        ->and($footer->eq(2)->attr('class'))->toBeNull()
        ->and($footer->eq(3)->attr('class'))->toContain('sy-hidden');
});

it('swaps the next slot for the submit slot on the last step', function (): void {
    $footer = crawlWizard(threeStepWizard()->startOnStep(3))->filter('.sy-sc-wizard-footer > div');

    expect($footer->eq(2)->attr('class'))->toContain('sy-hidden')
        ->and($footer->eq(3)->attr('class'))->toBeNull();
});

it('unlocks every header button and flags itself skippable', function (): void {
    $crawler = crawlWizard(threeStepWizard()->skippable());

    expect($crawler->filter('.sy-sc-wizard')->attr('data-sylament--schemas--wizard-skippable-value'))->toBe('true')
        ->and($crawler->filter('button.sy-sc-wizard-header-step-btn[disabled]')->count())->toBe(0);
});

it('drops the click actions when a footer action is disabled', function (): void {
    $footer = crawlWizard(threeStepWizard()
        ->nextAction(fn (Action $action): Action => $action->disabled())
        ->previousAction(fn (Action $action): Action => $action->disabled()))
        ->filter('.sy-sc-wizard-footer > div');

    expect($footer->eq(0)->attr('data-action'))->toBeNull()
        ->and($footer->eq(2)->attr('data-action'))->toBeNull();
});

it('disables every header button when the previous action is disabled', function (): void {
    $crawler = crawlWizard(threeStepWizard()
        ->skippable()
        ->previousAction(fn (Action $action): Action => $action->disabled()));

    expect($crawler->filter('button.sy-sc-wizard-header-step-btn[disabled]')->count())->toBe(3);
});

it('drops the header and names each panel by its own label when hidden', function (): void {
    $crawler = crawlWizard(threeStepWizard()->hiddenHeader());
    $panels = $crawler->filter('.sy-sc-wizard-step');

    expect($crawler->filter('ol.sy-sc-wizard-header')->count())->toBe(0)
        ->and($crawler->filter('.sy-sc-wizard')->attr('class'))->toContain('sy-sc-wizard-header-hidden')
        ->and($panels->eq(0)->attr('aria-label'))->toBe('Basics')
        ->and($panels->eq(0)->attr('aria-labelledby'))->toBeNull();
});

it('publishes the query-string key it persists the step under', function (): void {
    $root = crawlWizard(threeStepWizard()->persistStepInQueryString())->filter('.sy-sc-wizard');

    expect($root->attr('data-sylament--schemas--wizard-query-string-key-value'))->toBe('step');
});

it('labels the header with the wizard label', function (): void {
    $header = crawlWizard(threeStepWizard()->label('Order'))->filter('ol.sy-sc-wizard-header');

    expect($header->attr('aria-label'))->toBe('Order');
});

it('drops the contained class when the wizard is not contained', function (): void {
    $root = crawlWizard(threeStepWizard()->contained(false))->filter('.sy-sc-wizard');

    expect($root->attr('class'))->not->toContain('sy-contained');
});

it('keeps a hidden step label in the DOM for screen readers', function (): void {
    $label = crawlWizard(Wizard::make([Step::make('Basics')->hiddenLabel(), Step::make('Details')]))
        ->filter('.sy-sc-wizard-header-step-label')->eq(0);

    expect($label->attr('class'))->toContain('sy-sc-wizard-header-step-label-hidden')
        ->and($label->text())->toBe('Basics');
});

it('renders a step description and its icons', function (): void {
    $crawler = crawlWizard(Wizard::make([
        Step::make('Basics')
            ->description('The first bits')
            ->icon('heroicons:x-mark')
            ->completedIcon('heroicons:chevron-up-20-solid'),
        Step::make('Details'),
    ]));

    $completedIcons = $crawler->filter('svg.sy-sc-wizard-header-step-completed-icon');

    expect($crawler->filter('.sy-sc-wizard-header-step-description')->text())->toBe('The first bits')
        ->and($crawler->filter('svg.sy-sc-wizard-header-step-icon')->count())->toBe(1)
        // The fixture icons differ by viewBox, so the declared completed icon is
        // distinguishable from the `heroicons:check` default the other step keeps.
        ->and($completedIcons->eq(0)->attr('viewBox'))->toBe('0 0 20 20')
        ->and($completedIcons->eq(1)->attr('viewBox'))->toBe('0 0 24 24')
        ->and($crawler->filter('.sy-sc-wizard-header-step')->eq(0)->filter('.sy-sc-wizard-header-step-number')->count())->toBe(0)
        ->and($crawler->filter('.sy-sc-wizard-header-step')->eq(1)->filter('.sy-sc-wizard-header-step-number')->text())->toBe('02');
});

it('escapes a plain-string footer slot and prints a Markup one raw', function (): void {
    $html = renderWizard(threeStepWizard()
        ->cancelAction('<b>Cancel</b>')
        ->submitAction(new Markup('<em>Save</em>', 'UTF-8')));

    expect($html)->toContain('&lt;b&gt;Cancel&lt;/b&gt;')
        ->not->toContain('<b>Cancel</b>')
        ->toContain('<em>Save</em>');
});

it('renders an Action footer slot as a real trigger', function (): void {
    $footer = crawlWizard(threeStepWizard()->submitAction(Action::make('save')))
        ->filter('.sy-sc-wizard-footer > div');

    expect($footer->eq(3)->filter('button.sy-btn')->count())->toBe(1)
        ->and($footer->eq(3)->filter('button.sy-btn')->text())->toContain('Save');
});
