<?php

declare(strict_types=1);

use Sylament\Forms\Components\KeyValue;

it('hands the sortable controller its duration', function (Closure $field, string $duration): void {
    $crawler = $this->renderSchemaComponent($field(), ['pairs' => [['key' => 'colour', 'value' => 'red']]]);

    expect($crawler->filter('[data-controller~="sylament--support--sortable"]')->attr('data-sylament--support--sortable-animation-duration-value'))->toBe($duration);
})->with([
    'by default' => [fn (): KeyValue => KeyValue::make('pairs')->reorderable(), '300'],
    'reorderAnimationDuration()' => [fn (): KeyValue => KeyValue::make('pairs')->reorderable()->reorderAnimationDuration(150), '150'],
]);
