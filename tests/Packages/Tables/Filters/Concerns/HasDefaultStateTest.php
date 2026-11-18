<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\DefaultedJarFilterTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('fills its field from its default and filters by it from the first render', function (): void {
    $this->seed(
        $kept = new LarderJar('Plum', seal: JarSeal::Wax),
        $screwed = new LarderJar('Fig', seal: JarSeal::Screw),
        $unsealed = new LarderJar('Open'),
    );

    $tester = $this->createLiveComponent(DefaultedJarFilterTable::class)
        ->assertCanSeeTableRecords([$kept])
        ->assertCanNotSeeTableRecords([$screwed, $unsealed]);

    expect($tester->render()->crawler())
        ->toContainElement(Selectors::filterField('sealed', 'isActive') . '[checked]')
        ->toContainElement(Selectors::filterField('seal', 'value') . ' option[value="wax"][selected]');
});
