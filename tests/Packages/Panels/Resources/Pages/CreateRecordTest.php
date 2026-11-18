<?php

declare(strict_types=1);

use Doctrine\ORM\Exception\EntityMissingAssignedId;
use Sylament\Actions\Testing\Testable;
use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Tests\Fixtures\Actions\Selectors as ActionsSelectors;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Entity\CharmSatchel;
use Sylament\Tests\Fixtures\Entity\ClaspedLocket;
use Sylament\Tests\Fixtures\Entity\CodeKeyedSlate;
use Sylament\Tests\Fixtures\Entity\ConstructorCodedBadge;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderCrock;
use Sylament\Tests\Fixtures\Entity\Monument;
use Sylament\Tests\Fixtures\Entity\MonumentPlaque;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\QuayBollard;
use Sylament\Tests\Fixtures\Entity\SatchelCharm;
use Sylament\Tests\Fixtures\Entity\StampedBulletin;
use Sylament\Tests\Fixtures\Entity\VolumeSpine;
use Sylament\Tests\Fixtures\Notifications\Selectors as NotificationsSelectors;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateAssignedSlugHeadline;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateBarePottedPlant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateCaselessBollard;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateGroupedRowPottedPlant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateHookedPottedPlant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateListenerStampedBulletin;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateLowercasedBadge;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateMonumentPlaque;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreatePottedPlant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateSecondManagerSpine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateShelflessCrock;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateShelvedCrock;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateUnkeyedLocket;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateUnvettedKeyCharm;
use Sylament\Tests\Fixtures\Panels\Resources\PottedPlantResource;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

describe('the page', function (): void {
    it('opens the resource form empty under a "Create" title, crumb and page classes', function (): void {
        $page = $this->visit('/admin/articles/create');

        expect($page->filter(Selectors::DOCUMENT_TITLE)->text())->toStartWith('Create Article')
            ->and($page)->toContainElement(Selectors::PAGE_HEADING, 'Create Article')
            ->and($page)->toContainElement(SupportSelectors::CURRENT_BREADCRUMB, 'Create')
            ->and($page->filter(Selectors::PAGE . '.sy-pa-resource-create-record-page.sy-pa-resource-articles'))->toHaveCount(1)
            ->and($page->filter(Selectors::FORM . ' ' . Selectors::field('title'))->attr('value'))->toBe('')
            ->and($this->renderedLiveProps($page))->toHaveKey('data')->not->toHaveKey('record');
    });

    it("opens on every field's default", function (): void {
        $page = $this->visit('/create-shapes/potted-plants/create');

        expect($page->filter(Selectors::field('name'))->attr('value'))->toBe(PottedPlantResource::DEFAULT_NAME);
    });

    it('fills its form through a fillForm() the page replaces', function (): void {
        $page = $this->visit('/create-shapes/potted-plants/create-bare');

        expect($page->filter(Selectors::field('name'))->attr('value'))->toBe(CreateBarePottedPlant::FILLED_NAME);
    });

    it('submits its form to create, through a Create submit button beside a gray Cancel link to the listing', function (): void {
        $page = $this->visit('/admin/articles/create');

        expect($page->filter(Selectors::formSubmittingTo('create')))->toHaveCount(1)
            ->and($page)->toContainButtons(Selectors::FORM_ACTIONS . ' ' . ActionsSelectors::TRIGGER, ['Create', 'Cancel'])
            ->and($page)->toContainElement(Selectors::FORM_SUBMIT_BUTTON, 'Create')
            ->and($page)->toContainElement(Selectors::FORM_ACTIONS . ' a[href="http://localhost/admin/articles"]' . SupportSelectors::GRAY, 'Cancel');
    });

    it("checks a form-row modal that names no record class against the resource's", function (): void {
        $this->seed(new Plant('Fern'));

        $repot = TestAction::make('repot')->schemaComponent('form-actions', schema: 'formActions');

        $this->createLiveComponent(CreateGroupedRowPottedPlant::class, ['panelId' => 'create-shapes'])
            ->callAction($repot, data: ['name' => 'Fern'])
            ->assertHasActionErrors(['name'])
            ->fillForm(['name' => 'Ivy'])
            ->callMountedAction()
            ->assertHasNoActionErrors()
            ->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['repot']);
    });
});

