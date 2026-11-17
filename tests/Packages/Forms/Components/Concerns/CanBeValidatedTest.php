<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Hidden;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Components\Toggle;
use Sylament\Forms\Validation\ActiveUrl;
use Sylament\Forms\Validation\Alpha;
use Sylament\Forms\Validation\MatchesField;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Forms\Validation\UniqueValue;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\Berth;
use Sylament\Tests\Fixtures\Entity\BerthMooring;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Entity\ColumnKindSample;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\Trellis;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Uuid;
use Twig\Markup;

beforeEach(function (): void {
    $this->host = $this->validatingRecordMappingHost();
});

describe('required()', function (): void {
    it('refuses a blank value of a field it requires, under a closure condition too, and leaves one the closure releases optional', function (): void {
        $schema = $this->host->mapping(
            TextInput::make('code')->required(),
            TextInput::make('city')->required(static fn (): bool => true),
            TextInput::make('note')->required(static fn (): bool => false),
        );

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.code' => ['This value should not be blank.'],
                'data.city' => ['This value should not be blank.'],
            ]);
        });
    });

    it("carries Laravel's blank set: whitespace is blank, while false and zero are present", function (): void {
        $schema = $this->host->mapping(Hidden::make('flagged')->default(false), TextInput::make('body'), TextInput::make('count')->required())->record(ColumnKindSample::class);
        $this->host->data = ['flagged' => false, 'body' => " \t", 'count' => '0'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.body' => ['This value should not be blank.']]);
        });

        $this->host->data = ['flagged' => null, 'body' => 'Text', 'count' => '0'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.flagged' => ['This value should not be blank.']]);
        });
    });

    it('beats the record either way: required(false) and nullable() leave a field over a non-nullable property optional', function (Closure $field, bool $requires): void {
        $schema = $this->host->mapping($field())->record(Product::class);
        $this->host->data = ['name' => ''];
        $this->host->validateSchema($schema, shouldThrow: false);

        expect($this->host->getErrorsForSchemaStatePath('data.name'))->toBe($requires ? ['This value should not be blank.'] : []);
    })->with([
        'undeclared' => [fn (): TextInput => TextInput::make('name'), true],
        'required(false)' => [fn (): TextInput => TextInput::make('name')->required(false), false],
        'nullable()' => [fn (): TextInput => TextInput::make('name')->nullable(), false],
    ]);

    it('undeclares with required(null), so a field a closure declared optional takes its requirement from the record again', function (): void {
        $schema = $this->host->mapping(TextInput::make('name')->required(static fn (): bool => false)->required(null))->record(Product::class);

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.name' => ['This value should not be blank.']]);
        })
            ->and($this->renderSchemaComponent(Section::make()->record(Product::class)->components([TextInput::make('name')->required(static fn (): bool => false)->required(null)])))
            ->toContainElement('label .sy-fo-field-label-required-mark', '*')
            ->toContainElement('input[name="name"][required]');
    });
});

describe('isRequiredByRecord()', function (): void {
    it('requires a field whose write-side target is non-nullable, and no field over a nullable, undeclared or collection target', function (Closure $component, ?string $recordClass, string $path, bool $requires): void {
        $schema = $this->host->mapping($component())->record($recordClass);
        $this->host->validateSchema($schema, shouldThrow: false);

        expect($this->host->getErrorsForSchemaStatePath($path))->toBe($requires ? ['This value should not be blank.'] : []);
    })->with([
        'a non-nullable property' => [fn (): TextInput => TextInput::make('name'), Product::class, 'data.name', true],
        'a non-nullable property of the class a section frames, read at the path inside that frame' => [fn (): Section => Section::make()->statePath('mooring')->record(BerthMooring::class)->components([TextInput::make('label')]), Product::class, 'data.mooring.label', true],
        'a non-nullable property, on a field built with new outside the container' => [fn (): TextInput => new TextInput()->name('name')->statePath('name'), Product::class, 'data.name', true],
        'a non-nullable property the field leaves undehydrated' => [fn (): TextInput => TextInput::make('name')->dehydrated(false), Product::class, 'data.name', true],
        'a nullable property' => [fn (): TextInput => TextInput::make('title'), CappedHeadline::class, 'data.title', false],
        'a path the record does not declare' => [fn (): TextInput => TextInput::make('nickname'), Product::class, 'data.nickname', false],
        'a collection' => [fn (): TextInput => TextInput::make('panels'), Trellis::class, 'data.panels', false],
        'no record' => [fn (): TextInput => TextInput::make('name'), null, 'data.name', false],
    ]);

    it('marks a field its record requires with the asterisk and the native required attribute, and one over a nullable target with neither', function (): void {
        expect($this->renderSchemaComponent(Section::make()->record(Product::class)->components([TextInput::make('name')])))
            ->toContainElement('label .sy-fo-field-label-required-mark', '*')
            ->toContainElement('input[name="name"][required]')
            ->and($this->renderSchemaComponent(Section::make()->record(CappedHeadline::class)->components([TextInput::make('title')])))
            ->toContainElement('label', 'Title')
            ->toContainElement('input[name="title"]:not([required])')
            ->not->toContainElement('.sy-fo-field-label-required-mark');
    });

    it('gives up under a nullable branch while it is blank and derives again once it holds a value, and ignores a branch with no path of its own', function (): void {
        $schema = $this->host->mapping(
            Section::make()->statePath('mooring')->nullWhenBlank()->components([TextInput::make('pier'), TextInput::make('label')]),
            Section::make()->nullWhenBlank()->components([TextInput::make('name')]),
        )->record(Berth::class);
        $this->host->data = ['mooring' => ['pier' => '', 'label' => ''], 'name' => ''];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.name' => ['This value should not be blank.']]);
        });

        $this->host->data = ['mooring' => ['pier' => 'P1', 'label' => ''], 'name' => ''];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.mooring.label' => ['This value should not be blank.'],
                'data.name' => ['This value should not be blank.'],
            ]);
        });
    });

    it('derives against the child a relationship repeater item maps, not against its owner', function (): void {
        $schema = $this->host->mapping(Repeater::make('jars')->relationship()->components([TextInput::make('label'), TextInput::make('sealedOn')]))->record(new Larder('Pantry'));
        $this->host->data = ['jars' => ['added' => ['label' => '', 'sealedOn' => '']]];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.jars.added.label' => ['This value should not be blank.']]);
        });
    });

    it('requires a boolean select over a non-nullable bool, whose placeholder would write null', function (): void {
        $schema = $this->host->mapping(Select::make('flagged')->boolean())->record(ColumnKindSample::class);
        $this->host->data = ['flagged' => ''];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.flagged' => ['This value should not be blank.']]);
        });
    });
});

