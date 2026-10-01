<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Actions;

use RuntimeException;
use Sylament\Actions\DeleteBulkAction;

/**
 * An application's subclass of the bulk delete, overriding the by-key arm's
 * override point: it records every key list it is handed in a static the test
 * resets, then answers as the host tells it to: through the parent, with one
 * row fewer than it was handed and nothing deleted, or with the refusal an
 * audit guard throws.
 */
class RecordingDeleteBulkAction extends DeleteBulkAction
{
    public const string DELEGATES = 'delegates';

    public const string UNDERCOUNTS = 'undercounts';

    public const string REFUSES = 'refuses';

    /**
     * @var list<list<string>>
     */
    public static array $handedKeys = [];

    private string $answer = self::DELEGATES;

    public function answering(string $answer): static
    {
        $this->answer = $answer;

        return $this;
    }

    protected function deleteSelectedRecordsByKey(array $keys): int
    {
        self::$handedKeys[] = $keys;

        return match ($this->answer) {
            self::DELEGATES => parent::deleteSelectedRecordsByKey($keys),
            self::UNDERCOUNTS => count($keys) - 1,
            self::REFUSES => throw new RuntimeException('The audit guard refuses to delete these records.'),
        };
    }
}
