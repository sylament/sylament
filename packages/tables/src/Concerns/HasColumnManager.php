<?php

declare(strict_types=1);

namespace Sylament\Tables\Concerns;

use Symfony\UX\LiveComponent\Attribute\LiveProp;

trait HasColumnManager
{
    public const string TABLE_COLUMN_MANAGER_COLUMN_TYPE = 'column';

    /** @var list<array{type: string, name: string, isToggled: bool, isToggledHiddenByDefault: bool|null}> */
    #[LiveProp]
    public array $tableColumns = [];

    /** @var list<array{type: string, name: string, isToggled: bool, isToggledHiddenByDefault: bool|null}> */
    #[LiveProp(writable: true)]
    public array $tableDeferredColumns = [];

    /** @var array<string, bool>|null */
    private ?array $tableColumnsToggleStateByName = null;

    /**
     * Replaces the applied column set, dropping everything memoised from the
     * old one: this trait's per-name toggle map and the table's visible set.
     *
     * @param list<array{type: string, name: string, isToggled: bool, isToggledHiddenByDefault: bool|null}> $tableColumns
     */
    protected function setTableColumns(array $tableColumns): void
    {
        $this->tableColumns = $tableColumns;
        $this->tableColumnsToggleStateByName = null;

        $this->getTable()->flushCachedVisibleColumns();
    }

    public function isTableColumnToggledHidden(string $name): bool
    {
        return !($this->getTableColumnsToggleStateByName()[$name] ?? false);
    }

    /**
     * @return array<string, bool>
     */
    private function getTableColumnsToggleStateByName(): array
    {
        if ($this->tableColumnsToggleStateByName !== null) {
            return $this->tableColumnsToggleStateByName;
        }

        $map = [];

        foreach ($this->tableColumns as $item) {
            if ($item['type'] === self::TABLE_COLUMN_MANAGER_COLUMN_TYPE) {
                $map[$item['name']] = $item['isToggled'];
            }
        }

        return $this->tableColumnsToggleStateByName = $map;
    }
}
