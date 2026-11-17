<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Forms\Validation\UniqueValue;
use Sylament\Forms\Validation\UniqueValueValidator;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\RevisionedNote;
use Sylament\Tests\Fixtures\Schemas\Doctrine\WithdrawnNoteFilter;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('refuses a value another row holds at the property and passes one no row holds', function (): void {
    $this->seed(new Product('Lamp'));
    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $taken = $validator->validate('Lamp', new UniqueValue(Product::class, 'name'));

    expect($taken)->toHaveCount(1)
        ->and($taken->get(0)->getMessage())->toBe('This value is already taken.')
        ->and($validator->validate('Desk', new UniqueValue(Product::class, 'name')))->toHaveCount(0);
});

it('reads a blank value as free without running a statement', function (mixed $value): void {
    $this->seed(new Product(''));

    expect($this::getContainer()->get(ValidatorInterface::class)->validate($value, new UniqueValue(Product::class, 'name')))->toHaveCount(0)
        ->and($this->statementsRun())->toBe([]);
})->with(['null' => [null], 'an empty string' => ['']]);

it('refuses to probe when it was built without the association reader, naming the bundle that wires it', function (): void {
    $validator = Validation::createValidatorBuilder()
        ->setConstraintValidatorFactory(new ConstraintValidatorFactory([UniqueValueValidator::class => new UniqueValueValidator()]))
        ->getValidator();

    expect(fn (): ConstraintViolationListInterface => $validator->validate('Lamp', new UniqueValue(Product::class, 'name')))
        ->toThrow(LogicException::class, 'Sylament\Forms\Validation\UniqueValueValidator is a container service — SylamentFormsBundle registers it with its AssociationReader; a validator built with `new` cannot probe the database.');
});

describe('exclusion', function (): void {
    it('excludes the one row its ignored key names, and a key of another width excludes none', function (Closure $rows): void {
        [$own, $other] = $rows();
        $this->seed($own, $other);
        $key = $this::getContainer()->get(AssociationReader::class)->findRecordKey($this->reload($own));
        $validator = $this::getContainer()->get(ValidatorInterface::class);

        expect($validator->validate('Own', new UniqueValue($own::class, 'note', ignoredIdentifier: $key)))->toHaveCount(0)
            ->and($validator->validate('Other', new UniqueValue($own::class, 'note', ignoredIdentifier: $key)))->toHaveCount(1)
            ->and($validator->validate('Own', new UniqueValue($own::class, 'note', ignoredIdentifier: '1')))->toHaveCount(1)
            ->and($validator->validate('Own', new UniqueValue($own::class, 'note')))->toHaveCount(1);
    })->with([
        'two scalar fields' => fn (): array => [new OrderLine(order: 1, line: 1, note: 'Own'), new OrderLine(order: 1, line: 2, note: 'Other')],
        'an association plus a scalar' => function (): array {
            $invoice = new Invoice(7);

            return [new InvoiceLine($invoice, line: 1, note: 'Own'), new InvoiceLine($invoice, line: 2, note: 'Other')];
        },
    ]);

    it("hands its modifier the query with the value and the ignored key already bound, under names it can read", function (): void {
        $this->seed($lamp = new Product('Lamp'));
        $bound = [];

        $this::getContainer()->get(ValidatorInterface::class)->validate('Lamp', new UniqueValue(
            Product::class,
            'name',
            ignoredIdentifier: (string) $lamp->id,
            modifyQueryUsing: static function (QueryBuilder $query) use (&$bound): void {
                $bound = [$query->getParameter('sylament_probe_0')?->getValue(), $query->getParameter('sylament_ignored_record')?->getValue()];
            },
        ));

        expect($bound)->toBe(['Lamp', $lamp->id]);
    });
});

describe('filters', function (): void {
    it('reads a value only a row a Doctrine filter hides holds as free', function (): void {
        $note = new RevisionedNote(code: 'N1', revision: 1);
        $note->note = 'withdrawn';
        $this->seed($note);
        $validator = $this::getContainer()->get(ValidatorInterface::class);

        expect($validator->validate('withdrawn', new UniqueValue(RevisionedNote::class, 'note')))->toHaveCount(1);

        $this->entityManager()->getFilters()->enable(WithdrawnNoteFilter::NAME);

        expect($validator->validate('withdrawn', new UniqueValue(RevisionedNote::class, 'note')))->toHaveCount(0);
    });
});