describe('a create', function (): void {
    it('writes the typed record, toasts "Created" and redirects to its edit page', function (): void {
        $tester = $this->createLiveComponent(CreatePottedPlant::class, ['panelId' => 'create-shapes'])
            ->fillForm(['name' => 'Fern'])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Created');

        $plants = $this->entityManager()->getRepository(Plant::class)->findAll();

        expect($plants)->sequence(fn ($plant) => $plant->toBeRecord(name: 'Fern'));

        $tester->assertRedirect("/create-shapes/potted-plants/{$plants[0]->id}/edit");
    });

    it('writes nothing, toasts nothing and stays on the page when a required field is blank', function (): void {
        $this->createLiveComponent(CreatePottedPlant::class, ['panelId' => 'create-shapes'])
            ->fillForm(['name' => ''])
            ->call('create')
            ->assertHasFormErrors(['name' => RequiredValue::class])
            ->assertNoRedirect()
            ->assertNotNotified();

        expect($this->entityManager()->getRepository(Plant::class)->count())->toBe(0);
    });

    it("runs Filament's ladder in order, writes the mutator's answer, and the page it lands on shows the toast", function (): void {
        $client = $this::getContainer()->get('test.client');
        $tester = $this->createLiveComponent(CreateHookedPottedPlant::class, ['panelId' => 'create-shapes'], client: $client);
        $tester->render();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['beforeFill', 'afterFill']);

        $tester->fillForm(['name' => 'Fern'])->call('create');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['beforeValidate', 'afterValidate', 'mutateFormDataBeforeCreate', 'beforeCreate', 'afterCreate']);

        $plants = $this->entityManager()->getRepository(Plant::class)->findAll();

        expect($plants)->sequence(fn ($plant) => $plant->toBeRecord(name: 'FERN'));

        $tester->assertRedirect($uri = "/create-shapes/potted-plants/{$plants[0]->id}/edit");

        expect($this->visit($uri, client: $client))->toContainElement(NotificationsSelectors::TITLE, 'Created');
    });

    it('stops where a hook halts: nothing is written, toasted or redirected', function (): void {
        $this->createLiveComponent(CreateHookedPottedPlant::class, ['panelId' => 'create-shapes'])
            ->fillForm(['name' => CreateHookedPottedPlant::HALTING_NAME])
            ->call('create')
            ->assertNoRedirect()
            ->assertNotNotified();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['beforeValidate', 'afterValidate', 'mutateFormDataBeforeCreate', 'beforeCreate'])
            ->and($this->entityManager()->getRepository(Plant::class)->count())->toBe(0);
    });

    it('refuses by name a value its mutator adds that no field maps, before beforeCreate or any write', function (): void {
        $tester = $this->createLiveComponent(CreateHookedPottedPlant::class, ['panelId' => 'create-shapes'])
            ->fillForm(['name' => CreateHookedPottedPlant::UNMAPPED_NAME]);

        expect(fn () => $tester->call('create'))->toThrow(LogicException::class, 'The resource page [' . CreateHookedPottedPlant::class . '] added [status] in mutateFormDataBeforeCreate(), but no component of its form maps those paths, so the default write would drop them.');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['beforeValidate', 'afterValidate', 'mutateFormDataBeforeCreate'])
            ->and($this->entityManager()->getRepository(Plant::class)->count())->toBe(0);
    });

    it('never writes, nor refuses, a key the client typed into the form data that no field maps', function (): void {
        $this->createLiveComponent(CreatePottedPlant::class, ['panelId' => 'create-shapes'])
            ->set('data', ['name' => 'Fern', 'status' => 'potted'])
            ->call('create')
            ->assertRedirect();

        expect($this->entityManager()->getRepository(Plant::class)->findAll())->sequence(fn ($plant) => $plant->toBeRecord(name: 'Fern'));
    });

    it('toasts nothing when its notification title is blank, and still writes and redirects', function (): void {
        $this->createLiveComponent(CreateBarePottedPlant::class, ['panelId' => 'create-shapes'])
            ->call('create')
            ->assertRedirect()
            ->assertNotNotified();

        expect($this->entityManager()->getRepository(Plant::class)->findAll())->sequence(fn ($plant) => $plant->toBeRecord(name: CreateBarePottedPlant::FILLED_NAME));
    });
});