describe('nullable()', function (): void {
    it('passes null and a cleared value on an optional field a length bound would refuse, and a later required() overrides it', function (): void {
        $optional = $this->host->mapping(TextInput::make('name')->minLength(3)->nullable())->record(Product::class);

        foreach ([null, ''] as $blank) {
            $this->host->data = ['name' => $blank];
            $this->host->validateSchema($optional, shouldThrow: false);

            expect($this->host->getErrorsForSchemaStatePath('data.name'))->toBe([]);
        }

        $this->host->data = ['name' => 'ab'];
        $this->host->validateSchema($optional, shouldThrow: false);

        expect($this->host->getErrorsForSchemaStatePath('data.name'))->toBe(['This value is too short. It should have 3 characters or more.']);

        $overridden = $this->host->mapping(TextInput::make('name')->nullable()->required());
        $this->host->data = ['name' => ''];

        expect(fn () => $this->host->validateSchema($overridden))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.name' => ['This value should not be blank.']]);
        });
    });
});

describe('constraint()', function (): void {
    it('builds the constraints it was handed, a closure returning one or a list, in order, evaluating each condition first and skipping a closure that returns nothing', function (): void {
        $field = TextInput::make('code')
            ->constraint(new Regex('/^a/'))
            ->constraint(static fn (): array => [new Length(max: 3), new Type('string')])
            ->constraint(new Email(), false)
            ->constraint(static fn (): Constraint => throw new LogicException('A constraint under a false condition was built.'), false)
            ->constraint(static fn (): ?Constraint => null)
            ->constraints([new NotEqualTo('x'), static fn (): Json => new Json()], static fn (): bool => true)
            ->constraints(static fn (): array => [new Uuid()], false);

        expect(array_map(get_class(...), Schema::make()->statePath('data')->components([$field])->getValidationConstraints()['data.code']))
            ->toBe([Regex::class, Length::class, Type::class, NotEqualTo::class, Json::class]);
    });

    it('lets a blank value past every constraint that is not about presence, an escape-hatch Regex included, while required() still refuses it', function (): void {
        $schema = $this->host->mapping(
            TextInput::make('short')->minLength(3),
            TextInput::make('email')->email(),
            TextInput::make('amount')->numeric(),
            TextInput::make('slug')->regex('/^x/'),
            TextInput::make('site')->url(),
            TextInput::make('code')->constraint(new Regex('/^x/')),
            TextInput::make('name')->required(),
        );
        $this->host->data = ['short' => '', 'email' => '', 'amount' => '', 'slug' => '', 'site' => '', 'code' => '', 'name' => ''];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.name' => ['This value should not be blank.']]);
        });

        $this->host->data['code'] = 'y';
        $this->host->data['name'] = 'Ada';

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.code' => ['This value is not valid.']]);
        });
    });
});

describe('getValidationConstraints()', function (): void {
    it("composes in order: the required constraint, the length bounds, the in() list, the regex, the registered constraints, then the field's own arms", function (): void {
        $field = TextInput::make('code')->email()->constraint(new Json())->constraint(new Uuid(), false)->regex('/^a/')->in(['a'])->minLength(1)->required();

        expect(array_map(get_class(...), Schema::make()->statePath('data')->components([$field])->getValidationConstraints()['data.code']))
            ->toBe([RequiredValue::class, Length::class, Choice::class, Regex::class, Json::class, Email::class]);
    });
});

describe('validationMessages()', function (): void {
    it("rewrites the message of each constraint a key names, a string onto its message and a map onto the options it names, an escape hatch's and a subclass arm's alike", function (): void {
        $schema = $this->host->mapping(
            TextInput::make('name')->required()->validationMessages([RequiredValue::class => 'Name it.', Choice::class => 'Never built.']),
            TextInput::make('code')->minLength(5)->validationMessages([Length::class => ['minMessage' => 'Too short.']]),
            TextInput::make('slug')->constraint(new Regex('/^x/'))->validationMessages([Regex::class => 'Start with x.']),
            TextInput::make('email')->email()->validationMessages([Email::class => 'Not an email.']),
            TextInput::make('city')->required()->validationMessages([RequiredValue::class => static fn (): string => '']),
        );
        $this->host->data = ['name' => '', 'code' => 'ab', 'slug' => 'y', 'email' => 'z', 'city' => ''];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.name' => ['Name it.'],
                'data.code' => ['Too short.'],
                'data.slug' => ['Start with x.'],
                'data.email' => ['Not an email.'],
                'data.city' => ['This value should not be blank.'],
            ]);
        });
    });

    it('leaves a constraint the field was handed as it was', function (): void {
        $regex = new Regex('/^x/');
        $schema = $this->host->mapping(TextInput::make('slug')->constraint($regex)->validationMessages([Regex::class => 'Start with x.']));
        $this->host->data = ['slug' => 'y'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.slug' => ['Start with x.']]);
        })
            ->and($regex->message)->toBe('This value is not valid.');
    });

    it('refuses a message option the constraint lacks, naming the field, the constraint and the options it has', function (): void {
        $schema = $this->host->mapping(TextInput::make('code')->minLength(2)->validationMessages([Length::class => ['message' => 'Too short.']]));

        expect(fn () => $schema->getValidationConstraints())
            ->toThrow(LogicException::class, 'Sylament cannot apply a validation message to [data.code] because Symfony\Component\Validator\Constraints\Length has no [message] message option. Use one of: [maxMessage], [minMessage], [exactMessage], [charsetMessage].');
    });

    it('reaches the members of a nested All, while a string message keyed on the All itself is refused', function (): void {
        $schema = $this->host->mapping(CheckboxList::make('codes')->options(['ab' => 'AB', 'abc' => 'ABC'])->nestedRecursiveConstraint(new Length(max: 2))->validationMessages([Length::class => ['maxMessage' => 'Short codes only.']]));
        $this->host->data = ['codes' => ['abc']];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.codes' => ['Short codes only.']]);
        });

        $refused = $this->host->mapping(CheckboxList::make('codes')->options(['ab' => 'AB'])->nestedRecursiveConstraint(new Length(max: 2))->validationMessages([All::class => 'Short codes only.']));

        expect(fn () => $refused->getValidationConstraints())->toThrow(LogicException::class, 'because Symfony\Component\Validator\Constraints\All has no [message] message option.');
    });
});

