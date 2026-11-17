<?php

declare(strict_types=1);

use Sylament\Forms\Validation\ExistingValue;
use Sylament\Forms\Validation\ExistingValueValidator;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('passes a value a row holds at the property and refuses one no row holds', function (): void {
    $this->seed(new Product('Lamp'));
    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $missing = $validator->validate('Desk', new ExistingValue(Product::class, 'name'));

    expect($validator->validate('Lamp', new ExistingValue(Product::class, 'name')))->toHaveCount(0)
        ->and($missing)->toHaveCount(1)
        ->and($missing->get(0)->getMessage())->toBe('The selected value does not exist.');
});

it('checks a list in one statement, every distinct value it asks for having to exist', function (array $value, bool $passes): void {
    $this->seed(new Product('Lamp'), new Product('Desk'));

    expect($this::getContainer()->get(ValidatorInterface::class)->validate($value, new ExistingValue(Product::class, 'name')))->toHaveCount($passes ? 0 : 1)
        ->and($this->statementsRun())->toHaveCount(1);
})->with([
    'every value present' => [['Lamp', 'Desk'], true],
    'a value asked for twice' => [['Lamp', 'Lamp', 'Desk'], true],
    'two of three present' => [['Lamp', 'Desk', 'Sofa'], false],
]);

it('reads a blank value as present without running a statement', function (mixed $value): void {
    $this->seed(new Product('Lamp'));

    expect($this::getContainer()->get(ValidatorInterface::class)->validate($value, new ExistingValue(Product::class, 'name')))->toHaveCount(0)
        ->and($this->statementsRun())->toBe([]);
})->with(['null' => [null], 'an empty string' => [''], 'an empty list' => [[]]]);

it('refuses to probe when it was built without the association reader, naming the bundle that wires it', function (): void {
    $validator = Validation::createValidatorBuilder()
        ->setConstraintValidatorFactory(new ConstraintValidatorFactory([ExistingValueValidator::class => new ExistingValueValidator()]))
        ->getValidator();

    expect(fn (): ConstraintViolationListInterface => $validator->validate('Lamp', new ExistingValue(Product::class, 'name')))
        ->toThrow(LogicException::class, 'Sylament\Forms\Validation\ExistingValueValidator is a container service — SylamentFormsBundle registers it with its AssociationReader; a validator built with `new` cannot probe the database.');
});

it('probes a scalar identifier half as the ordinary column it is', function (object $line): void {
    $this->seed($line);
    $validator = $this::getContainer()->get(ValidatorInterface::class);

    expect($validator->validate(2, new ExistingValue($line::class, 'line')))->toHaveCount(0)
        ->and($validator->validate(3, new ExistingValue($line::class, 'line')))->toHaveCount(1);
})->with([
    'two scalar fields' => fn (): OrderLine => new OrderLine(order: 1, line: 2),
    'an association plus a scalar' => fn (): InvoiceLine => new InvoiceLine(new Invoice(7), line: 2),
]);

it('refuses an identifier association as the unmapped field it is, rather than probing by key', function (): void {
    $this->seed(new InvoiceLine(new Invoice(7), line: 2));

    expect(fn () => $this::getContainer()->get(ValidatorInterface::class)->validate('7', new ExistingValue(InvoiceLine::class, 'invoice')))
        ->toThrow(LogicException::class, 'exists() probes [Sylament\Tests\Fixtures\Entity\InvoiceLine] by [invoice], which is not a mapped field of that entity. Name a field it maps: [line], [note].')
        ->and($this->statementsRun())->toBe([]);
});
