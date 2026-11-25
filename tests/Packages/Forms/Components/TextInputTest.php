<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Components\Section;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Forms\TextInputForm;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Url;
use Twig\Error\RuntimeError;

describe('rendering', function (): void {
    it('renders its state as the value of its control, and an empty value while it has none', function (): void {
        expect($this->createLiveComponent(TextInputForm::class, ['data' => ['plain' => 'Hello']])->render()->crawler())
            ->toContainElement('input[name="plain"][value="Hello"]')
            ->toContainElement('input[name="email"][value=""]');
    });

    it('greys out its wrapper and disables its control while disabled', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('code')->disabled()))
            ->toContainElement('.sy-fo-text-input' . SupportSelectors::DISABLED . ' input[name="code"][disabled]')
            ->and($this->renderSchemaComponent(TextInput::make('code')))
            ->toContainElement('.sy-fo-text-input:not(' . SupportSelectors::DISABLED . ') input[name="code"]:not([disabled])');
    });
});

describe('type', function (): void {
    it('renders the type its sugar asks for, the first in order when several do, and text when none does', function (Closure $field, string $type): void {
        expect($this->renderSchemaComponent($field()))->toContainElement("input[name=\"a\"][type=\"{$type}\"]");
    })->with([
        'plain' => [fn (): TextInput => TextInput::make('a'), 'text'],
        'email' => [fn (): TextInput => TextInput::make('a')->email(), 'email'],
        'numeric' => [fn (): TextInput => TextInput::make('a')->numeric(), 'number'],
        'integer' => [fn (): TextInput => TextInput::make('a')->integer(), 'number'],
        'password' => [fn (): TextInput => TextInput::make('a')->password(), 'password'],
        'tel' => [fn (): TextInput => TextInput::make('a')->tel(), 'tel'],
        'url' => [fn (): TextInput => TextInput::make('a')->url(), 'url'],
        'email before url' => [fn (): TextInput => TextInput::make('a')->url()->email(), 'email'],
        'an explicit type over a sugar' => [fn (): TextInput => TextInput::make('a')->email()->type('search'), 'search'],
        'a sugar whose condition resolves false' => [fn (): TextInput => TextInput::make('a')->email(static fn (): bool => false)->tel(static fn (): bool => false)->url(static fn (): bool => false), 'text'],
    ]);
});

describe('type sugar', function (): void {
    it('refuses a value its email or url sugar does not accept, and takes one it does', function (string $field, string $refused, string $constraint, string $accepted): void {
        $this->createLiveComponent(TextInputForm::class)
            ->fillForm([$field => $refused])
            ->call('save')
            ->assertHasFormErrors([$field => $constraint])
            ->fillForm([$field => $accepted])
            ->call('save')
            ->assertHasNoFormErrors([$field]);
    })->with([
        'email' => ['email', 'nope', Email::class, 'ada@example.com'],
        'url' => ['site', 'nope', Url::class, 'https://example.com'],
    ]);

    it('validates nothing for an email, tel or url sugar whose condition resolves false when the form is validated', function (): void {
        $this->createLiveComponent(TextInputForm::class)
            ->fillForm(['lazy' => 'nope', 'email' => 'nope'])
            ->call('save')
            ->assertHasFormErrors(['email'])
            ->assertHasNoFormErrors(['lazy']);
    });
});

describe('numeric', function (): void {
    it('renders a number input taking decimals, or whole numbers when integer, and a text input when its condition resolves false', function (Closure $field, string $selector): void {
        expect($this->renderSchemaComponent($field()))->toContainElement($selector);
    })->with([
        'plain' => [fn (): TextInput => TextInput::make('a'), 'input[name="a"][type="text"]:not([step]):not([inputmode])'],
        'numeric' => [fn (): TextInput => TextInput::make('a')->numeric(), 'input[name="a"][type="number"][step="any"][inputmode="decimal"]'],
        'integer' => [fn (): TextInput => TextInput::make('a')->integer(), 'input[name="a"][type="number"][step="1"][inputmode="numeric"]'],
        'numeric resolving false' => [fn (): TextInput => TextInput::make('a')->numeric(static fn (): bool => false), 'input[name="a"][type="text"]:not([step]):not([inputmode])'],
        'integer resolving false' => [fn (): TextInput => TextInput::make('a')->integer(static fn (): bool => false), 'input[name="a"][type="text"]:not([step]):not([inputmode])'],
    ]);

    it('refuses a value that is not a number', function (): void {
        $this->createLiveComponent(TextInputForm::class)
            ->fillForm(['price' => 'abc'])
            ->call('save')
            ->assertHasFormErrors(['price' => Type::class]);
    });
});

