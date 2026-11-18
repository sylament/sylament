<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;

it("colours the icon of a plain item, and leaves a badged item's icon to its badge", function (): void {
    $plain = $this->renderViewComponent(TextColumn::make('status')->record(['status' => 'live'])->icon('support:star')->iconColor('danger'));
    $badged = $this->renderViewComponent(TextColumn::make('status')->record(['status' => 'live'])->badge()->icon('support:star')->iconColor('danger'));

    expect($plain)->toContainElement(Selectors::TEXT_ITEM . ' svg' . SupportSelectors::color('danger'), 'Star')
        ->and($badged)->toContainElement(SupportSelectors::BADGE . ' svg' . SupportSelectors::GRAY, 'Star');
});
