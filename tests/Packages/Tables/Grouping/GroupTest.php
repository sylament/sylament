<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\GroupableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

describe('keys and titles', function (): void {
    it("heads a backed enum's group with its backing value, as it keys it", function (): void {
        $this->seed(new LarderJar('Apple', seal: JarSeal::Wax), new LarderJar('Fig', seal: JarSeal::Screw));

        $headers = $this->createLiveComponent(GroupableLarderJarTable::class, ['tableGrouping' => 'seal'])->render()->crawler()->filter(Selectors::GROUP_HEADER_ROW);

        expect($headers->each(static fn (Crawler $header): array => [$header->attr('data-sy-group'), $header->filter('.sy-ta-group-heading')->text()]))->toBe([
            ['screw', 'Seal: screw'],
            ['wax', 'Seal: wax'],
        ])
            ->and($headers->last()->attr('id'))->toEndWith('.table.groups.wax');
    });
});