describe('format sugar', function (): void {
    it('passes what each format arm accepts and refuses what it does not', function (Closure $field, mixed $accepted, mixed $refused, string $message): void {
        $schema = $this->host->mapping($field());

        $this->host->data = ['value' => $accepted];
        $this->host->validateSchema($schema, shouldThrow: false);

        expect($this->host->getErrorsForSchemaStatePath('data.value'))->toBe([]);

        $this->host->data = ['value' => $refused];
        $this->host->validateSchema($schema, shouldThrow: false);

        expect($this->host->getErrorsForSchemaStatePath('data.value'))->toBe([$message]);
    })->with([
        'string()' => [fn (): TextInput => TextInput::make('value')->string(), 'x', 12, 'This value should be of type string.'],
        'json()' => [fn (): TextInput => TextInput::make('value')->json(), '{"a":1}', '{a', 'This value should be valid JSON.'],
        'macAddress()' => [fn (): TextInput => TextInput::make('value')->macAddress(), '00:1A:2B:3C:4D:5E', '00:1A:2B', 'This value is not a valid MAC address.'],
        'ip(), an IPv6 address' => [fn (): TextInput => TextInput::make('value')->ip(), '::1', '300.0.0.1', 'This is not a valid IP address.'],
        'ip(), an IPv4 address' => [fn (): TextInput => TextInput::make('value')->ip(), '127.0.0.1', 'localhost', 'This is not a valid IP address.'],
        'ipv4()' => [fn (): TextInput => TextInput::make('value')->ipv4(), '127.0.0.1', '::1', 'This is not a valid IP address.'],
        'ipv6()' => [fn (): TextInput => TextInput::make('value')->ipv6(), '::1', '127.0.0.1', 'This is not a valid IP address.'],
        'uuid(), the nil UUID' => [fn (): TextInput => TextInput::make('value')->uuid(), '00000000-0000-0000-0000-000000000000', 'not-a-uuid', 'This is not a valid UUID.'],
        'ulid()' => [fn (): TextInput => TextInput::make('value')->ulid(), '01ARZ3NDEKTSV4RRFFQ69G5FAV', 'not-a-ulid', 'This is not a valid ULID.'],
        'hexColor(), refusing a named colour' => [fn (): TextInput => TextInput::make('value')->hexColor(), '#a1b2c3', 'red', 'This value is not a valid CSS color.'],
        'hexColor(), refusing an rgb() colour' => [fn (): TextInput => TextInput::make('value')->hexColor(), '#abc', 'rgb(0, 0, 0)', 'This value is not a valid CSS color.'],
        'alpha()' => [fn (): TextInput => TextInput::make('value')->alpha(), 'Ada', 'Ada1', 'This value may only contain letters.'],
        'alphaDash()' => [fn (): TextInput => TextInput::make('value')->alphaDash(), 'ada-1_b', 'ada 1', 'This value may only contain letters, numbers, dashes and underscores.'],
        'alphaNum()' => [fn (): TextInput => TextInput::make('value')->alphaNum(), 'Ada1', 'Ada-1', 'This value may only contain letters and numbers.'],
        'ascii()' => [fn (): TextInput => TextInput::make('value')->ascii(), 'Ada', 'Žofia', 'This value may only contain ASCII characters.'],
    ]);

    it('checks activeUrl() with the constraint that resolves the host, which the suite never runs against the network', function (): void {
        expect(array_map(get_class(...), Schema::make()->statePath('data')->components([TextInput::make('site')->activeUrl()])->getValidationConstraints()['data.site']))
            ->toBe([ActiveUrl::class]);
    });

    it("keys a first-party arm's message on its own class, leaving another regex arm's message alone", function (): void {
        $schema = $this->host->mapping(TextInput::make('code')->regex('/^\d+$/')->alpha()->validationMessages([Alpha::class => 'Letters only.']));
        $this->host->data = ['code' => '1!'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.code' => ['This value is not valid.', 'Letters only.']]);
        });
    });
});

describe('regex()', function (): void {
    it('keeps one pattern, a later call replacing or clearing the earlier, and refuses a value it does not match', function (): void {
        $schema = $this->host->mapping(TextInput::make('code')->regex('/^a/')->regex('/^b/'), TextInput::make('slug')->regex('/^a/')->regex(null));
        $this->host->data = ['code' => 'a1', 'slug' => 'z'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.code' => ['This value is not valid.']]);
        });

        $this->host->data['code'] = 'b1';

        expect($this->host->validateSchema($schema))->toBeNull();
    });

    it('refuses a value notRegex() matches, with a message of its own', function (): void {
        $schema = $this->host->mapping(TextInput::make('code')->notRegex('/^\d/'), TextInput::make('slug')->notRegex('/^\d/'));
        $this->host->data = ['code' => '1a', 'slug' => 'a1'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.code' => ['This value has a format that is not allowed.']]);
        });
    });
});

