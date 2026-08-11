<?php

declare(strict_types=1);

namespace Sylament\Tables\Columns\Concerns;

use Closure;
use LogicException;
use Sylament\Tables\Query\AggregateDeclaration;
use Symfony\Component\String\UnicodeString;

trait CanAggregateRelatedRecords
{
    protected string|Closure|null $columnToAvg = null;

    /**
     * @var string|array<int|string, string|Closure>|Closure|null
     */
    protected string|array|Closure|null $relationshipToAvg = null;

    /**
     * @var string|array<int|string, string|Closure>|Closure|null
     */
    protected string|array|Closure|null $relationshipsToCount = null;

    /**
     * @var string|array<int|string, string|Closure>|Closure|null
     */
    protected string|array|Closure|null $relationshipsToExistenceCheck = null;

    protected string|Closure|null $columnToMax = null;

    /**
     * @var string|array<int|string, string|Closure>|Closure|null
     */
    protected string|array|Closure|null $relationshipToMax = null;

    protected string|Closure|null $columnToMin = null;

    /**
     * @var string|array<int|string, string|Closure>|Closure|null
     */
    protected string|array|Closure|null $relationshipToMin = null;

    protected string|Closure|null $columnToSum = null;

    /**
     * @var string|array<int|string, string|Closure>|Closure|null
     */
    protected string|array|Closure|null $relationshipToSum = null;

    /**
     * @param string|array<int|string, string|Closure>|Closure|null $relationship
     */
    public function avg(string|array|Closure|null $relationship, string|Closure|null $column): static
    {
        $this->columnToAvg = $column;
        $this->relationshipToAvg = $relationship;

        return $this;
    }

    /**
     * @param string|array<int|string, string|Closure>|Closure|null $relationships
     */
    public function counts(string|array|Closure|null $relationships): static
    {
        $this->relationshipsToCount = $relationships;

        return $this;
    }

    /**
     * @param string|array<int|string, string|Closure>|Closure|null $relationships
     */
    public function exists(string|array|Closure|null $relationships): static
    {
        $this->relationshipsToExistenceCheck = $relationships;

        return $this;
    }

    /**
     * @param string|array<int|string, string|Closure>|Closure|null $relationship
     */
    public function max(string|array|Closure|null $relationship, string|Closure|null $column): static
    {
        $this->columnToMax = $column;
        $this->relationshipToMax = $relationship;

        return $this;
    }

    /**
     * @param string|array<int|string, string|Closure>|Closure|null $relationship
     */
    public function min(string|array|Closure|null $relationship, string|Closure|null $column): static
    {
        $this->columnToMin = $column;
        $this->relationshipToMin = $relationship;

        return $this;
    }

    /**
     * @param string|array<int|string, string|Closure>|Closure|null $relationship
     */
    public function sum(string|array|Closure|null $relationship, string|Closure|null $column): static
    {
        $this->columnToSum = $column;
        $this->relationshipToSum = $relationship;

        return $this;
    }

    public function getColumnToAvg(): ?string
    {
        return $this->evaluate($this->columnToAvg);
    }

    /**
     * @return string|array<int|string, string|Closure>|null
     */
    public function getRelationshipToAvg(): string|array|null
    {
        return $this->evaluate($this->relationshipToAvg);
    }

    /**
     * @return string|array<int|string, string|Closure>|null
     */
    public function getRelationshipsToCount(): string|array|null
    {
        return $this->evaluate($this->relationshipsToCount);
    }

    /**
     * @return string|array<int|string, string|Closure>|null
     */
    public function getRelationshipsToExistenceCheck(): string|array|null
    {
        return $this->evaluate($this->relationshipsToExistenceCheck);
    }

    public function getColumnToMax(): ?string
    {
        return $this->evaluate($this->columnToMax);
    }

    /**
     * @return string|array<int|string, string|Closure>|null
     */
    public function getRelationshipToMax(): string|array|null
    {
        return $this->evaluate($this->relationshipToMax);
    }

    public function getColumnToMin(): ?string
    {
        return $this->evaluate($this->columnToMin);
    }

    /**
     * @return string|array<int|string, string|Closure>|null
     */
    public function getRelationshipToMin(): string|array|null
    {
        return $this->evaluate($this->relationshipToMin);
    }

    public function getColumnToSum(): ?string
    {
        return $this->evaluate($this->columnToSum);
    }

    /**
     * @return string|array<int|string, string|Closure>|null
     */
    public function getRelationshipToSum(): string|array|null
    {
        return $this->evaluate($this->relationshipToSum);
    }

