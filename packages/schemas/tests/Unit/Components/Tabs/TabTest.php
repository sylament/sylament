<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Tabs;

use Sylament\Schemas\Components\Contracts\CanConcealComponents;
use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Support\Enums\IconPosition;
use Twig\Markup;

/**
 * Wire the tab into a real Tabs inside a real Schema, as a render does: the key closure
 * reads the state path, and `getAllColumns()` falls back to the container.
 */
function wireTab(Tab $tab, ?Tabs $tabs = null, ?Schema $schema = null): Tab
{
    $tabs ??= Tabs::make('Article');
    $schema ??= Schema::make(new HasSchemasFixture());

    $schema->components([$tabs->tabs([$tab])])->getComponents();
    $tabs->getTabs();

    return $tab;
}

it('keys itself from its label, non-inheritable', function (): void {
    $tab = wireTab(Tab::make('Content'));

    expect($tab->getKey(isAbsolute: false))->toBe('content::tab')
        ->and($tab->isKeyInheritable())->toBeFalse();
});

it('conceals its components', function (): void {
    expect(Tab::make('Content'))->toBeInstanceOf(CanConcealComponents::class)
        ->and(Tab::make('Content')->canConcealComponents())->toBeTrue();
});

it('applies configureUsing() closures on make()', function (): void {
    Tab::configureUsing(function (Tab $tab): void {
        $tab->badge('7');
    });

    expect(Tab::make('Content')->getBadge())->toBe('7');
});

it('falls back to the container columns until it sets its own', function (): void {
    $tab = wireTab(Tab::make('Content'));

    expect($tab->getAllColumns())->toBe($tab->getContainer()->getAllColumns());

    $own = wireTab(Tab::make('Meta')->columns(3));

    expect($own->getAllColumns()['lg'])->toBe(3);
});

it('carries a label, icon and icon position', function (): void {
    $tab = Tab::make('Content')->icon('heroicons:star')->iconPosition(IconPosition::After);

    expect($tab->getLabel())->toBe('Content')
        ->and($tab->getIcon())->toBe('heroicons:star')
        ->and($tab->getIconPosition())->toBe(IconPosition::After);
});

it('carries a badge with a colour and a tooltip', function (): void {
    $tab = Tab::make('Content')->badge(3)->badgeColor('success')->badgeTooltip('Three drafts');

    expect($tab->getBadge())->toBe('3')
        ->and($tab->getBadgeColor())->toBe('success')
        ->and($tab->getBadgeTooltip())->toBe('Three drafts')
        ->and($tab->hasBadge())->toBeTrue();
});

it('renders a Markup badge tooltip untouched', function (): void {
    $markup = new Markup('<b>Three</b>', 'UTF-8');

    expect(Tab::make('Content')->badgeTooltip($markup)->getBadgeTooltip())->toBe($markup);
});

it('positions the badge icon before by default and evaluates both with the badge', function (): void {
    $plain = Tab::make('Content');

    expect($plain->getBadgeIcon())->toBeNull()
        ->and($plain->getBadgeIconPosition())->toBe(IconPosition::Before);

    $tab = Tab::make('Content')
        ->badge('new')
        ->badgeIcon(fn (?string $badge): string => $badge === 'new' ? 'heroicons:sparkles' : 'heroicons:star')
        ->badgeIconPosition(fn (?string $badge): IconPosition => $badge === 'new' ? IconPosition::After : IconPosition::Before);

    expect($tab->getBadgeIcon('new'))->toBe('heroicons:sparkles')
        ->and($tab->getBadgeIcon('old'))->toBe('heroicons:star')
        ->and($tab->getBadgeIconPosition('new'))->toBe(IconPosition::After)
        ->and($tab->getBadgeIconPosition('old'))->toBe(IconPosition::Before);
});

it('resolves a string badge icon position through the enum', function (): void {
    expect(Tab::make('Content')->badgeIconPosition('after')->getBadgeIconPosition())->toBe('after');
});

it('defers the badge only when asked', function (): void {
    expect(Tab::make('Content')->isBadgeDeferred())->toBeFalse()
        ->and(Tab::make('Content')->deferBadge()->isBadgeDeferred())->toBeTrue()
        ->and(Tab::make('Content')->deferBadge(false)->isBadgeDeferred())->toBeFalse()
        ->and(Tab::make('Content')->deferBadge(fn (): bool => true)->isBadgeDeferred())->toBeTrue();
});

it('returns the query untouched without a modifier', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('post')->from(Post::class, 'post');

    expect(Tab::make('Drafts')->modifyQuery($query))->toBe($query)
        ->and($query->getDQLPart('where'))->toBeNull();
});

it('applies a query() modifier in place', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('post')->from(Post::class, 'post');

    $tab = Tab::make('Drafts')->query(static function ($query): void {
        $query->andWhere('post.title IS NOT NULL');
    });

    expect($tab->modifyQuery($query))->toBe($query)
        ->and((string) $query->getDQLPart('where'))->toBe('post.title IS NOT NULL');
});

it('modifyQueryUsing() is the same setter as query()', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('post')->from(Post::class, 'post');

    Tab::make('Drafts')
        ->modifyQueryUsing(static function ($query): void {
            $query->andWhere('post.title IS NULL');
        })
        ->modifyQuery($query);

    expect((string) $query->getDQLPart('where'))->toBe('post.title IS NULL');
});

it('excludes the query when resolving a record only when asked', function (): void {
    expect(Tab::make('Drafts')->shouldExcludeQueryWhenResolvingRecord())->toBeFalse()
        ->and(Tab::make('Drafts')->excludeQueryWhenResolvingRecord()->shouldExcludeQueryWhenResolvingRecord())->toBeTrue()
        ->and(Tab::make('Drafts')->excludeQueryWhenResolvingRecord(fn (): bool => true)->shouldExcludeQueryWhenResolvingRecord())->toBeTrue();
});
