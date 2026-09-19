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
     * @param array<array-key, mixed> $row one scalar or array result row
     *
     * @throws LogicException when the row lacks a selected key column
     */
    public function keyOf(array $row): string
    {
        $platform = $this->entityManager->getConnection()->getDatabasePlatform();
        $values = [];

        foreach ($this->resultAliases as $index => $resultAlias) {
            if (!array_key_exists($resultAlias, $row)) {
                throw new LogicException(sprintf('A result row of [%s] lacks the key column [%s], so it was not read through the selection that keys it.', $this->fields->class, $resultAlias));
            }

            $value = $row[$resultAlias];

            $values[] = is_object($value)
                ? $value
                : Type::getType($this->fields->types[$index])->convertToPHPValue($value, $platform);
        }

        return RecordKey::fromValues($this->entityManager, $this->fields->class, $values);
    }
}
