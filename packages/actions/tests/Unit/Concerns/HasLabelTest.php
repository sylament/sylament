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
