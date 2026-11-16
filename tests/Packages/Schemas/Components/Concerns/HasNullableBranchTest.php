<?php

declare(strict_types=1);

use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Tests\Fixtures\Entity\Lighthouse;
use Sylament\Tests\Fixtures\Schemas\Record\AnnotatedCard;
use Sylament\Tests\Fixtures\Schemas\Record\CardAddress;
use Sylament\Tests\Fixtures\Schemas\Record\CardAuthor;
use Sylament\Tests\Fixtures\Schemas\Record\CatalogueCard;
use Sylament\Tests\Fixtures\Schemas\SubtreeOwningGroup;

beforeEach(function (): void {
    $this->host = $this->validatingRecordMappingHost();

    $this->card = new CatalogueCard();
    $this->card->author = new CardAuthor();
    $this->card->author->name = 'Ada';
    $this->card->author->address = new CardAddress();
});

describe('the built-in blank test', function (): void {
    it('keeps a branch any declared leaf beneath it fills, however deep or whichever component declares it', function (array $components, array $state): void {
        $schema = $this->host->mapping(...$components)->record($this->card);
        $this->host->data = $state;

        expect($schema->getData()->author)->toBe($this->card->author)
            ->address->city->toBe('Oslo');
    })->with([
        'a leaf two levels down' => fn (): array => [
            [Section::make()->statePath('author')->nullWhenBlank()->components([
                TextInput::make('name'),
                Section::make()->statePath('address')->components([TextInput::make('city')]),
            ])],
            ['author' => ['name' => null, 'address' => ['city' => 'Oslo']]],
        ],
        "a sibling's leaf beneath its path" => fn (): array => [
            [
                Section::make()->statePath('author')->nullWhenBlank()->components([TextInput::make('name')]),
                TextInput::make('author.address.city'),
            ],
            ['author' => ['name' => null, 'address' => ['city' => 'Oslo']]],
        ],
    ]);

    it('reads a checkbox resting at false as blank', function (): void {
        $schema = $this->host->mapping(Section::make()->statePath('author')->nullWhenBlank()->components([
            TextInput::make('name'),
            Checkbox::make('email'),
        ]))->record($this->card);
        $this->host->data = ['author' => ['name' => null, 'email' => false]];

        expect($schema->getData()->author)->toBeNull();
    });

    it('judges a branch that owns its record subtree by its whole slice, so a blank entry still counts', function (array $notes, ?array $expected): void {
        $card = new AnnotatedCard();
        $card->notes = ['margin' => 'Kept'];

        $schema = $this->host->mapping(SubtreeOwningGroup::make()->statePath('notes')->nullWhenBlank())->record($card);
        $this->host->data = ['notes' => $notes];

        expect($schema->getData()->notes)->toBe($expected);
    })->with([
        'a blank entry' => [['margin' => null], ['margin' => null]],
        'nothing' => [[], null],
    ]);

    it('keeps a branch holding a subtree owner whose slice has an entry, though every field the owner declares is blank', function (): void {
        $record = new stdClass();
        $record->author = new stdClass();
        $record->author->notes = null;

        $schema = $this->host->mapping(Section::make()->statePath('author')->nullWhenBlank()->components([
            SubtreeOwningGroup::make([TextInput::make('margin')])->statePath('notes'),
        ]))->record($record);
        $this->host->data = ['author' => ['notes' => ['margin' => null]]];

        expect($schema->getData()->author)->toBe($record->author)
            ->notes->toBe(['margin' => null]);
    });

    it('ignores a key of the slice no component declares', function (): void {
        $schema = $this->host->mapping(Section::make()->statePath('author')->nullWhenBlank()->components([TextInput::make('name')]))->record($this->card);
        $this->host->data = ['author' => ['name' => null, 'nickname' => 'Countess']];

        expect($schema->getData()->author)->toBeNull();
    });
});

it('nulls a branch whose path is a number', function (): void {
    $record = new stdClass();
    $record->{'0'} = new CardAuthor();

    $schema = $this->host->mapping(Section::make()->statePath('0')->nullWhenBlank()->components([TextInput::make('name')]))->record($record);
    $this->host->data = ['0' => ['name' => null]];

    expect($schema->getData()->{'0'})->toBeNull();
});