describe('in()', function (): void {
    it('accepts a listed value in either spelling, an enum by its value and a comma string trimmed, and refuses one it does not list', function (Closure $field, mixed $value, bool $passes): void {
        $schema = $this->host->mapping($field());
        $this->host->data = ['value' => $value];
        $this->host->validateSchema($schema, shouldThrow: false);

        expect($this->host->getErrorsForSchemaStatePath('data.value'))->toBe($passes ? [] : ['The value you selected is not a valid choice.']);
    })->with([
        'a listed integer the wire sends as text' => [fn (): TextInput => TextInput::make('value')->in([1, StringBackedLetter::B]), '1', true],
        'a listed enum by its value' => [fn (): TextInput => TextInput::make('value')->in([1, StringBackedLetter::B]), 'b', true],
        'a value a comma string lists' => [fn (): TextInput => TextInput::make('value')->in('a, b'), 'b', true],
        'a value the list leaves out' => [fn (): TextInput => TextInput::make('value')->in([1, StringBackedLetter::B]), '2', false],
        'a list switched off by its condition' => [fn (): TextInput => TextInput::make('value')->in([1], static fn (): bool => false), '2', true],
    ]);

    it('keeps a null member and the text form of every scalar in the list it builds, once each', function (): void {
        expect(TextInput::make('value')->in([1, null, '1', 2])->getInValidationConstraint()?->choices)->toBe([1, '1', null, 2, '2']);
    });

    it('refuses a value notIn() lists, a comma string split and trimmed', function (): void {
        $schema = $this->host->mapping(TextInput::make('code')->notIn('a, b'), TextInput::make('slug')->notIn('a, b'));
        $this->host->data = ['code' => 'b', 'slug' => 'c'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.code' => ['This value is not allowed.']]);
        });
    });

    it("replaces an option field's own membership check, even on a field with no options, and checks each element of a multiple one", function (): void {
        $schema = $this->host->mapping(
            Select::make('listed')->options(['a' => 'A'])->in(['z']),
            Select::make('offered')->options(['a' => 'A'])->in(['z']),
            Select::make('optionless')->in(['z']),
            Select::make('many')->multiple()->options(['a' => 'A', 'b' => 'B'])->in(['a']),
        );
        $this->host->data = ['listed' => 'z', 'offered' => 'a', 'optionless' => 'z', 'many' => ['a', 'b']];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.offered' => ['The value you selected is not a valid choice.'],
                'data.many' => ['One or more of the given values is invalid.'],
            ]);
        });
    });
});

describe('startsWith()', function (): void {
    it('passes a value with any listed prefix or suffix, or with none under the negations, and splits a comma string without trimming it', function (): void {
        $schema = $this->host->mapping(
            TextInput::make('code')->startsWith(['ab', 'x']),
            TextInput::make('site')->endsWith('.com'),
            TextInput::make('slug')->doesntStartWith('ab'),
            TextInput::make('host')->doesntEndWith('.org'),
            TextInput::make('tag')->startsWith('ab, cd'),
        );
        $this->host->data = ['code' => 'abc', 'site' => 'x.org', 'slug' => 'abx', 'host' => 'x.com', 'tag' => 'cd1'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.site' => ['This value must end with one of: .com.'],
                'data.slug' => ['This value must not start with any of: ab.'],
                'data.tag' => ['This value must start with one of: ab,  cd.'],
            ]);
        });

        $this->host->data = ['code' => 'x1', 'site' => 'x.com', 'slug' => 'xab', 'host' => 'x.net', 'tag' => ' cd1'];

        expect($this->host->validateSchema($schema))->toBeNull();
    });
});

describe('filled()', function (): void {
    it('refuses a blank value without marking the field required', function (): void {
        $schema = $this->host->mapping(TextInput::make('code')->filled());

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.code' => ['This value should not be blank.']]);
        })
            ->and($this->renderSchemaComponent(TextInput::make('code')->filled()))
            ->toContainElement('label', 'Code')
            ->toContainElement('input[name="code"]:not([required])')
            ->not->toContainElement('.sy-fo-field-label-required-mark');
    });
});

describe('multipleOf()', function (): void {
    it('passes a multiple of its divisor within its precision and refuses anything else, a zero divisor every value and a non-numeric value as the wrong type', function (int|float|Closure $divisor, string $value, ?string $message): void {
        $schema = $this->host->mapping(TextInput::make('amount')->multipleOf($divisor));
        $this->host->data = ['amount' => $value];
        $this->host->validateSchema($schema, shouldThrow: false);

        expect($this->host->getErrorsForSchemaStatePath('data.amount'))->toBe($message === null ? [] : [$message]);
    })->with([
        'a multiple' => [3, '9', null],
        'a value that is no multiple' => [3, '10', 'This value should be a multiple of 3.'],
        'a decimal multiple a float remainder would miss' => [0.1, '0.3', null],
        'a zero divisor' => [0, '0', 'This value should be a multiple of 0.'],
        'a non-numeric value' => [3, 'x', 'This value should be of type numeric.'],
        'a divisor its closure leaves out' => [fn (): Closure => static fn (): ?int => null, '10', null],
    ]);
});

