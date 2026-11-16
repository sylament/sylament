<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Support\Enums\Alignment;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('aligns its footer actions as it is told, at the start unless told otherwise', function (?Alignment $alignment, string $rendered): void {
    $section = Section::make('Shipping')->footerActions([Action::make('save')])->components([Text::make('Body')]);

    if ($alignment instanceof Alignment) {
        $section->footerActionsAlignment($alignment);
    }

    expect($this->renderSchemaComponent($section))
        ->toContainElement('section footer ' . Selectors::SCHEMA . SupportSelectors::align($rendered), 'Save');
})->with([
    'unset' => [null, 'start'],
    'end' => [Alignment::End, 'end'],
    'right, as the end' => [Alignment::Right, 'end'],
    'center' => [Alignment::Center, 'center'],
    'between' => [Alignment::Between, 'between'],
    'justify, as between' => [Alignment::Justify, 'between'],
]);