describe('integer', function (): void {
    it('takes a whole number, signed or not, however long', function (string $value): void {
        $this->createLiveComponent(TextInputForm::class)
            ->fillForm(['age' => $value])
            ->call('save')
            ->assertHasNoFormErrors(['age']);
    })->with([
        'unsigned' => ['5'],
        'negative' => ['-5'],
        'positive' => ['+5'],
        'past the largest integer' => ['99999999999999999999'],
    ]);

    it('refuses a decimal and a padded number with its pattern alone', function (string $value): void {
        $this->createLiveComponent(TextInputForm::class)
            ->fillForm(['age' => $value])
            ->call('save')
            ->assertHasFormErrors(['age' => Regex::class])
            ->assertHasNoFormErrors(['age' => Type::class]);
    })->with([
        'a decimal' => ['5.5'],
        'a padded number' => [' 5 '],
    ]);

    it('refuses prose with both the numeric check and its pattern', function (): void {
        $this->createLiveComponent(TextInputForm::class)
            ->fillForm(['age' => 'abc'])
            ->call('save')
            ->assertHasFormErrors(['age' => Type::class])
            ->assertHasFormErrors(['age' => Regex::class]);
    });
});

describe('tel', function (): void {
    it('takes a phone number and refuses prose', function (): void {
        $tester = $this->createLiveComponent(TextInputForm::class);

        foreach (['+420 123 456 789', '(123)456-7890', '1234567890'] as $number) {
            $tester->fillForm(['phone' => $number])->call('save')->assertHasNoFormErrors(['phone']);
        }

        $tester->fillForm(['phone' => 'abc'])->call('save')->assertHasFormErrors(['phone' => Regex::class]);
    });

    it('validates with its own pattern when given one', function (): void {
        $this->createLiveComponent(TextInputForm::class)
            ->fillForm(['custom_phone' => '+420 123'])
            ->call('save')
            ->assertHasFormErrors(['custom_phone' => Regex::class])
            ->fillForm(['custom_phone' => '420123'])
            ->call('save')
            ->assertHasNoFormErrors(['custom_phone']);
    });

    it('shares one pattern with regex(), so the later of the two calls decides it', function (string $field, string $accepted, string $refused): void {
        $this->createLiveComponent(TextInputForm::class)
            ->fillForm([$field => $accepted])
            ->call('save')
            ->assertHasNoFormErrors([$field])
            ->fillForm([$field => $refused])
            ->call('save')
            ->assertHasFormErrors([$field => Regex::class]);
    })->with([
        'regex() after tel()' => ['phone_then_regex', 'xxx', '123'],
        'tel() after regex()' => ['regex_then_phone', '123', 'xxx'],
    ]);
});

describe('validation arms', function (): void {
    it('reports its length, its pattern, its own constraint, its type and its bounds in that order', function (): void {
        $render = $this->createLiveComponent(TextInputForm::class)
            ->fillForm(['ordered' => 'ab'])
            ->call('save')
            ->render()->crawler();

        expect($render->filter(Selectors::FIELD_WRAPPER)->reduce(static fn (Crawler $wrapper): bool => $wrapper->filter('input[name="ordered"]')->count() > 0)
            ->filter(Selectors::ERROR_MESSAGE)->each(static fn (Crawler $message): string => $message->text()))->toBe([
                'This value is too short. It should have 5 characters or more.',
                'This value is not valid.',
                'Pick another value.',
                'This value is not a valid email address.',
                'This value should be less than or equal to 0.',
            ]);
    });

    it('reports only that it is required while it is empty', function (): void {
        $this->createLiveComponent(TextInputForm::class)
            ->fillForm(['ordered' => ''])
            ->call('save')
            ->assertHasFormErrors(['ordered' => RequiredValue::class])
            ->assertHasNoFormErrors(['ordered' => Email::class]);
    });
});

