<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Tables\Filters\BaseFilter;
use Sylament\Tables\Filters\SelectFilter;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Tables\FilterableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\PairedFieldJarFilterTable;
use Sylament\Tests\Fixtures\Tables\SealedJarFilter;

it('turns on a ternary filter given only its name', function (): void {
    $this->seed($open = new LarderJar('Open'), new LarderJar('Plum', sealedOn: new DateTimeImmutable('2026-01-01')));

    $this->createLiveComponent(FilterableLarderJarTable::class)
        ->filterTable('sealedOn')
        ->assertCanNotSeeTableRecords([$open]);
});

it('sets a ternary filter to the value given', function (): void {
    $this->seed(new LarderJar('Open'), $plum = new LarderJar('Plum', sealedOn: new DateTimeImmutable('2026-01-01')));

    $this->createLiveComponent(FilterableLarderJarTable::class)
        ->filterTable('sealedOn', false)
        ->assertCanNotSeeTableRecords([$plum]);
});

it("selects an enum's value in a select filter", function (): void {
    $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw));

    $this->createLiveComponent(FilterableLarderJarTable::class)
        ->filterTable('seal', JarSeal::Wax)
        ->assertCanSeeTableRecords([$plum])
        ->assertCanNotSeeTableRecords([$fig]);
});

it('selects several values in a multiple select filter', function (): void {
    $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw), $open = new LarderJar('Open'));

    $this->createLiveComponent(FilterableLarderJarTable::class)
        ->filterTable('seals', [JarSeal::Wax, 'screw'])
        ->assertCanSeeTableRecords([$plum, $fig])
        ->assertCanNotSeeTableRecords([$open]);
});

it('selects a record by the key the table mints for it', function (): void {
    $this->seed($farm = new Product('Farm'), $mill = new Product('Mill'), $plum = new LarderJar('Plum', supplier: $farm), $fig = new LarderJar('Fig', supplier: $mill));

    $this->createLiveComponent(FilterableLarderJarTable::class)
        ->filterTable('supplier', $farm)
        ->assertCanSeeTableRecords([$plum])
        ->assertCanNotSeeTableRecords([$fig]);
});

it('turns on a plain filter given only its name', function (): void {
    $this->seed($unlabelled = new LarderJar(), new LarderJar('Plum'));

    $this->createLiveComponent(FilterableLarderJarTable::class)
        ->filterTable('labelled')
        ->assertCanNotSeeTableRecords([$unlabelled]);
});

it('sets a plain filter to the data given as an array', function (): void {
    $this->seed($unlabelled = new LarderJar(), new LarderJar('Plum'));

    $this->createLiveComponent(FilterableLarderJarTable::class)
        ->filterTable('labelled', ['isActive' => true])
        ->assertCanNotSeeTableRecords([$unlabelled]);
});

it('resets the filters', function (): void {
    $this->seed(new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw));

    $this->createLiveComponent(FilterableLarderJarTable::class)
        ->filterTable('seal', JarSeal::Wax)
        ->resetTableFilters()
        ->assertCanSeeTableRecords([$fig]);
});

it('keeps the other filters applied as one is set and then removed', function (): void {
    $this->seed(new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw), $unlabelled = new LarderJar(seal: JarSeal::Wax));

    $this->createLiveComponent(FilterableLarderJarTable::class)
        ->filterTable('seal', JarSeal::Wax)
        ->filterTable('labelled')
        ->assertCanNotSeeTableRecords([$fig, $unlabelled])
        ->removeTableFilter('seal')
        ->assertCanSeeTableRecords([$fig])
        ->assertCanNotSeeTableRecords([$unlabelled]);
});

it('removes every filter', function (): void {
    $this->seed(new LarderJar('Plum', seal: JarSeal::Wax), $fig = new LarderJar('Fig', seal: JarSeal::Screw));

    $this->createLiveComponent(FilterableLarderJarTable::class)
        ->filterTable('seal', JarSeal::Wax)
        ->removeTableFilters()
        ->assertCanSeeTableRecords([$fig]);
});

it('fails asserting that a table filter exists when the table has none of that name', function (): void {
    expect(fn () => $this->createLiveComponent(FilterableLarderJarTable::class)->assertTableFilterExists('shelf'))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that a table filter with name [shelf] exists on the [' . FilterableLarderJarTable::class . '] component.');
});

it("fails asserting a filter's configuration it does not have", function (): void {
    $isMultiple = static fn (BaseFilter $filter): bool => ($filter instanceof SelectFilter) && $filter->isMultiple();

    $tester = $this->createLiveComponent(FilterableLarderJarTable::class)->assertTableFilterExists('seals', $isMultiple);

    expect(fn () => $tester->assertTableFilterExists('seal', $isMultiple))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that a table filter with name [seal] and provided configuration exists on the [' . FilterableLarderJarTable::class . '] component.');
});

it("resets only the field of a filter's schema it is given, keeping the other field's narrowing", function (): void {
    $this->seed($low = new LarderJar('Plum', position: 1), $high = new LarderJar('Plum', position: 4), $fig = new LarderJar('Fig', position: 4));

    $this->createLiveComponent(PairedFieldJarFilterTable::class)
        ->filterTable('shelf', ['label' => 'Plum', 'lowestPosition' => '3'])
        ->assertCanSeeTableRecords([$high])
        ->assertCanNotSeeTableRecords([$low, $fig])
        ->removeTableFilter('shelf', 'label')
        ->assertCanSeeTableRecords([$high, $fig])
        ->assertCanNotSeeTableRecords([$low]);
});

it('names a filter given as its class by the default name the class gives it', function (): void {
    $this->seed($open = new LarderJar('Plum'), $sealed = new LarderJar('Fig', seal: JarSeal::Wax));

    $this->createLiveComponent(PairedFieldJarFilterTable::class)
        ->assertTableFilterExists(SealedJarFilter::class)
        ->filterTable(SealedJarFilter::class)
        ->assertCanSeeTableRecords([$sealed])
        ->assertCanNotSeeTableRecords([$open])
        ->removeTableFilter(SealedJarFilter::class)
        ->assertCanSeeTableRecords([$open, $sealed]);
});