describe('the record it builds', function (): void {
    it('refuses by name a record class whose constructor requires arguments', function (): void {
        $tester = $this->createLiveComponent(CreateArticle::class)->fillForm(['title' => 'Draft']);

        expect(fn () => $tester->call('create'))->toThrow(LogicException::class, 'Cannot map a new record on the resource page [' . CreateArticle::class . ']: ' . Article::class . ' requires constructor arguments, so the mapper cannot build it.');

        expect($this->entityManager()->getRepository(Article::class)->count())->toBe(0);
    });

    it("is built by a makeRecord() that hands the constructor its key, and lands on the listing of a resource with no edit page", function (): void {
        $this->createLiveComponent(CreateLowercasedBadge::class, ['panelId' => 'create-shapes'])
            ->fillForm(['code' => 'moon', 'label' => 'Moon'])
            ->call('create')
            ->assertRedirect('/create-shapes/lowercased-badges');

        expect($this->entityManager()->getRepository(ConstructorCodedBadge::class)->findAll())->sequence(fn ($badge) => $badge->toBeRecord(code: 'moon', label: 'Moon'));
    });

    it('refuses by name a key the form writes that the constructor kept another way', function (): void {
        $tester = $this->createLiveComponent(CreateLowercasedBadge::class, ['panelId' => 'create-shapes'])
            ->fillForm(['code' => 'MOON', 'label' => 'Moon']);

        expect(fn () => $tester->call('create'))->toThrow(LogicException::class, 'Cannot map state path "code" onto ' . ConstructorCodedBadge::class . ': no setter can change that property, and it already holds another value');

        expect($this->entityManager()->getRepository(ConstructorCodedBadge::class)->count())->toBe(0);
    });

    it('refuses by name a makeRecord() answer that is no record of its resource, writing nothing', function (): void {
        $tester = $this->createLiveComponent(CreateLowercasedBadge::class, ['panelId' => 'create-shapes'])
            ->fillForm(['code' => CreateLowercasedBadge::STRAY_CODE, 'label' => 'Stray']);

        expect(fn () => $tester->call('create'))->toThrow(LogicException::class, 'built a [' . CodeKeyedSlate::class . '] in makeRecord(), but its resource');

        expect($this->entityManager()->getRepository(CodeKeyedSlate::class)->count())->toBe(0);
    });

    it('files a taken key under its field rather than failing the write', function (): void {
        $this->seed(new ConstructorCodedBadge('moon', 'Stored'));

        $tester = $this->createLiveComponent(CreateLowercasedBadge::class, ['panelId' => 'create-shapes'])
            ->fillForm(['code' => 'moon', 'label' => 'Moon'])
            ->call('create')
            ->assertHasFormErrors(['code'])
            ->assertNoRedirect();

        expect($tester->render()->crawler()->text())->toContain('This value is already used.')
            ->and($this->entityManager()->getRepository(ConstructorCodedBadge::class)->findAll())->sequence(fn ($badge) => $badge->toBeRecord(code: 'moon', label: 'Stored'));
    });

    it("shows a refusal that names no field in the form's record errors", function (): void {
        $this->seed(new CappedHeadline('old', 'Old', CreateAssignedSlugHeadline::SLUG));

        expect($this->visit('/create-shapes/assigned-slug-headlines/create')->filter(ActionsSelectors::RECORD_ERRORS))->toHaveCount(0);

        $tester = $this->createLiveComponent(CreateAssignedSlugHeadline::class, ['panelId' => 'create-shapes'])
            ->fillForm(['code' => 'new', 'title' => 'News'])
            ->call('create')
            ->assertNoRedirect();

        expect($tester->render()->crawler()->filter(Selectors::FORM . ' ' . ActionsSelectors::RECORD_ERROR)->each(static fn (Crawler $error): string => $error->text()))->toBe(['This value is already used.'])
            ->and($this->entityManager()->getRepository(CappedHeadline::class)->count())->toBe(1);
    });

    it('redirects to the key the new row reads back, not the spelling typed', function (): void {
        $this->createLiveComponent(CreateCaselessBollard::class, ['panelId' => 'create-shapes'])
            ->fillForm(['code' => 'MOON', 'label' => 'Moon'])
            ->call('create')
            ->assertRedirect('/create-shapes/caseless-bollards/moon/edit');

        expect($this->visit('/create-shapes/caseless-bollards/moon/edit')->filter(Selectors::field('label'))->attr('value'))->toBe('Moon')
            ->and($this->entityManager()->getRepository(QuayBollard::class)->count())->toBe(1);
    });

    it("asks the form's own reader, rebound to the manager the page writes through", function (): void {
        $this->createLiveComponent(CreateSecondManagerSpine::class, ['panelId' => 'create-shapes'])
            ->fillForm(['code' => 'moon', 'label' => 'Moon'])
            ->call('create')
            ->assertRedirect('/create-shapes/second-manager-spines');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['key check on a ' . VolumeSpine::class . ' by the reader rebound to the second manager', 'blank-key read on a ' . VolumeSpine::class . ' by the reader rebound to the second manager'])
            ->and($this->entityManager()->getRepository(VolumeSpine::class)->findAll())->sequence(fn ($spine) => $spine->toBeRecord(code: 'moon', label: 'Moon'));
    });
});

