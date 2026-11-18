<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\QueryingJarFilterTable;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;

it('narrows the rows with its query while ticked, and leaves them alone unticked or without a query', function (): void {
    $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw));

    $this->createLiveComponent(ShapedJarFilterTable::class)
        ->filterTable('owned')
        ->assertCanSeeTableRecords([$plum, $fig])
        ->filterTable('waxed')
        ->assertCanSeeTableRecords([$plum])
        ->assertCanNotSeeTableRecords([$fig])
        ->filterTable('waxed', false)
        ->assertCanSeeTableRecords([$plum, $fig]);
});

it('narrows the rows with its base query while ticked', function (): void {
    $this->seed($plum = new LarderJar('Plum', position: 1), $unlabelled = new LarderJar(position: 1));

    $this->createLiveComponent(QueryingJarFilterTable::class)
        ->assertCanSeeTableRecords([$plum, $unlabelled])
        ->filterTable('labelled')
        ->assertCanSeeTableRecords([$plum])
        ->assertCanNotSeeTableRecords([$unlabelled]);
});

it('applies its query with no state when its fields hold no isActive', function (): void {
    $this->seed($shelved = new LarderJar('Plum', position: 1), $loose = new LarderJar('Fig'));

    $this->createLiveComponent(QueryingJarFilterTable::class)
        ->assertCanSeeTableRecords([$shelved])
        ->assertCanNotSeeTableRecords([$loose]);
});

it('composes with the other filters set, keeping an or-condition inside its own pair', function (): void {
    $this->seed(
        $plum = new LarderJar('Plum', seal: JarSeal::Wax, position: 3),
        $fig = new LarderJar('Fig', seal: JarSeal::Screw, position: 1),
        $pear = new LarderJar('Pear', seal: JarSeal::Screw, position: 2),
        $unlabelled = new LarderJar(seal: JarSeal::Wax, position: 2),
    );

    $this->createLiveComponent(QueryingJarFilterTable::class)
        ->filterTable('waxOrFirst')
        ->assertCanSeeTableRecords([$plum, $fig, $unlabelled])
        ->assertCanNotSeeTableRecords([$pear])
        ->filterTable('labelled')
        ->assertCanSeeTableRecords([$plum, $fig])
        ->assertCanNotSeeTableRecords([$pear, $unlabelled]);
});