describe('value comparison', function (): void {
    it("orders a value against its sibling's live value, numeric text as numbers and other text by PHP's comparison, and drops the comparison for a blank or absent sibling", function (Closure $field, mixed $own, mixed $sibling, ?string $message): void {
        $schema = $this->host->mapping($field(), TextInput::make('bound'));
        $this->host->data = ['amount' => $own, 'bound' => $sibling];
        $this->host->validateSchema($schema, shouldThrow: false);

        expect($this->host->getErrorsForSchemaStatePath('data.amount'))->toBe($message === null ? [] : [$message]);
    })->with([
        'gt(), numeric text above' => [fn (): TextInput => TextInput::make('amount')->gt('bound'), '10', '9', null],
        'gt(), numeric text below' => [fn (): TextInput => TextInput::make('amount')->gt('bound'), '5', '9', 'This value should be greater than "9".'],
        'gte(), an equal value' => [fn (): TextInput => TextInput::make('amount')->gte('bound'), '9', '9', null],
        'lt(), a value above' => [fn (): TextInput => TextInput::make('amount')->lt('bound'), '10', '9', 'This value should be less than "9".'],
        'lte(), a value above' => [fn (): TextInput => TextInput::make('amount')->lte('bound'), '10', '9', 'This value should be less than or equal to "9".'],
        'gt(), text after its sibling' => [fn (): TextInput => TextInput::make('amount')->gt('bound'), 'b', 'a', null],
        'gt(), text before its sibling' => [fn (): TextInput => TextInput::make('amount')->gt('bound'), 'a', 'b', 'This value should be greater than "b".'],
        'gt(), a blank sibling' => [fn (): TextInput => TextInput::make('amount')->gt('bound'), '5', '', null],
        'gt(), a null sibling' => [fn (): TextInput => TextInput::make('amount')->gt('bound'), '5', null, null],
        'gt(), a sibling no component holds' => [fn (): TextInput => TextInput::make('amount')->gt('missing'), '5', '9', null],
    ]);

    it('rewrites the messages of gte() and minValue() together, since both build the one class a message is keyed on', function (): void {
        $schema = $this->host->mapping(TextInput::make('amount')->numeric()->minValue(5)->gte('bound')->validationMessages([GreaterThanOrEqual::class => 'Too small.']), TextInput::make('bound'));
        $this->host->data = ['amount' => '3', 'bound' => '4'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.amount' => ['Too small.', 'Too small.']]);
        });
    });

    it("requires same() to match its partner and different() to differ from it, naming the partner by its label and never by its value, under a message key of their own", function (): void {
        $schema = $this->host->mapping(
            TextInput::make('password')->same('passwordConfirmation'),
            TextInput::make('passwordConfirmation')->label('Repeat password'),
            TextInput::make('echo')->same('password')->validationMessages([MatchesField::class => 'Type it again.']),
            TextInput::make('hint')->different('password'),
        );
        $this->host->data = ['password' => 's3cret', 'passwordConfirmation' => '', 'echo' => 'secret', 'hint' => 's3cret'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.password' => ['This value must match Repeat password.'],
                'data.echo' => ['Type it again.'],
                'data.hint' => ['This value must differ from Password.'],
            ]);
        });

        $this->host->data = ['password' => 's3cret', 'passwordConfirmation' => 's3cret', 'echo' => 's3cret', 'hint' => 'a hint'];

        expect($this->host->validateSchema($schema))->toBeNull();
    });

    it('confirms against the component at its own path suffixed _confirmation, by its plain-text label, or by that path humanised when no component holds it', function (): void {
        $labelled = $this->host->mapping(TextInput::make('pin')->confirmed(), TextInput::make('pin_confirmation')->label(new Markup('<b>Pin again</b>', 'UTF-8')));
        $this->host->data = ['pin' => '1234', 'pin_confirmation' => '4321'];

        expect(fn () => $this->host->validateSchema($labelled))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.pin' => ['This value must match Pin again.']]);
        });

        $unlabelled = $this->host->mapping(TextInput::make('pin')->confirmed());
        $this->host->data = ['pin' => '1234'];

        expect(fn () => $this->host->validateSchema($unlabelled))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.pin' => ['This value must match Pin confirmation.']]);
        });
    });
});

describe('cross-field arms', function (): void {
    it("reads its sibling's raw value, so a trimmed own value meets its partner untrimmed", function (): void {
        $schema = $this->host->mapping(TextInput::make('password')->trim()->same('again'), TextInput::make('again')->trim());
        $this->host->data = ['password' => ' s3cret ', 'again' => ' s3cret '];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.password' => ['This value must match Again.']]);
        });
    });

    it('reads its sibling again on every validation, tracking a partner still being typed into', function (): void {
        $schema = $this->host->mapping(TextInput::make('password')->same('again'), TextInput::make('amount')->gt('bound'), TextInput::make('again'), TextInput::make('bound'));
        $this->host->data = ['password' => 's3cret', 'again' => 's3', 'amount' => '5', 'bound' => '9'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect(array_keys($exception->getErrors()))->toBe(['data.password', 'data.amount']);
        });

        $this->host->data['again'] = 's3cret';
        $this->host->data['bound'] = '4';

        expect($this->host->validateSchema($schema))->toBeNull();
    });

    it('builds no cross-field constraint for a field no live host holds', function (): void {
        $components = static fn (): array => [
            TextInput::make('amount')->gt('bound'),
            TextInput::make('password')->same('bound'),
            TextInput::make('note')->requiredIf('bound', '9'),
            TextInput::make('email')->prohibits('bound'),
            TextInput::make('bound'),
        ];
        $this->host->data = ['amount' => '5', 'password' => 'x', 'note' => '', 'email' => 'x', 'bound' => '9'];

        expect(Schema::make()->statePath('data')->components($components())->getValidationConstraints())->toBe([])
            ->and(array_keys($this->host->mapping(...$components())->getValidationConstraints()))->toBe(['data.amount', 'data.password', 'data.note', 'data.email']);
    });
});