describe('a key still blank after persist()', function (): void {
    it('is refused by name before the flush, and nothing is written', function (string $page, Closure $parent, string $parameter, array $data, string $recordClass, string $advice): void {
        $this->seed($parentRecord = $parent());

        $tester = $this->createLiveComponent($page, ['panelId' => 'create-shapes', 'parentRecords' => [$parameter => (string) $parentRecord->id]])
            ->set('data', $data);

        expect(fn () => $tester->call('create'))->toThrow(LogicException::class, "The resource page [{$page}] built a [{$recordClass}] whose identifier is still blank after persist(), so it was not saved. Give every identifier field a form field — a Hidden one for a value the user should not type — or fill it in a prePersist listener, in makeRecord(), or in handleRecordCreation() before calling the parent.{$advice}");

        expect($this->entityManager()->getRepository($recordClass)->count())->toBe(0);
    })->with([
        'scalar fields' => [
            CreateUnvettedKeyCharm::class,
            fn (): CharmSatchel => new CharmSatchel('Leather'),
            'charm_satchel',
            ['tier' => '2', 'code' => '  ', 'label' => 'Blank'],
            SatchelCharm::class,
            ' The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.',
        ],
        'an association plus a scalar' => [
            CreateShelflessCrock::class,
            fn (): Larder => new Larder('Cellar'),
            'larder',
            ['label' => 'Jam'],
            LarderCrock::class,
            '',
        ],
    ]);

    it("is refused by name when the whole key flattens to '', which stays scheduled and is never written", function (): void {
        $tester = $this->createLiveComponent(CreateUnkeyedLocket::class, ['panelId' => 'create-shapes'])->fillForm(['label' => 'Gold']);

        expect(fn () => $tester->call('create'))->toThrow(LogicException::class, 'The resource page [' . CreateUnkeyedLocket::class . '] built a [' . ClaspedLocket::class . '] whose identifier is still blank after persist(), so it was not saved.');

        expect(array_map(get_class(...), array_values($this->entityManager()->getUnitOfWork()->getScheduledEntityInsertions())))->toContain(ClaspedLocket::class)
            ->and($this->statementsRun())->toBe([])
            ->and($this->entityManager()->getRepository(ClaspedLocket::class)->count())->toBe(0);
    });

    it('is saved once a prePersist listener fills it, since the read runs after persist()', function (): void {
        $this->createLiveComponent(CreateListenerStampedBulletin::class, ['panelId' => 'create-shapes'])
            ->fillForm(['scope' => 'news', 'label' => 'Morning'])
            ->call('create')
            ->assertRedirect('/create-shapes/listener-stamped-bulletins');

        $saved = new StampedBulletin();
        $saved->scope = 'news';
        $saved->stamp = 'auto';

        expect($this->reload($saved))->toBeRecord(label: 'Morning');
    });

    it('is saved once the page sets it before the default write', function (): void {
        $this->createLiveComponent(CreateUnkeyedLocket::class, ['panelId' => 'create-shapes'])
            ->fillForm(['label' => CreateUnkeyedLocket::KEYED_LABEL])
            ->call('create')
            ->assertRedirect('/create-shapes/unkeyed-lockets');

        expect($this->entityManager()->getRepository(ClaspedLocket::class)->findAll())->sequence(fn ($locket) => $locket->label->toBe(CreateUnkeyedLocket::KEYED_LABEL)->and($locket->value->clasp->code)->toBe(CreateUnkeyedLocket::CLASP_CODE));
    });
});