describe('value bounds', function (): void {
    it('renders its bounds on the control', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('price')->numeric()->minValue(1)->maxValue(10)))
            ->toContainElement('input[name="price"][min="1"][max="10"]');
    });

    it('compares a submitted number by its magnitude', function (string $value, ?string $constraint): void {
        $tester = $this->createLiveComponent(TextInputForm::class)->fillForm(['price' => $value])->call('save');

        $constraint === null ? $tester->assertHasNoFormErrors(['price']) : $tester->assertHasFormErrors(['price' => $constraint]);
    })->with([
        'within' => ['7', null],
        'above the maximum' => ['70', LessThanOrEqual::class],
        'below the minimum' => ['0', GreaterThanOrEqual::class],
        'prose' => ['abc', LessThanOrEqual::class],
    ]);

    it('builds no bound that resolves blank, and keeps a zero bound', function (): void {
        $this->createLiveComponent(TextInputForm::class)
            ->fillForm(['quantity' => '5', 'delta' => '-1'])
            ->call('save')
            ->assertHasNoFormErrors(['quantity'])
            ->assertHasFormErrors(['delta' => GreaterThanOrEqual::class]);
    });

    it('reports no bound against a cleared input, and only that a required one is required', function (): void {
        $this->createLiveComponent(TextInputForm::class)
            ->fillForm(['price' => '', 'amount' => ''])
            ->call('save')
            ->assertHasNoFormErrors(['price'])
            ->assertHasFormErrors(['amount' => RequiredValue::class])
            ->assertHasNoFormErrors(['amount' => GreaterThanOrEqual::class]);
    });
});

describe('currentPassword()', function (): void {
    it("checks the signed-in user's password only when asked", function (): void {
        $classes = static fn (TextInput $field): array => array_map(get_class(...), $field->getValidationConstraints());

        expect($classes(TextInput::make('current')->password()->currentPassword()))->toContain(UserPassword::class)
            ->and($classes(TextInput::make('current')->password()))->not->toContain(UserPassword::class);
    });
});

describe('revealable', function (): void {
    it('renders a password control with a show and a hide button, bound to its controller', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('secret')->password()->revealable()))
            ->toContainElement('[data-controller="sylament--forms--text-input"] input[name="secret"][type="password"].sy-revealable' . Selectors::TEXT_INPUT_TARGET)
            ->toContainButtons(SupportSelectors::SUFFIX_ACTIONS, ['Show password', 'Hide password']);
    });

    it('refuses to reveal a field that is not a password, naming the field', function (): void {
        expect(fn (): Crawler => $this->renderSchemaComponent(TextInput::make('secret')->revealable()))
            ->toThrow(RuntimeError::class, 'The text input [data.secret] is not a [password()], so it cannot be [revealable()].');
    });
});

describe('copyable', function (): void {
    it('renders a copy button and an empty status region announcing the copy, bound to its controller, and neither on a field that does not copy', function (): void {
        $copyable = $this->renderSchemaComponent(TextInput::make('code')->copyable());
        $plain = $this->renderSchemaComponent(TextInput::make('code'));

        expect($copyable)
            ->toContainElement('.sy-fo-text-input[data-controller="sylament--forms--text-input"] input[name="code"]' . Selectors::TEXT_INPUT_TARGET)
            ->toContainElement('[role="status"][aria-live="polite"][data-sylament--forms--text-input-target="copyMessage"]:not([hidden])')
            ->toContainButtons(SupportSelectors::SUFFIX_ACTIONS, ['Copy'])
            ->and($copyable->filter('[role="status"]')->text())->toBe('')
            ->and($plain)
            ->toContainElement('.sy-fo-text-input:not([data-controller]) input[name="code"]:not(' . Selectors::TEXT_INPUT_TARGET . ')')
            ->not->toContainElement('[role="status"]');
    });
});

describe('state hooks', function (): void {
    it('validates and saves its trimmed, stripped value and leaves what was typed, and saves an untouched field as typed', function (): void {
        $tester = $this->createLiveComponent(TextInputForm::class)
            ->fillForm(['slug' => ' a-b ', 'plain' => ' a-b '])
            ->call('save')
            ->assertHasNoFormErrors(['slug']);

        expect($tester->component()->getSchema('form')->getStateSnapshot())
            ->toHaveKey('slug', 'ab')
            ->toHaveKey('plain', ' a-b ')
            ->and($tester->component()->data)
            ->toHaveKey('slug', ' a-b ');
    });
});

describe('native constraints', function (): void {
    it('keeps its native required, bounds and lengths inside a collapsible section', function (): void {
        expect($this->renderSchemaComponent(Section::make('Details')->collapsible()->components([TextInput::make('n')->numeric()->required()->minValue(1)->maxValue(9)])))
            ->toContainElement('input[name="n"][required][min="1"][max="9"]')
            ->and($this->renderSchemaComponent(Section::make('Details')->collapsible()->components([TextInput::make('code')->minLength(2)->maxLength(5)])))
            ->toContainElement('input[name="code"][minlength="2"][maxlength="5"]');
    });
});