describe('conditional requirement', function (): void {
    it('requires the field while its condition over the sibling state holds, and leaves it optional otherwise', function (Closure $field, array $holding, array $notHolding): void {
        $schema = $this->host->mapping($field(), TextInput::make('plan'), TextInput::make('phone'), TextInput::make('fax'), Toggle::make('terms'));

        foreach ([[$holding, ['This value should not be blank.']], [$notHolding, []]] as [$siblings, $errors]) {
            $this->host->data = ['note' => '', ...$siblings];
            $this->host->validateSchema($schema, shouldThrow: false);

            expect($this->host->getErrorsForSchemaStatePath('data.note'))->toBe($errors);
        }
    })->with([
        'requiredIf()' => [fn (): TextInput => TextInput::make('note')->requiredIf('plan', 'pro'), ['plan' => 'pro'], ['plan' => 'free']],
        'requiredUnless()' => [fn (): TextInput => TextInput::make('note')->requiredUnless('plan', 'free'), ['plan' => 'pro'], ['plan' => 'free']],
        'requiredIfAccepted()' => [fn (): TextInput => TextInput::make('note')->requiredIfAccepted('terms'), ['terms' => true], ['terms' => false]],
        'requiredIfDeclined()' => [fn (): TextInput => TextInput::make('note')->requiredIfDeclined('terms'), ['terms' => false], ['terms' => true]],
        'requiredWith()' => [fn (): TextInput => TextInput::make('note')->requiredWith('phone, fax'), ['phone' => '', 'fax' => '1'], ['phone' => '', 'fax' => '']],
        'requiredWithAll()' => [fn (): TextInput => TextInput::make('note')->requiredWithAll(['phone', 'fax']), ['phone' => '1', 'fax' => '2'], ['phone' => '1', 'fax' => '']],
        'requiredWithout()' => [fn (): TextInput => TextInput::make('note')->requiredWithout(['phone', 'fax']), ['phone' => '1', 'fax' => ''], ['phone' => '1', 'fax' => '2']],
        'requiredWithoutAll()' => [fn (): TextInput => TextInput::make('note')->requiredWithoutAll(['phone', 'fax']), ['phone' => '', 'fax' => ''], ['phone' => '', 'fax' => '2']],
    ]);

    it("matches the declared values with the sibling's own strictness", function (mixed $sibling, mixed $declared, bool $requires): void {
        $schema = $this->host->mapping(TextInput::make('note')->requiredIf('flag', $declared), TextInput::make('flag'));
        $this->host->data = ['note' => '', 'flag' => $sibling];
        $this->host->validateSchema($schema, shouldThrow: false);

        expect($this->host->getErrorsForSchemaStatePath('data.note'))->toBe($requires ? ['This value should not be blank.'] : []);
    })->with([
        'a text sibling against an integer' => ['1', 1, true],
        'a text sibling against true' => ['1', true, false],
        'a text sibling spelling true' => ['true', true, true],
        'a boolean sibling against true' => [true, true, true],
        'a null sibling against null' => [null, null, true],
    ]);

    it('shows no asterisk and no native required attribute, adds nothing to a field already required, and leaves a requirement the record derives in place', function (): void {
        $schema = $this->host->mapping(
            TextInput::make('note')->requiredIf('plan', 'pro'),
            TextInput::make('code')->required()->requiredIf('plan', 'pro'),
            TextInput::make('name')->requiredUnless('plan', 'pro'),
            TextInput::make('plan'),
        )->record(Product::class);
        $this->host->data = ['note' => '', 'code' => '', 'name' => '', 'plan' => 'pro'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.note' => ['This value should not be blank.'],
                'data.code' => ['This value should not be blank.'],
                'data.name' => ['This value should not be blank.'],
            ]);
        })
            ->and($this->renderSchemaComponent(TextInput::make('note')->requiredIf('plan', 'pro'), ['plan' => 'pro']))
            ->toContainElement('label', 'Note')
            ->toContainElement('input[name="note"]:not([required])')
            ->not->toContainElement('.sy-fo-field-label-required-mark');
    });

    it("requires with the field's own required constraint, so a Toggle left off passes and a Checkbox left unticked does not, under required(), a condition and filled() alike", function (): void {
        $schema = $this->host->mapping(
            Toggle::make('muted')->required(),
            Toggle::make('alerts')->requiredIf('plan', 'pro'),
            Toggle::make('digest')->filled(),
            Checkbox::make('agree')->requiredIf('plan', 'pro'),
            TextInput::make('plan'),
        );
        $this->host->data = ['muted' => false, 'alerts' => false, 'digest' => false, 'agree' => false, 'plan' => 'pro'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.agree' => ['This value should not be blank.']]);
        });

        $this->host->data = ['muted' => null, 'alerts' => null, 'digest' => null, 'agree' => true, 'plan' => 'pro'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.muted' => ['This value should not be null.'],
                'data.alerts' => ['This value should not be null.'],
                'data.digest' => ['This value should not be null.'],
            ]);
        });
    });

    it('refuses a value prohibited(), prohibitedIf() and prohibitedUnless() rule out, and passes a blank one', function (): void {
        $schema = $this->host->mapping(
            TextInput::make('code')->prohibited(),
            TextInput::make('coupon')->prohibitedIf('plan', 'pro'),
            TextInput::make('voucher')->prohibitedUnless('plan', 'free'),
            TextInput::make('plan'),
        );
        $this->host->data = ['code' => 'x', 'coupon' => 'x', 'voucher' => 'x', 'plan' => 'pro'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.code' => ['This value is prohibited.'],
                'data.coupon' => ['This value is prohibited.'],
                'data.voucher' => ['This value is prohibited.'],
            ]);
        });

        $this->host->data = ['code' => '', 'coupon' => 'x', 'voucher' => 'x', 'plan' => 'free'];

        expect($this->host->validateSchema($schema))->toBeNull();
    });

    it('puts a prohibits() violation on its own field, naming each filled sibling it rules out', function (): void {
        $schema = $this->host->mapping(TextInput::make('email')->prohibits(['phone', 'fax']), TextInput::make('phone'), TextInput::make('fax')->label('Fax line'));
        $this->host->data = ['email' => 'ada@example.com', 'phone' => '1', 'fax' => '2'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.email' => ['This value cannot be combined with Phone, Fax line.']]);
        });

        $this->host->data = ['email' => 'ada@example.com', 'phone' => '', 'fax' => '2'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.email' => ['This value cannot be combined with Fax line.']]);
        });

        $this->host->data = ['email' => 'ada@example.com', 'phone' => '', 'fax' => ''];

        expect($this->host->validateSchema($schema))->toBeNull();
    });
});

