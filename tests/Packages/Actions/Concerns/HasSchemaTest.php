<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Actions\FallbackFormHost;
use Sylament\Tests\Fixtures\Actions\SchemaDeclarationHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Actions\StepwiseActionHost;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

describe('declaring', function (): void {
    it('renders in its modal the fields its list declares, or the schema its closure hands back prepared, and runs on their state', function (string $name, string $field): void {
        $tester = $this->createLiveComponent(SchemaDeclarationHost::class)->mountAction($name)->assertActionMounted($name);

        expect($tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN . ' ' . SchemasSelectors::boundTo("mountedActionsData.0.{$field}"))->count())->toBe(1);

        $tester->fillForm([$field => 'Lamp'])->callMountedAction();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([sprintf('%s: {"%s":"Lamp"}', $name, $field)]);
    })->with([
        'a list' => ['listed', 'title'],
        'a closure handing back a schema' => ['prepared', 'note'],
    ]);

    it('runs at once without a modal when its declaration is cleared to null or empty', function (string $name): void {
        $tester = $this->createLiveComponent(SchemaDeclarationHost::class)->mountAction($name)->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["{$name}: []"])
            ->and($tester->render()->crawler()->filter(Selectors::MOUNTED_MODAL)->count())->toBe(0);
    })->with([
        'cleared to null' => ['cleared'],
        'an empty list' => ['emptyList'],
        'a closure answering an empty list' => ['emptyClosure'],
    ]);

    it('hands its declaration the container being built under each of its three names, never the schema the action sits in', function (Closure $declaration): void {
        $schema = Action::make('archive')->schemaContainer(Schema::make()->key('page'))->schema($declaration)->getSchema($container = Schema::make()->key('modal'));

        expect($schema)->toBe($container)
            ->and(array_map(static fn (TextInput $field): ?string => $field->getName(), $schema->getComponents()))->toBe(['modal']);
    })->with([
        'form' => [static fn (Schema $form): array => [TextInput::make((string) $form->getKey())]],
        'schema' => [static fn (Schema $schema): array => [TextInput::make((string) $schema->getKey())]],
        'infolist' => [static fn (Schema $infolist): array => [TextInput::make((string) $infolist->getKey())]],
    ]);

    it('disables the fields of its modal when told, a wizard among them', function (): void {
        $field = fn (string $host, string $name, string $field): Crawler => $this->createLiveComponent($host)->mountAction($name)->render()->crawler()->filter(Selectors::mountedModal(0) . " input[name=\"{$field}\"]");

        expect($field(SchemaDeclarationHost::class, 'locked', 'title')->attr('disabled'))->not->toBeNull()
            ->and($field(StepwiseActionHost::class, 'sealed', 'name')->attr('disabled'))->not->toBeNull()
            ->and($field(SchemaDeclarationHost::class, 'listed', 'title')->attr('disabled'))->toBeNull()
            ->and($field(StepwiseActionHost::class, 'onboard', 'name')->attr('disabled'))->toBeNull();
    });

    it('wraps its modal in a form its Submit submits, or without one puts the call on Submit itself', function (): void {
        expect($this->createLiveComponent(SchemaDeclarationHost::class)->mountAction('listed')->render()->crawler()->filter(Selectors::mountedModal(0)))
            ->toContainElement('form' . SupportSelectors::MODAL_WINDOW . Selectors::liveParameter('action', 'callMountedAction') . ' ' . SupportSelectors::MODAL_FOOTER . ' button[type="submit"]:not([data-action])', 'Submit');

        $tester = $this->createLiveComponent(SchemaDeclarationHost::class)->mountAction('unwrapped');
        $modal = $tester->render()->crawler()->filter(Selectors::mountedModal(0));

        expect($modal)->toContainElement('div' . SupportSelectors::MODAL_WINDOW . ' ' . SupportSelectors::MODAL_FOOTER . ' button[type="button"][data-action="live#action"]' . Selectors::liveParameter('action', 'callMountedAction'), 'Submit')
            ->and($modal->filter('form')->count())->toBe(0);

        $tester->fillForm(['title' => 'Lamp'])->callMountedAction();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['unwrapped: {"title":"Lamp"}']);
    });
});

describe('the mounted schema', function (): void {
    it("reads the modal's state from a before() hook, and nothing for a confirmation that mounted no schema or an action without a host", function (): void {
        $this->createLiveComponent(SchemaDeclarationHost::class)->mountAction('reading')->fillForm(['title' => 'Lamp'])->callMountedAction();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['before: {"title":"Lamp"}', 'reading: {"title":"Lamp"}']);

        $this->createLiveComponent(SchemaDeclarationHost::class)->callAction('confirmReading');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['before: null', 'confirmReading: []'])
            ->and(Action::make('archive')->getMountedSchema())->toBeNull();
    });
});

describe('the host form', function (): void {
    it("takes the form its host hands every action, built under all three names, when it declares none, and only its own when it declares one", function (): void {
        $field = fn (string $name, string $field): int => $this->createLiveComponent(FallbackFormHost::class)->mountAction($name)->render()->crawler()
            ->filter(Selectors::mountedModal(0) . Selectors::OPEN . ' ' . SchemasSelectors::boundTo("mountedActionsData.0.{$field}"))->count();

        expect($field('inherits', 'shared'))->toBe(1)
            ->and($field('own', 'own'))->toBe(1)
            ->and($field('own', 'shared'))->toBe(0);
    });

    it('takes no form from its host when it declares an empty one, nor without a host', function (): void {
        $this->createLiveComponent(FallbackFormHost::class)->mountAction('none')->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['none: []'])
            ->and(Action::make('archive')->getSchema(Schema::make()))->toBeNull();
    });
});

describe('a wizard', function (): void {
    it("wraps its steps in an uncontained wizard whose footer holds the modal's Cancel and Submit, the modal dropping its own footer and keeping its form", function (): void {
        $tester = $this->createLiveComponent(StepwiseActionHost::class)->mountAction('onboard');
        $modal = $tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN);

        expect($modal)->toContainElement(SchemasSelectors::WIZARD . ':not(' . SupportSelectors::CONTAINED . ')')
            ->toContainElement(SchemasSelectors::WIZARD_CANCEL_SLOT . ' ' . SupportSelectors::BUTTON . SupportSelectors::MODAL_DISMISS, 'Cancel')
            ->toContainElement('form' . SupportSelectors::MODAL_WINDOW . Selectors::liveParameter('action', 'callMountedAction') . ' ' . SchemasSelectors::WIZARD_SUBMIT_SLOT . ' button[type="submit"]', 'Submit')
            ->and($modal->filter(SupportSelectors::MODAL_FOOTER)->count())->toBe(0);

        $tester->fillForm(['name' => 'Ada', 'note' => 'Welcome'])->callMountedAction();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['onboard: {"name":"Ada","note":"Welcome"}']);
    });

    it('keeps the wizard a modifyWizardUsing() closure only modifies, and renders the one it hands back instead', function (): void {
        $wizard = fn (string $name): Crawler => $this->createLiveComponent(StepwiseActionHost::class)->mountAction($name)->render()->crawler()->filter(Selectors::mountedModal(0) . ' ' . SchemasSelectors::WIZARD);

        expect($wizard('retouched'))->toContainElement('[data-tour="retouched"] ' . SchemasSelectors::WIZARD_STEP_BUTTON, 'check 01 Account')
            ->and($wizard('replaced')->filter(SchemasSelectors::WIZARD_STEP_BUTTON)->each(static fn (Crawler $step): string => $step->text()))->toBe(['check 01 Replacement']);
    });
});
