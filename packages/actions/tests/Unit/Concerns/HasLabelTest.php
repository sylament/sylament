<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\HasLabelFixture;

it('derives the label from the name', function (): void {
    expect(new HasLabelFixture()->name('delete')->getLabel())->toBe('Delete');
});

it('derives the label from the segment before the first dot', function (): void {
    expect(new HasLabelFixture()->name('user.edit')->getLabel())->toBe('User');
});

it('lets an explicit label win over the name default', function (): void {
    expect(new HasLabelFixture()->name('delete')->label('Remove')->getLabel())->toBe('Remove');
});

it('evaluates a label closure', function (): void {
    expect(new HasLabelFixture()->name('delete')->label(fn (): string => 'Dynamic')->getLabel())
        ->toBe('Dynamic');
});

it('does not hide the label by default', function (): void {
    $fixture = new HasLabelFixture()->name('delete');

    expect($fixture->isLabelHidden())->toBeFalse()
        ->and($fixture->hasLabelHidden())->toBeFalse();
});

it('hides the label once hiddenLabel() is called', function (): void {
    expect(new HasLabelFixture()->name('delete')->hiddenLabel()->isLabelHidden())->toBeTrue();
});

it('evaluates a hiddenLabel closure', function (): void {
    expect(new HasLabelFixture()->name('delete')->hiddenLabel(fn (): bool => false)->isLabelHidden())
        ->toBeFalse();
});

it('reports the raw hidden-label value through hasLabelHidden()', function (): void {
    $fixture = new HasLabelFixture()->name('delete')->hiddenLabel(fn (): ?bool => null);

    expect($fixture->isLabelHidden())->toBeFalse()
        ->and($fixture->hasLabelHidden())->toBeTrue();
});
