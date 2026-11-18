<?php

declare(strict_types=1);

use Sylament\Panels\DependencyInjection\Compiler\PageComponentPass;
use Sylament\Panels\Pages\Page;
use Sylament\Tests\Fixtures\Panels\Pages\NotesPage;
use Sylament\Tests\Fixtures\Panels\Pages\OrphanPage;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('makes a registered page a live component that renders and answers its route', function (): void {
    expect($this->createLiveComponent(NotesPage::class)->call('refresh')->render()->crawler()->filter(Selectors::PAGE_HEADING)->text())->toBe('Notes Page')
        ->and($this->visit('/pages/notes-page')->filter(Selectors::PAGE . ' ' . Selectors::PAGE_HEADING)->text())->toBe('Notes Page');
});

it('makes a page no panel lists a live component too, with no route of its own', function (): void {
    expect($this->createLiveComponent(OrphanPage::class)->call('refresh')->render()->crawler()->filter(Selectors::PAGE))->toHaveCount(1)
        ->and(fn () => $this->visit('/pages/orphan-page'))->toThrow(NotFoundHttpException::class);
});

it('refuses a page without a bare #[AsLiveComponent], naming its class, and skips an abstract definition', function (): void {
    $unmarked = new class extends Page {};

    $abstract = new ContainerBuilder();
    $abstract->register('unmarked', $unmarked::class)->setAbstract(true)->addTag('sylament.panels.page');

    $concrete = new ContainerBuilder();
    $concrete->register('unmarked', $unmarked::class)->addTag('sylament.panels.page');

    expect(new PageComponentPass()->process($abstract))->toBeNull()
        ->and(fn () => new PageComponentPass()->process($concrete))->toThrow(LogicException::class, sprintf('Panel page "%s" must carry a bare #[AsLiveComponent] attribute.', $unmarked::class));
});

it('refuses two pages deriving one component name, naming both services', function (): void {
    $container = new ContainerBuilder();
    $container->register('notes', NotesPage::class)->addTag('sylament.panels.page');
    $container->register('notes.again', NotesPage::class)->addTag('sylament.panels.page');

    expect(fn () => new PageComponentPass()->process($container))->toThrow(LogicException::class, 'Two panel pages derive the live component name "Sylament:Tests:Fixtures:Panels:Pages:NotesPage": notes and notes.again.');
});
