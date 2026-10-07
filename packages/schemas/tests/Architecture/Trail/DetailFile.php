<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture\Trail;

use UnexpectedValueException;

/**
 * A docs/progress/<package>/<name>.md file: its frontmatter, its Surface tables and its "##" headings.
 */
final readonly class DetailFile
{
    /**
     * @param list<string> $ports
     * @param list<string> $owns
     * @param list<Table> $surface
     * @param list<string> $sections
     */
    public function __construct(
        public string $path,
        public string $package,
        public string $description,
        public array $ports,
        public array $owns,
        public array $surface,
        public array $sections,
    ) {
    }

    public function status(): Status
    {
        try {
            return Trail::rollup($this->statuses(), $this->unaudited());
        } catch (UnexpectedValueException $unexpectedValueException) {
            throw new UnexpectedValueException("{$this->path} {$unexpectedValueException->getMessage()}", $unexpectedValueException->getCode(), previous: $unexpectedValueException);
        }
    }

    /**
     * @return array{inProgress: int, blocked: int, todo: int, unaudited: bool}
     */
    public function open(): array
    {
        $count = static fn (Status $status, array $statuses): int => count(array_keys($statuses, $status, true));
        $statuses = $this->statuses();

        return [
            'inProgress' => $count(Status::InProgress, $statuses),
            'blocked' => $count(Status::Blocked, $statuses),
            'todo' => $count(Status::Todo, $statuses),
            'unaudited' => $this->unaudited(),
        ];
    }

    /**
     * @return list<Status>
     */
    private function statuses(): array
    {
        $statuses = [];

        foreach ($this->surface as $table) {
            foreach ($table->rows as $row) {
                $statuses[] = $row->status;
            }
        }

        return $statuses;
    }

    private function unaudited(): bool
    {
        return array_any($this->surface, fn (Table $table): bool => $table->unaudited);
    }
}