describe('distinct()', function (): void {
    it("refuses an item's value another item holds, on that item's own path, loosely for a scalar and on any overlap for a list", function (): void {
        $schema = $this->host->mapping(Repeater::make('items')->components([
            TextInput::make('code')->distinct(),
            CheckboxList::make('tags')->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])->distinct(),
        ]));
        $this->host->data = ['items' => [
            'x' => ['code' => '1', 'tags' => ['a', 'b']],
            'y' => ['code' => 1, 'tags' => ['b']],
            'z' => ['code' => '2', 'tags' => ['c']],
        ]];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.items.x.code' => ['This value is already used by another item.'],
                'data.items.x.tags' => ['This value is already used by another item.'],
                'data.items.y.code' => ['This value is already used by another item.'],
                'data.items.y.tags' => ['This value is already used by another item.'],
            ]);
        });
    });

    it('lets one item switch a distinct toggle on, refuses a second, and asks for one while the toggle is required', function (): void {
        $optional = $this->host->mapping(Repeater::make('items')->components([Toggle::make('primary')->distinct()]));
        $this->host->data = ['items' => ['x' => ['primary' => true], 'y' => ['primary' => true], 'z' => ['primary' => false]]];

        expect(fn () => $this->host->validateSchema($optional))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.items.x.primary' => ['Only one item may be selected.'],
                'data.items.y.primary' => ['Only one item may be selected.'],
            ]);
        });

        $this->host->data = ['items' => ['x' => ['primary' => true], 'y' => ['primary' => false]]];

        expect($this->host->validateSchema($optional))->toBeNull();

        $required = $this->host->mapping(Repeater::make('items')->components([Toggle::make('primary')->required()->distinct()]));
        $this->host->data = ['items' => ['x' => ['primary' => false], 'y' => ['primary' => false]]];

        expect(fn () => $this->host->validateSchema($required))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.items.x.primary' => ['At least one item must be selected.'],
                'data.items.y.primary' => ['At least one item must be selected.'],
            ]);
        });
    });
});

describe('unique()', function (): void {
    it("refuses a value another row holds, probing the bound record's class at the field's own name, and passes the bound record's own value", function (): void {
        $this->seed($lamp = new Product('Lamp'), new Product('Desk'));
        $schema = $this->host->mapping(TextInput::make('name')->unique())->record($this->reload($lamp));
        $this->host->data = ['name' => 'Lamp'];

        expect($this->host->validateSchema($schema))->toBeNull();

        $this->host->data = ['name' => 'Desk'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.name' => ['This value is already taken.']]);
        });
    });

    it('probes the bound record too when told not to ignore it, by argument or by default', function (Closure $field): void {
        $this->seed($lamp = new Product('Lamp'));
        $schema = $this->host->mapping($field())->record($this->reload($lamp));
        $this->host->data = ['name' => 'Lamp'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.name' => ['This value is already taken.']]);
        });
    })->with([
        'ignoreRecord: false' => [fn (): TextInput => TextInput::make('name')->unique(ignoreRecord: false)],
        'uniqueValidationIgnoresRecordByDefault(false)' => [fn (): TextInput => TextInput::make('name')->unique()->uniqueValidationIgnoresRecordByDefault(false)],
    ]);

    it('excludes nothing for a record never flushed, so a create page refuses a value another row holds', function (): void {
        $this->seed(new Product('Lamp'));
        $schema = $this->host->mapping(TextInput::make('name')->unique())->record(new Product('Lamp'));
        $this->host->data = ['name' => 'Lamp'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.name' => ['This value is already taken.']]);
        });
    });

    it('validates nothing with no record class to probe, and probes the entity it is given', function (): void {
        $this->seed(new Product('Lamp'));
        $schema = $this->host->mapping(TextInput::make('name')->unique(), TextInput::make('title')->unique(entity: Product::class, property: 'name'));
        $this->host->data = ['name' => 'Lamp', 'title' => 'Lamp'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.title' => ['This value is already taken.']]);
        });
    });

    it('excludes the record ignorable: names, evaluating a closure for it', function (Closure $ignorable): void {
        $this->seed($lamp = new Product('Lamp'));
        $schema = $this->host->mapping(TextInput::make('name')->unique(entity: Product::class, ignorable: $ignorable($this->reload($lamp))), TextInput::make('title')->unique(entity: Product::class, property: 'name'));
        $this->host->data = ['name' => 'Lamp', 'title' => 'Lamp'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.title' => ['This value is already taken.']]);
        });
    })->with([
        'a record' => [static fn (Product $lamp): Product => $lamp],
        'a closure returning it' => [static fn (Product $lamp): Closure => static fn (): Product => $lamp],
    ]);

    it('narrows the probe through modifyQueryUsing, handing it the query builder with the closure injection a field keeps', function (): void {
        $this->seed(new Product('Lamp'));
        $narrowed = static fn (QueryBuilder $query, TextInput $component): QueryBuilder => $query->andWhere('record.name <> :spared')->setParameter('spared', $component->getName() === 'name' ? 'Lamp' : '');
        $schema = $this->host->mapping(TextInput::make('name')->unique(entity: Product::class, modifyQueryUsing: $narrowed), TextInput::make('title')->unique(entity: Product::class, property: 'name', modifyQueryUsing: $narrowed));
        $this->host->data = ['name' => 'Lamp', 'title' => 'Lamp'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.title' => ['This value is already taken.']]);
        })
            ->and($this->statementsRun())->toHaveCount(2);
    });

    it("excludes whatever row of another probed class carries the bound record's key", function (): void {
        $this->seed($lamp = new Product('Lamp'), new Article('Lamp'));
        $schema = $this->host->mapping(TextInput::make('name')->unique(entity: Article::class, property: 'title'))->record($this->reload($lamp));
        $this->host->data = ['name' => 'Lamp'];

        expect($this->host->validateSchema($schema))->toBeNull();

        $schema = $this->host->mapping(TextInput::make('name')->unique(entity: Article::class, property: 'title', ignoreRecord: false))->record($this->reload($lamp));

        expect(fn () => $this->host->validateSchema($schema))->toThrow(ValidationException::class);
    });

    it('answers a unique() on a key half during field validation, ahead of the record check', function (): void {
        $this->seed(new OrderLine(order: 1, line: 1));
        $schema = $this->host->mapping(TextInput::make('order')->unique(), TextInput::make('line'))->record(new OrderLine(order: 0, line: 0));
        $this->host->data = ['order' => '1', 'line' => '1'];

        expect(fn () => $schema->getData())->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.order' => ['This value is already taken.']]);
        });
    });

    it('takes a message keyed by UniqueValue', function (): void {
        $this->seed(new Product('Lamp'));
        $schema = $this->host->mapping(TextInput::make('name')->unique(entity: Product::class)->validationMessages([UniqueValue::class => 'Pick another name.']));
        $this->host->data = ['name' => 'Lamp'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.name' => ['Pick another name.']]);
        });
    });
});

