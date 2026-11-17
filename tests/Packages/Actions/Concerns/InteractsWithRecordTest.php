<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Support\ArrayRecord;
use Sylament\Tests\Fixtures\Actions\Record\IdlessTicket;
use Sylament\Tests\Fixtures\Actions\Record\LooseIdTicket;
use Sylament\Tests\Fixtures\Entity\AisleSeat;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\Product;

use function Symfony\Component\String\u;

describe('the record', function (): void {
    it('drops a record of another class than the declared one, and passes an array record through', function (): void {
        ActionGroup::make([$create = Action::make('create')->recordClass(Product::class)])->record(new Article('Notes'));

        $arrayRecord = ['__key' => '3', 'name' => 'Lamp'];

        expect($create->getRecord())->toBeNull()
            ->and(Action::make('open')->recordClass(Product::class)->record($arrayRecord)->getRecord())->toBe($arrayRecord);
    });
});

describe('the record label', function (): void {
    it("labels a record by its custom label, else by its class's words, title-casing each word", function (): void {
        $byClass = ActionGroup::make()->recordClass(AisleSeat::class);
        $custom = ActionGroup::make()->recordClass(AisleSeat::class)->recordLabel('front row seat');

        expect($byClass->getRecordLabel())->toBe('aisle seat')
            ->and($byClass->getTitleCaseRecordLabel())->toBe('Aisle Seat')
            ->and($custom->getTitleCaseRecordLabel())->toBe('Front Row Seat');
    });

    it('pluralises the label, unless a custom plural is given, title-casing each word', function (): void {
        $byClass = ActionGroup::make()->recordClass(AisleSeat::class);
        $custom = ActionGroup::make()->recordClass(AisleSeat::class)->pluralRecordLabel('seating');

        expect($byClass->getPluralRecordLabel())->toBe('aisle seats')
            ->and($byClass->getTitleCasePluralRecordLabel())->toBe('Aisle Seats')
            ->and($custom->getTitleCasePluralRecordLabel())->toBe('Seating');
    });
});

describe('the record title', function (): void {
    it('titles a record by its recordTitle() closure, handed the record by name and by its class', function (): void {
        $lamp = new Product('Lamp');
        $group = ActionGroup::make()->recordTitle(fn (Product $product, object $record): string => "{$product->name} ({$record->name})");

        expect($group->getRecordTitle($lamp))->toBe('Lamp (Lamp)')
            ->and($group->record($lamp)->getRecordTitle())->toBe('Lamp (Lamp)');
    });

    it('titles a record by its title attribute, along a dotted path, and casts a Stringable value', function (): void {
        $jar = new LarderJar(label: 'Plum jam');
        $jar->larder = new Larder('Cellar');

        expect(ActionGroup::make()->recordTitleAttribute('label')->getRecordTitle($jar))->toBe('Plum jam')
            ->and(ActionGroup::make()->recordTitleAttribute('larder.name')->getRecordTitle($jar))->toBe('Cellar')
            ->and(ActionGroup::make()->recordTitleAttribute('id')->getRecordTitle(new LooseIdTicket(u('T-7'))))->toBe('T-7');
    });

    it('falls back to the record label for an attribute it cannot read, a blank one or one that is no scalar', function (object $record, string $label): void {
        expect(ActionGroup::make()->recordClass($record::class)->recordTitleAttribute('id')->getRecordTitle($record))->toBe($label);
    })->with([
        'unreadable' => fn (): array => [new IdlessTicket('T-7'), 'idless ticket'],
        'blank' => fn (): array => [new LooseIdTicket(''), 'loose id ticket'],
        'not a scalar' => fn (): array => [new LooseIdTicket(['T', 7]), 'loose id ticket'],
    ]);

    it('answers the record label without running the title closure when no record is bound', function (): void {
        $group = ActionGroup::make()->recordClass(Product::class)->recordTitle(static fn (Product $record): string => $record->name);

        expect($group->getRecordTitle())->toBe('product');
    });
});

describe('resolveRecordKey()', function (): void {
    it("answers an object record's id, and an array record's key entry, as a string", function (): void {
        $lamp = new Product('Lamp');
        $lamp->id = 7;

        expect(Action::make('open')->resolveRecordKey($lamp))->toBe('7')
            ->and(Action::make('open')->resolveRecordKey(new LooseIdTicket(u('T-7'))))->toBe('T-7')
            ->and(Action::make('open')->resolveRecordKey([ArrayRecord::getKeyName() => 3, 'name' => 'Lamp']))->toBe('3');
    });

    it('answers no key for a record with no readable, filled, scalar id, or for no record at all', function (mixed $record): void {
        expect(Action::make('open')->resolveRecordKey($record))->toBeNull();
    })->with([
        'no id to read' => fn (): IdlessTicket => new IdlessTicket('T-7'),
        'a blank id' => fn (): LooseIdTicket => new LooseIdTicket(''),
        'an id that is no scalar' => fn (): LooseIdTicket => new LooseIdTicket(['T', 7]),
        'an array record whose key entry is blank' => fn (): array => [ArrayRecord::getKeyName() => ''],
        'a bare key' => fn (): string => '7',
    ]);

    it('refuses an array record without its key entry', function (): void {
        expect(fn (): ?string => Action::make('open')->resolveRecordKey(['name' => 'Lamp']))
            ->toThrow(LogicException::class, 'Record arrays must have a unique, printable [__key] entry for identification.');
    });
});