describe('under a parent', function (): void {
    it('takes a refused record back out of its many-to-many parent, so a later flush in the request succeeds', function (array $data, Closure $refuse): void {
        $stored = new SatchelCharm(1, 'a', 'Old');
        $satchel = new CharmSatchel('Leather');
        $satchel->addCharm($stored);
        $this->seed($stored, $satchel);

        $refuse($this->createLiveComponent(CreateUnvettedKeyCharm::class, ['panelId' => 'create-shapes', 'parentRecords' => ['charm_satchel' => (string) $satchel->id]])->set('data', $data));

        expect($this->entityManager()->find(CharmSatchel::class, $satchel->id)?->charms->map(static fn (SatchelCharm $charm): string => $charm->label)->getValues())->toBe(['Old']);

        $this->entityManager()->flush();

        expect($this->entityManager()->getConnection()->fetchAllAssociative('SELECT charm_tier, charm_code FROM charm_satchel_charm'))->toBe([['charm_tier' => 1, 'charm_code' => 'a']]);
    })->with([
        'by the mapping' => [
            ['tier' => '2', 'code' => 'b', 'label' => 'Too long'],
            function (Testable $tester): void {
                $tester->call('create')->assertHasFormErrors(['label']);
            },
        ],
        'by the blank-key read after persist()' => [
            ['tier' => '2', 'code' => '  ', 'label' => 'Blank'],
            function (Testable $tester): void {
                expect(fn (): Testable => $tester->call('create'))->toThrow(LogicException::class, 'whose identifier is still blank after persist()');
            },
        ],
        'by persist(), whose refusal is rethrown unchanged' => [
            ['tier' => '', 'code' => 'c', 'label' => 'Null'],
            function (Testable $tester): void {
                expect(fn (): Testable => $tester->call('create'))->toThrow(EntityMissingAssignedId::class, "missing an assigned ID for field  'tier'");
            },
        ],
    ]);

    it('keeps a saved record in the many-to-many parent it was created under', function (): void {
        $stored = new SatchelCharm(1, 'a', 'Old');
        $satchel = new CharmSatchel('Leather');
        $satchel->addCharm($stored);
        $this->seed($stored, $satchel);

        $this->createLiveComponent(CreateUnvettedKeyCharm::class, ['panelId' => 'create-shapes', 'parentRecords' => ['charm_satchel' => (string) $satchel->id]])
            ->set('data', ['tier' => '2', 'code' => 'd', 'label' => 'New'])
            ->call('create')
            ->assertRedirect();

        expect($this->entityManager()->find(CharmSatchel::class, $satchel->id)?->charms->map(static fn (SatchelCharm $charm): string => $charm->label)->getValues())->toBe(['Old', 'New'])
            ->and($this->entityManager()->getConnection()->fetchAllAssociative('SELECT charm_tier, charm_code FROM charm_satchel_charm ORDER BY charm_tier'))->toBe([['charm_tier' => 1, 'charm_code' => 'a'], ['charm_tier' => 2, 'charm_code' => 'd']]);
    });

    it('joins its parent before the mapping, so a key the parent half-fills is filed taken under the field the form maps', function (): void {
        $larder = new Larder('Cellar');
        $larder->addCrock(new LarderCrock(shelf: 'top', label: 'Stored'));
        $this->seed($larder);

        $tester = $this->createLiveComponent(CreateShelvedCrock::class, ['panelId' => 'create-shapes', 'parentRecords' => ['larder' => (string) $larder->id]])
            ->fillForm(['shelf' => 'top', 'label' => 'Jam'])
            ->call('create')
            ->assertHasFormErrors(['shelf'])
            ->assertNoRedirect();

        expect($tester->render()->crawler()->text())->toContain('This value is already used.')
            ->and($this->entityManager()->getRepository(LarderCrock::class)->findAll())->sequence(fn ($crock) => $crock->toBeRecord(shelf: 'top', label: 'Stored'));
    });

    it("builds a child whose constructor takes the page's own parent, which both attaches leave alone", function (): void {
        $this->seed($monument = new Monument('Tower'));

        $this->createLiveComponent(CreateMonumentPlaque::class, ['panelId' => 'create-shapes', 'parentRecords' => ['monument' => (string) $monument->id]])
            ->fillForm(['locale' => 'en', 'inscription' => 'Built 1890'])
            ->call('create')
            ->assertRedirect();

        expect($this->entityManager()->getRepository(MonumentPlaque::class)->findAll())->sequence(fn ($plaque) => $plaque->locale->toBe('en')->and($plaque->value->monument->id)->toBe($monument->id));
    });

    it('refuses by name a child whose constructor took another parent, saving it under neither', function (): void {
        $this->seed($monument = new Monument('Tower'));

        $tester = $this->createLiveComponent(CreateMonumentPlaque::class, ['panelId' => 'create-shapes', 'parentRecords' => ['monument' => (string) $monument->id]])
            ->fillForm(['locale' => CreateMonumentPlaque::STRANGER_LOCALE, 'inscription' => 'Built 1890']);

        expect(fn () => $tester->call('create'))->toThrow(LogicException::class, 'Cannot write association "monument" onto ' . MonumentPlaque::class . ': no setter can change that property, and it already holds another value');

        expect($this->entityManager()->getRepository(MonumentPlaque::class)->count())->toBe(0)
            ->and($this->entityManager()->getRepository(Monument::class)->count())->toBe(1);
    });
});