describe('exists()', function (): void {
    it("refuses a value no row of the bound record's class holds at the field's own name", function (): void {
        $this->seed(new Product('Lamp'));
        $schema = $this->host->mapping(TextInput::make('name')->exists(), TextInput::make('title')->exists(entity: Product::class, property: 'name'))->record(Product::class);
        $this->host->data = ['name' => 'Lamp', 'title' => 'Desk'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.title' => ['The selected value does not exist.']]);
        });
    });
});

describe('date comparison', function (): void {
    it("builds the comparison Filament's rule spelling names", function (string $rule, array $errors): void {
        $schema = $this->host->mapping(TextInput::make('endsAt')->dateComparisonRule($rule, '2026-01-01'));
        $this->host->data = ['endsAt' => '2026-01-01'];

        if ($errors === []) {
            expect($this->host->validateSchema($schema))->toBeNull();

            return;
        }

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception) use ($errors): void {
            expect($exception->getErrors())->toBe(['data.endsAt' => $errors]);
        });
    })->with([
        'after' => ['after', ['This value must be after 2026-01-01 00:00:00.']],
        'after_or_equal' => ['after_or_equal', []],
        'before' => ['before', ['This value must be before 2026-01-01 00:00:00.']],
        'before_or_equal' => ['before_or_equal', []],
    ]);

    it('compares against a literal date, or against a sibling field read again on each run', function (): void {
        $schema = $this->host->mapping(
            TextInput::make('startsAt'),
            TextInput::make('endsAt')->after('startsAt'),
            TextInput::make('reviewedAt')->before('2026-01-01'),
        );
        $this->host->data = ['startsAt' => '2026-06-01', 'endsAt' => '2026-03-01', 'reviewedAt' => '2027-01-01'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.endsAt' => ['This value must be after 2026-06-01 00:00:00.'],
                'data.reviewedAt' => ['This value must be before 2026-01-01 00:00:00.'],
            ]);
        });

        $this->host->data = ['startsAt' => '2026-01-01', 'endsAt' => '2026-03-01', 'reviewedAt' => '2025-01-01'];

        expect($this->host->validateSchema($schema))->toBeNull();
    });

    it("reads a sibling relative to its own container, a slash-led name from the root, and a field named like a date as a date", function (): void {
        $schema = $this->host->mapping(
            Section::make('Period')->statePath('period')->components([
                TextInput::make('startsAt'),
                TextInput::make('endsAt')->after('startsAt'),
            ]),
            TextInput::make('closedAt')->after('/data.period.startsAt'),
            TextInput::make('may'),
            TextInput::make('noted')->after('may'),
            TextInput::make('flagged')->after('2999-01-01', isStatePathAbsolute: true),
        );
        $this->host->data = [
            'period' => ['startsAt' => '2026-06-01', 'endsAt' => '2026-03-01'],
            'closedAt' => '2026-03-01',
            'may' => '1900-01-01',
            'noted' => '1950-01-01',
            'flagged' => '2000-01-01',
        ];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toHaveKeys(['data.period.endsAt', 'data.closedAt', 'data.noted', 'data.flagged'])
                ->and($exception->getErrors()['data.period.endsAt'])->toBe(['This value must be after 2026-06-01 00:00:00.'])
                ->and($exception->getErrors()['data.closedAt'])->toBe(['This value must be after 2026-06-01 00:00:00.'])
                ->and($exception->getErrors()['data.noted'][0])->toStartWith('This value must be after ')
                ->and($exception->getErrors()['data.flagged'])->toBe(['This value must be after 2999-01-01 00:00:00.']);
        });
    });

    it('validates clean against a blank or unparseable sibling whatever the operator, leaving presence to the sibling', function (string $method, string $startsAt): void {
        $schema = $this->host->mapping(TextInput::make('startsAt'), TextInput::make('endsAt')->{$method}('startsAt'));
        $this->host->data = ['startsAt' => $startsAt, 'endsAt' => '2026-03-01'];

        expect($this->host->validateSchema($schema))->toBeNull();
    })->with([
        'after, a blank sibling' => ['after', ''],
        'after or equal, a blank sibling' => ['afterOrEqual', ''],
        'before, a blank sibling' => ['before', ''],
        'before or equal, a blank sibling' => ['beforeOrEqual', ''],
        'after, an unparseable sibling' => ['after', 'soon'],
    ]);

    it('files only the required error of a blank sibling that is required, none on the field comparing against it', function (): void {
        $schema = $this->host->mapping(TextInput::make('startsAt')->required(), TextInput::make('endsAt')->before('startsAt'));
        $this->host->data = ['startsAt' => '', 'endsAt' => '2026-03-01'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.startsAt' => ['This value should not be blank.']]);
        });
    });
});
