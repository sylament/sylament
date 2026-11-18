<?php

declare(strict_types=1);

use Sylament\Tables\Columns\Summarizers\Average;
use Sylament\Tests\Fixtures\Entity\LarderJar;

it('averages the values under its label in one statement of its own', function (): void {
    $this->seed(new LarderJar(position: 1), new LarderJar(position: 2));

    $crawler = $this->renderSummarizer(Average::make());

    expect($this::textSummaries($crawler))->toBe(['Average 1.5'])
        ->and(preg_grep('/\bAVG\(/', $this->statementsRun()))->toHaveCount(1);
});