it("hands a closure the branch's slice and nulls the branch on its answer", function (): void {
    $seen = null;
    $schema = $this->host->mapping(Section::make()->statePath('author')->nullWhenBlank(function (array $state) use (&$seen): bool {
        $seen = $state;

        return true;
    })->components([TextInput::make('name')]))->record($this->card);
    $this->host->data = ['author' => ['name' => 'Grace']];

    expect($schema->getData()->author)->toBeNull()
        ->and($seen)->toBe(['name' => 'Grace']);
});

it('builds a blank branch that does not declare it rather than nulling it', function (): void {
    $card = new CatalogueCard();
    $schema = $this->host->mapping(Section::make()->statePath('author')->components([TextInput::make('name')]))->record($card);
    $this->host->data = ['author' => ['name' => null]];

    expect($schema->getData()->author)->toBeInstanceOf(CardAuthor::class)
        ->name->toBeNull();
});

it('still fails a leaf declared required inside a blank branch', function (): void {
    $schema = $this->host->mapping(Section::make()->statePath('author')->nullWhenBlank()->components([TextInput::make('name')->required()]))->record($this->card);
    $this->host->data = ['author' => ['name' => null]];

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class);
});

describe('a branch the submission does not carry', function (): void {
    it('survives when its only leaf is hidden', function (): void {
        $schema = $this->host->mapping(Section::make()->statePath('author')->nullWhenBlank()->components([TextInput::make('name')->hidden()]))->record($this->card);
        $this->host->data = ['author' => ['name' => null]];

        expect($schema->getData()->author)->name->toBe('Ada');
    });

    it('is no branch at all on a field, so a field kept out of the submission keeps its value', function (): void {
        $this->card->title = 'Dune';
        $schema = $this->host->mapping(TextInput::make('title')->nullWhenBlank()->dehydrated(false))->record($this->card);
        $this->host->data = ['title' => null];

        expect($schema->getData()->title)->toBe('Dune');
    });

    it('is not refused over a property that cannot be null', function (): void {
        $address = $this->card->author->address;
        $schema = $this->host->mapping(Section::make()->statePath('author.address')->nullWhenBlank()->hidden()->components([TextInput::make('city')]))->record($this->card);
        $this->host->data = ['author' => ['address' => ['city' => null]]];

        expect($schema->getData()->author->address)->toBe($address);
    });
});

describe('the refusals', function (): void {
    it('refuses a branch over a property the record cannot null, blank or filled', function (?string $city): void {
        $schema = $this->host->mapping(Section::make()->statePath('author.address')->nullWhenBlank()->components([TextInput::make('city')]))->record($this->card);
        $this->host->data = ['author' => ['address' => ['city' => $city]]];

        expect(fn (): object => $schema->getData())->toThrow(
            LogicException::class,
            'Cannot map state path "author.address" onto ' . CatalogueCard::class . ': the record declares that property non-nullable, so the nullWhenBlank() on the component that owns it can never apply. Make the property nullable, or drop nullWhenBlank().',
        );
    })->with([
        'blank' => [null],
        'filled' => ['Oslo'],
    ]);

    it('refuses a branch with no state path', function (): void {
        $schema = $this->host->mapping(Section::make()->nullWhenBlank()->components([TextInput::make('author.name')]))->record($this->card);

        expect(fn (): object => $schema->getData())->toThrow(
            LogicException::class,
            Section::class . ' declares nullWhenBlank() but carries no state path, so it names no property to null. Give it the statePath() of the branch it wraps, or drop nullWhenBlank().',
        );
    });

    it('refuses a branch that also declares a save hook', function (): void {
        $lighthouse = new Lighthouse();
        $schema = $this->host->mapping(Group::make([TextInput::make('keeper')])->relationship('log')->nullWhenBlank())->record($lighthouse);
        $this->host->data = ['log' => ['keeper' => 'Ada']];

        expect(fn (): object => $schema->getData())->toThrow(
            LogicException::class,
            Group::class . ' declares nullWhenBlank() alongside a relationship save hook — the hook owns what an empty submission means, so the two cannot compose. Drop nullWhenBlank() or the relationship.',
        );
    });
});