    public function hasRelationshipAggregates(): bool
    {
        return array_any([
            $this->getColumnToAvg(),
            $this->getRelationshipToAvg(),
            $this->getRelationshipsToCount(),
            $this->getRelationshipsToExistenceCheck(),
            $this->getColumnToMax(),
            $this->getRelationshipToMax(),
            $this->getColumnToMin(),
            $this->getRelationshipToMin(),
            $this->getColumnToSum(),
            $this->getRelationshipToSum(),
        ], static fn (mixed $declared): bool => $declared !== null);
    }

    /**
     * The one declaration whose value this column's cell reads, or null when
     * none does. A kind declared in the singular string form belongs to this
     * column whatever it is named; the array form declares several at once, so
     * an entry only belongs here when its alias — explicit through
     * `relationship as alias`, otherwise derived the way Eloquent derives it —
     * is the column name.
     *
     * @throws LogicException when the column declares more than one kind and
     *                        no alias among them names it, which cannot be a
     *                        cell value and is always a misdeclaration
     */
    public function getCellAggregateDeclaration(): ?AggregateDeclaration
    {
        $groups = array_values(array_filter([
            $this->buildAggregateEntries($this->getRelationshipToAvg(), 'AVG', 'avg', $this->getColumnToAvg()),
            $this->buildAggregateEntries($this->getRelationshipsToCount(), 'COUNT', 'count'),
            $this->buildAggregateEntries($this->getRelationshipsToExistenceCheck(), 'COUNT', 'exists', castToBool: true),
            $this->buildAggregateEntries($this->getRelationshipToMax(), 'MAX', 'max', $this->getColumnToMax()),
            $this->buildAggregateEntries($this->getRelationshipToMin(), 'MIN', 'min', $this->getColumnToMin()),
            $this->buildAggregateEntries($this->getRelationshipToSum(), 'SUM', 'sum', $this->getColumnToSum()),
        ], static fn (array $entries): bool => $entries !== []));

        if ($groups === []) {
            return null;
        }

        $name = $this->getName();

        foreach ($groups as $entries) {
            foreach ($entries as $entry) {
                if ($entry['alias'] === $name) {
                    return $entry['declaration'];
                }
            }
        }

        if (count($groups) > 1) {
            throw new LogicException(sprintf(
                'Column [%s] declares multiple aggregates and none of them is aliased [%s], so no single value can fill its cell. Alias the one it reads, or move the others onto columns of their own.',
                $name,
                $name,
            ));
        }

        return $groups[0][0]['bindsToAnyName'] ? $groups[0][0]['declaration'] : null;
    }

    /**
     * @param string|array<int|string, string|Closure>|null $relationships
     * @return list<array{alias: string, declaration: AggregateDeclaration, bindsToAnyName: bool}>
     */
    private function buildAggregateEntries(
        string|array|null $relationships,
        string $function,
        string $aliasSuffix,
        ?string $column = null,
        bool $castToBool = false,
    ): array {
        if ($relationships === null) {
            return [];
        }

        $bindsToAnyName = is_string($relationships);

        $entries = [];

        foreach (is_string($relationships) ? [$relationships] : $relationships as $key => $value) {
            $declared = is_string($key) ? $key : $value;
            $scope = is_string($key) ? $value : null;

            if (! is_string($declared) || ($scope !== null && ! $scope instanceof Closure)) {
                throw new LogicException(sprintf(
                    'Column [%s] declares a malformed aggregate: name the relationship as an array value, or as an array key whose value is the closure scoping it.',
                    $this->getName(),
                ));
            }

            [$relationshipPath, $alias] = self::parseAggregateRelationship($declared);

            $entries[] = [
                'alias' => $alias ?? self::deriveAggregateAlias($relationshipPath, $aliasSuffix, $column),
                'declaration' => new AggregateDeclaration(
                    function: $function,
                    relationshipPath: $relationshipPath,
                    column: $column,
                    scope: $scope,
                    castToBool: $castToBool,
                ),
                'bindsToAnyName' => $bindsToAnyName,
            ];
        }

        return $entries;
    }

    /**
     * @return array{string, ?string} the relationship path, then the alias its
     *                                `as` clause gave it, or null for none
     */
    private static function parseAggregateRelationship(string $relationship): array
    {
        $segments = explode(' ', $relationship);

        if (count($segments) === 3 && mb_strtolower($segments[1]) === 'as') {
            return [$segments[0], $segments[2]];
        }

        return [$relationship, null];
    }

    private static function deriveAggregateAlias(string $relationshipPath, string $aliasSuffix, ?string $column): string
    {
        $alias = new UnicodeString($relationshipPath)->snake() . '_' . $aliasSuffix;

        if ($column === null) {
            return $alias;
        }

        return $alias . '_' . new UnicodeString($column)->snake();
    }
}
