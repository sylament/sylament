<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Actions\Selectors as ActionsSelectors;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\CompanionHerb;
use Sylament\Tests\Fixtures\Entity\HookedArticle;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\VettedBinder;
use Sylament\Tests\Fixtures\Entity\VettedBinderSheet;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditActionedHerb;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditHookedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditInvoiceLine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditLoggedHerb;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditOrderLine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditPottedPlant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditRetitledHerb;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditSheetedBinder;
use Sylament\Tests\Fixtures\Panels\Resources\VariantHerbResource;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders the edit page filled with the record', function (): void {
    $this->seed($article = new Article('Draft'));

    $page = $this->visit("/admin/articles/{$article->id}/edit");

    expect($page->filter(Selectors::DOCUMENT_TITLE)->text())->toStartWith('Edit Article') // the record label, not the record title
        ->and($page->filter(Selectors::field('title'))->attr('value'))->toBe('Draft')
        ->and($this->renderedLiveProps($page)['data'])->toBe(['title' => 'Draft']);
});

it('saves the edit, stays on the page and toasts', function (): void {
    $this->seed($article = new Article('Draft'));

    $this->createLiveComponent(EditArticle::class, ['record' => $article->id])
        ->fillForm(['title' => 'Published'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNoRedirect()
        ->assertNotified('Saved');

    expect($this->reload($article)->title)->toBe('Published');
});

it('leaves the row untouched when the edit is invalid', function (): void {
    $this->seed($article = new Article('Draft'));

    $this->createLiveComponent(EditArticle::class, ['record' => $article->id])
        ->fillForm(['title' => ''])
        ->call('save')
        ->assertHasFormErrors(['title' => RequiredValue::class]);

    expect($this->reload($article)->title)->toBe('Draft');
});

it("runs the save hooks in Filament's order, and a halt skips the write", function (): void {
    $this->seed($article = new HookedArticle(EditHookedArticle::HALTING_TITLE));

    $this->createLiveComponent(EditHookedArticle::class, ['record' => $article->id])
        ->fillForm(['title' => 'Published'])
        ->call('save');

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['beforeValidate', 'afterValidate', 'beforeSave'])
        ->and($this->reload($article)->title)->toBe(EditHookedArticle::HALTING_TITLE);
});

it('refuses an edited composite key and keeps the row under its key', function (string $page, object $line): void {
    $this->seed($line);

    $this->createLiveComponent($page, ['record' => RecordKey::of($this->entityManager(), $line)])
        ->fillForm(['line' => 3])
        ->call('save')
        ->assertHasFormErrors(['line']);

    expect($this->entityManager()->getRepository($line::class)->findAll())
        ->sequence(fn ($stored) => $stored->toBeRecord(line: 2, note: 'First'));
})->with([
    'scalar fields' => fn (): array => [EditOrderLine::class, new OrderLine(order: 7, line: 2, note: 'First')],
    'an association plus a scalar' => fn (): array => [EditInvoiceLine::class, new InvoiceLine(new Invoice(7), line: 2, note: 'First')],
]);

it('submits its form to save, through a Save submit button beside a gray Cancel link to the listing', function (): void {
    $this->seed($article = new Article('Draft'));

    $page = $this->visit("/admin/articles/{$article->id}/edit");

    expect($page->filter(Selectors::formSubmittingTo('save')))->toHaveCount(1)
        ->and($page)->toContainButtons(Selectors::FORM_ACTIONS . ' ' . ActionsSelectors::TRIGGER, ['Save', 'Cancel'])
        ->and($page)->toContainElement(Selectors::FORM_SUBMIT_BUTTON, 'Save')
        ->and($page)->toContainElement(Selectors::FORM_ACTIONS . ' a[href="http://localhost/admin/articles"]' . SupportSelectors::GRAY, 'Cancel');
});

it('renders its content schema after the form, never inside it', function (): void {
    $this->seed($plant = new Plant('Fern'));

    $page = $this->visit("/create-shapes/potted-plants/{$plant->id}/edit");

    expect($page->filter(Selectors::PAGE)->text())->toContain(EditPottedPlant::CONTENT)
        ->and($page->filter(Selectors::FORM)->text())->not->toContain(EditPottedPlant::CONTENT)
        ->and($page->filter(Selectors::FORM . ' ~ *')->text())->toContain(EditPottedPlant::CONTENT)
        ->and($page->filter(Selectors::FORM . ' ' . Selectors::field('name'))->attr('value'))->toBe('Fern');
});

it('marks its root with the edit page, its resource and its record', function (object $record, string $uri, string $resourceClass): void {
    $this->seed($record);
    $key = RecordKey::of($this->entityManager(), $record);

    $page = $this->visit(sprintf($uri, $key));

    expect($page->filter(Selectors::PAGE . '.sy-pa-resource-edit-record-page.' . $resourceClass . "[class~=\"sy-pa-resource-record-{$key}\"]"))->toHaveCount(1);
})->with([
    'a single identifier' => fn (): array => [new Article('Draft'), '/admin/articles/%s/edit', 'sy-pa-resource-articles'],
    'a slashed slug' => fn (): array => [new VettedBinder(), '/edit-shapes/binders/sheeted/%s/edit', 'sy-pa-resource-binders-sheeted'],
    'scalar fields' => fn (): array => [new OrderLine(order: 7, line: 2, note: 'First'), '/admin/order-lines/%s/edit', 'sy-pa-resource-order-lines'],
    'an association plus a scalar' => fn (): array => [new InvoiceLine(new Invoice(7), line: 2, note: 'First'), '/admin/invoice-lines/%s/edit', 'sy-pa-resource-invoice-lines'],
]);

it('shows a title and a crumb of its own', function (): void {
    $this->seed($herb = new CompanionHerb('Mint', 'Mentha'));

    $page = $this->visit("/edit-shapes/variant-herbs/{$herb->id}/retitled");

    expect($page->filter(Selectors::DOCUMENT_TITLE)->text())->toStartWith('Tend the herb')
        ->and($page)->toContainElement(Selectors::PAGE_HEADING, 'Tend the herb')
        ->and($page)->toContainElement(SupportSelectors::CURRENT_BREADCRUMB, 'Tend');
});

describe('its form', function (): void {
    it("binds the resource form to the record under the edit operation, showing what only an edit shows and leaving out what an edit hides", function (): void {
        $this->seed($herb = new CompanionHerb('Mint', 'Mentha'));

        $form = $this->visit("/edit-shapes/variant-herbs/{$herb->id}/retitled")->filter(Selectors::FORM);

        expect($form->filter(Selectors::field('name'))->attr('value'))->toBe('Mint')
            ->and($form->text())->toContain(VariantHerbResource::EDIT_ONLY_LINE)
            ->and($form->filter(Selectors::field('latinName')))->toHaveCount(0);
    });

    it('fills through the fill mutator, between the fill hooks', function (): void {
        $this->seed($herb = new CompanionHerb('Mint', 'Mentha'));

        $page = $this->visit("/edit-shapes/variant-herbs/{$herb->id}/logged");

        expect($page->filter(Selectors::field('name'))->attr('value'))->toBe('MINT')
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe(['beforeFill', 'mutateFormDataBeforeFill', 'afterFill']);
    });
});

describe('a save', function (): void {
    it("runs Filament's ladder in order, asks the form's own reader rebound to the page's manager, and hands the write the record already mapped", function (): void {
        $this->seed($herb = new CompanionHerb('Mint', 'Mentha'));

        $this->createLiveComponent(EditLoggedHerb::class, ['record' => $herb->id, 'panelId' => 'edit-shapes'])
            ->fillForm(['name' => 'bASIL'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Saved');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([
            'beforeValidate',
            'afterValidate',
            'beforeSave',
            'mutateFormDataBeforeSave',
            'key check on a ' . CompanionHerb::class . " by the reader rebound to the registry's manager",
            'handleRecordUpdate',
            'afterSave',
        ])
            ->and($this->reload($herb))->toBeRecord(name: 'Basil', latinName: 'Herba Basil');
    });

    it('refuses by name a path its save mutator adds that no field maps, and writes nothing', function (): void {
        $this->seed($herb = new CompanionHerb('Mint', 'Mentha'));

        $tester = $this->createLiveComponent(EditLoggedHerb::class, ['record' => $herb->id, 'panelId' => 'edit-shapes'])
            ->fillForm(['name' => EditLoggedHerb::UNMAPPED_NAME]);

        expect(fn () => $tester->call('save'))->toThrow(LogicException::class, sprintf('The resource page [%s] added [status] in mutateFormDataBeforeSave(), but no component of its form maps those paths', EditLoggedHerb::class))
            ->and($this->reload($herb))->toBeRecord(name: 'Mint', latinName: 'Mentha');
    });

    it('leaves for the URL its getRedirectUrl() answers, toasting nothing under a blank notification title', function (): void {
        $this->seed($herb = new CompanionHerb('Mint', 'Mentha'));

        $this->createLiveComponent(EditRetitledHerb::class, ['record' => $herb->id, 'panelId' => 'edit-shapes'])
            ->fillForm(['name' => 'Basil'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect('/edit-shapes/variant-herbs')
            ->assertNotNotified();

        expect($this->reload($herb))->toBeRecord(name: 'Basil');
    });

    it("keys a relationship repeater's added item by the row the flush wrote, so the next save updates it", function (): void {
        $binder = new VettedBinder();
        $binder->addSheet(new VettedBinderSheet('One'));
        $this->seed($binder);

        $tester = $this->createLiveComponent(EditSheetedBinder::class, ['record' => $binder->id, 'panelId' => 'edit-shapes'])
            ->set('data.sheets.added', ['heading' => 'Two'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertFormSet(['sheets' => ['record-1' => ['heading' => 'One'], 'record-2' => ['heading' => 'Two']]]);

        $tester->fillForm(['sheets' => ['record-2' => ['heading' => 'Three']]])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($this->entityManager()->createQuery('SELECT sheet.heading FROM ' . VettedBinderSheet::class . ' sheet')->getSingleColumnResult())->toEqualCanonicalizing(['One', 'Three']);
    });

    it('refuses a violation the record raises once the state is mapped onto it, and writes nothing', function (string $path, mixed $value, string $error): void {
        $binder = new VettedBinder();
        $binder->addSheet(new VettedBinderSheet('One'));
        $binder->addSheet(new VettedBinderSheet('Two'));
        $this->seed($binder);

        $this->createLiveComponent(EditSheetedBinder::class, ['record' => $binder->id, 'panelId' => 'edit-shapes'])
            ->set($path, $value)
            ->call('save')
            ->assertHasFormErrors([$error])
            ->assertNotNotified();

        expect($this->entityManager()->createQuery('SELECT sheet.heading FROM ' . VettedBinderSheet::class . ' sheet')->getSingleColumnResult())->toEqualCanonicalizing(['One', 'Two']);
    })->with([
        "the record's own constraint, over a third sheet" => ['data.sheets.added', ['heading' => 'Three'], 'sheets'],
        "an item's own, over an overlong heading" => ['data.sheets.record-1.heading', 'Overlong', 'sheets.record-1.heading'],
    ]);
});

describe('its header actions', function (): void {
    it("open a create modal blank on the resource form, and a view modal filled from the page's record and disabled, each in the form's own one column", function (): void {
        $this->seed($herb = new CompanionHerb('Mint', 'Mentha'));

        $create = $this->createLiveComponent(EditActionedHerb::class, ['record' => $herb->id, 'panelId' => 'edit-shapes'])
            ->mountAction('create')
            ->assertActionDataSet(['name' => null, 'latinName' => null])
            ->render()
            ->crawler();

        $view = $this->createLiveComponent(EditActionedHerb::class, ['record' => $herb->id, 'panelId' => 'edit-shapes'])
            ->mountAction('view')
            ->assertActionDataSet(['name' => 'Mint', 'latinName' => 'Mentha'])
            ->render()
            ->crawler();

        expect($create)->toContainElement(SupportSelectors::OPEN_MODAL . ' ' . Selectors::field('latinName'))
            ->and($create->filter(SupportSelectors::OPEN_MODAL)->text())->not->toContain(VariantHerbResource::EDIT_ONLY_LINE)
            ->and($create)->toContainElement(SupportSelectors::OPEN_MODAL . ' ' . SupportSelectors::ONE_COLUMN)
            ->and($view)->toContainElement(SupportSelectors::MODAL_HEADING, 'View Mint')
            ->and($view->filter(SupportSelectors::OPEN_MODAL . ' ' . Selectors::field('name') . '[disabled]')->attr('value'))->toBe('Mint')
            ->and($view)->toContainElement(SupportSelectors::OPEN_MODAL . ' ' . SupportSelectors::ONE_COLUMN);
    });

    it("head a delete modal by the record's title, with no fields to fill", function (): void {
        $this->seed($herb = new CompanionHerb('Mint', 'Mentha'));

        $delete = $this->createLiveComponent(EditActionedHerb::class, ['record' => $herb->id, 'panelId' => 'edit-shapes'])
            ->mountAction('delete')
            ->render()
            ->crawler();

        expect($delete)->toContainElement(SupportSelectors::MODAL_HEADING, 'Delete Mint')
            ->and($delete->filter(SupportSelectors::OPEN_MODAL . ' input'))->toHaveCount(0);
    });

    it("create a new record from the create modal, checked against the page's record as against any other and leaving it as it was", function (): void {
        $this->seed($herb = new CompanionHerb('Mint', 'Mentha'));

        $this->createLiveComponent(EditActionedHerb::class, ['record' => $herb->id, 'panelId' => 'edit-shapes'])
            ->callAction('create', data: ['name' => 'Mint', 'latinName' => 'Mentha'])
            ->assertHasActionErrors(['name'])
            ->setActionData(['name' => 'Thyme', 'latinName' => 'Thymus'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        expect($this->entityManager()->getRepository(CompanionHerb::class)->findBy([], ['id' => 'ASC']))
            ->sequence(
                fn ($stored) => $stored->toBeRecord(name: 'Mint', latinName: 'Mentha'),
                fn ($stored) => $stored->toBeRecord(name: 'Thyme', latinName: 'Thymus'),
            );
    });

    it("embed the page's own form in the edit modal, whose submit writes nothing until the page saves what was typed", function (): void {
        $this->seed($herb = new CompanionHerb('Mint', 'Mentha'));

        $tester = $this->createLiveComponent(EditActionedHerb::class, ['record' => $herb->id, 'panelId' => 'edit-shapes'])
            ->mountAction('edit');

        $page = $tester->render()->crawler();

        expect($page)->toContainElement(SupportSelectors::MODAL_HEADING, 'Edit Mint')
            ->and($page->filter(SupportSelectors::OPEN_MODAL . ' [id="form.name"]'))->toHaveCount(1)
            ->and($page->filter('[id="form.name"]'))->toHaveCount(2);

        $tester->fillForm(['name' => 'Peppermint'], form: 'form')
            ->callMountedAction()
            ->assertActionNotMounted();

        expect($this->reload($herb))->toBeRecord(name: 'Mint');

        $tester->call('save')->assertHasNoFormErrors();

        expect($this->reload($herb))->toBeRecord(name: 'Peppermint');
    });

    it('redirects a header delete to the index and removes the record', function (): void {
        $this->seed($herb = new CompanionHerb('Mint', 'Mentha'));

        $this->createLiveComponent(EditActionedHerb::class, ['record' => $herb->id, 'panelId' => 'edit-shapes'])
            ->callAction('delete')
            ->assertRedirect('/edit-shapes/variant-herbs');

        expect($this->entityManager()->getRepository(CompanionHerb::class)->count())->toBe(0);
    });

    it("check a form-row modal's name against the other records, never the page's own, and a create's against every record", function (): void {
        $this->seed($herb = new CompanionHerb('Mint', 'Mentha'), new CompanionHerb('Sage', 'Salvia'));

        $rename = TestAction::make('rename')->schemaComponent('form-actions', schema: 'formActions');
        $sow = TestAction::make('sow')->schemaComponent('form-actions', schema: 'formActions');
        $page = fn () => $this->createLiveComponent(EditActionedHerb::class, ['record' => $herb->id, 'panelId' => 'edit-shapes']);

        $page()->callAction($rename, data: ['name' => 'Mint'])->assertHasNoActionErrors();
        $page()->callAction($rename, data: ['name' => 'Sage'])->assertHasActionErrors(['name']);
        $page()->callAction($sow, data: ['name' => 'Mint'])->assertHasActionErrors(['name']);
    });
});
