<?php

declare(strict_types=1);

namespace Sylament\Schemas\Identity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final readonly class KeySelection
{
    /**
     * @internal built by {@see IdentifierConstraint::select()}
     *
     * @param list<string> $expressions   one `<dql> AS <alias>` per identifier field
     * @param list<string> $resultAliases the alias each expression hydrates under
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private IdentifierFields $fields,
        public array $expressions,
        private array $resultAliases,
    ) {
    }

    /**
     * @param array<array-key, mixed> $row one scalar result row, every value raw
     *
     * @throws LogicException when the row lacks a selected key column
     */
    public function keyOf(array $row): string
    {
        return $this->mint($row, convertFields: true);
    }

    /**
     * The key of a row whose hydrator already read every field through its type
     * — an array result, or the rows Doctrine's limit walker answers — so only an
     * IDENTITY() association half, raw under every hydrator, is converted here.
     *
     * @param array<array-key, mixed> $row
     *
     * @throws LogicException when the row lacks a selected key column
     */
    public function keyOfHydrated(array $row): string
    {
        return $this->mint($row, convertFields: false);
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function mint(array $row, bool $convertFields): string
    {
        $platform = $this->entityManager->getConnection()->getDatabasePlatform();
        $values = [];

        foreach ($this->resultAliases as $index => $resultAlias) {
            if (!array_key_exists($resultAlias, $row)) {
                throw new LogicException(sprintf('A result row of [%s] lacks the key column [%s], so it was not read through the selection that keys it.', $this->fields->class, $resultAlias));
            }

            $value = $row[$resultAlias];

            $values[] = is_object($value) || (!$convertFields && !$this->fields->associations[$index])
                ? $value
                : Type::getType($this->fields->types[$index])->convertToPHPValue($value, $platform);
        }

        return $this->fields->toKey($values);
    }
}
