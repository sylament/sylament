<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use LogicException;
use Stringable;
use Sylament\Support\ArrayRecord;
use Symfony\Component\PropertyAccess\Exception\AccessException;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

trait InteractsWithRecord
{
    protected mixed $record = null;

    public function record(mixed $record): static
    {
        $this->record = $record;

        return $this;
    }

    public function hasRecord(): bool
    {
        return filled($this->record);
    }

    public function getRecord(): mixed
    {
        $record = $this->evaluate($this->record);

        if ($record !== null) {
            return $record;
        }

        if (method_exists($this, 'getGroup')) {
            return $this->getGroup()?->getRecord();
        }

        return null;
    }

    /**
     * The identity a mounted action carries back to its host — null when the
     * value is not a record, or is one carrying no readable, non-blank key.
     *
     * @throws LogicException when an array record has no key entry
     */
    public function resolveRecordKey(mixed $record): ?string
    {
        if (is_array($record)) {
            $key = $record[ArrayRecord::getKeyName()]
                ?? throw new LogicException(sprintf('Record arrays must have a unique [%s] entry for identification.', ArrayRecord::getKeyName()));
        } elseif (is_object($record)) {
            try {
                $key = $this->getPropertyAccessor()->getFromRecord($record, 'id');
            } catch (AccessException) {
                return null;
            }
        } else {
            return null;
        }

        if ($key instanceof Stringable) {
            $key = (string) $key;
        }

        if (blank($key) || !is_scalar($key)) {
            return null;
        }

        return (string) $key;
    }
}
