<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use LogicException;
use Stringable;
use Sylament\Support\ArrayRecord;
use Symfony\Component\PropertyAccess\Exception\AccessException;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Sylament\Support\get_record_label;
use function Sylament\Support\plural;
use function Symfony\Component\String\u;

trait InteractsWithRecord
{
    protected mixed $record = null;

    /**
     * @var class-string|Closure|null
     */
    protected string|Closure|null $recordClass = null;

    protected string|Closure|null $recordLabel = null;

    protected string|Closure|null $pluralRecordLabel = null;

    protected string|Closure|null $recordTitle = null;

    protected string|Closure|null $recordTitleAttribute = null;

    public function record(mixed $record): static
    {
        $this->record = $record;

        return $this;
    }

    /**
     * @param class-string|Closure|null $class
     */
    public function recordClass(string|Closure|null $class): static
    {
        $this->recordClass = $class;

        return $this;
    }

    public function recordLabel(string|Closure|null $label): static
    {
        $this->recordLabel = $label;

        return $this;
    }

    public function pluralRecordLabel(string|Closure|null $label): static
    {
        $this->pluralRecordLabel = $label;

        return $this;
    }

    public function recordTitle(string|Closure|null $title): static
    {
        $this->recordTitle = $title;

        return $this;
    }

    public function recordTitleAttribute(string|Closure|null $attribute): static
    {
        $this->recordTitleAttribute = $attribute;

        return $this;
    }

    public function hasRecord(): bool
    {
        return filled($this->record);
    }

    public function getRecord(): mixed
    {
        $record = $this->evaluate($this->record);

        if (is_object($record) || filled($record)) {
            return $this->ensureCorrectRecordType($record);
        }

        if (method_exists($this, 'getGroup')) {
            return $this->ensureCorrectRecordType($this->getGroup()?->getRecord());
        }

        return null;
    }

    /**
     * @return class-string|null
     */
    public function getCustomRecordClass(): ?string
    {
        return $this->evaluate($this->recordClass);
    }

    /**
     * @return class-string|null
     */
    public function getRecordClass(): ?string
    {
        $class = $this->getCustomRecordClass();

        if ($class !== null) {
            return $class;
        }

        $record = $this->getRecord();

        return is_object($record) ? $record::class : null;
    }

    public function getCustomRecordLabel(): ?string
    {
        return $this->evaluate($this->recordLabel);
    }

    public function getRecordLabel(): ?string
    {
        $label = $this->getCustomRecordLabel();

        if (filled($label)) {
            return $label;
        }

        $class = $this->getRecordClass();

        return $class === null ? null : get_record_label($class);
    }

    public function getTitleCaseRecordLabel(): ?string
    {
        $label = $this->getRecordLabel();

        return blank($label) ? null : u($label)->title(allWords: true)->toString();
    }

    public function getCustomPluralRecordLabel(): ?string
    {
        return $this->evaluate($this->pluralRecordLabel);
    }

    public function getPluralRecordLabel(): ?string
    {
        $label = $this->getCustomPluralRecordLabel();

        if (filled($label)) {
            return $label;
        }

        $singular = $this->getRecordLabel();

        return $singular === null ? null : plural($singular);
    }

    public function getTitleCasePluralRecordLabel(): ?string
    {
        $label = $this->getPluralRecordLabel();

        return blank($label) ? null : u($label)->title(allWords: true)->toString();
    }

    /**
     * The closure title, then the title attribute — both only for a record that
     * exists, Filament's own guard.
     */
    public function getCustomRecordTitle(?object $record = null): ?string
    {
        $record ??= $this->getRecordObject();

        if ($record === null) {
            return null;
        }

        $title = $this->evaluate(
            $this->recordTitle,
            namedInjections: ['record' => $record],
            typedInjections: [$record::class => $record],
        );

        if (filled($title)) {
            return $title;
        }

        $attribute = $this->getCustomRecordTitleAttribute();

        if (blank($attribute)) {
            return null;
        }

        try {
            $value = $this->getPropertyAccessor()->getFromRecord($record, $attribute);
        } catch (AccessException) {
            return null;
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (blank($value) || !is_scalar($value)) {
            return null;
        }

        return (string) $value;
    }

    public function getRecordTitle(?object $record = null): ?string
    {
        return $this->getCustomRecordTitle($record) ?? $this->getRecordLabel();
    }

    public function getCustomRecordTitleAttribute(): ?string
    {
        return $this->evaluate($this->recordTitleAttribute);
    }

    public function getRecordTitleAttribute(): ?string
    {
        return $this->getCustomRecordTitleAttribute();
    }

    public function hasCustomRecordTitle(): bool
    {
        return $this->recordTitle !== null;
    }

    public function hasCustomRecordTitleAttribute(): bool
    {
        return $this->recordTitleAttribute !== null;
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
                ?? throw new LogicException(sprintf('Record arrays must have a unique, printable [%s] entry for identification.', ArrayRecord::getKeyName()));
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

    /**
     * A record that is not an instance of the custom record class is dropped —
     * what lets a create action for one class sit on a table of another.
     */
    protected function ensureCorrectRecordType(mixed $record): mixed
    {
        $customClass = $this->getCustomRecordClass();

        if (is_object($record) && $customClass !== null && !$record instanceof $customClass) {
            return null;
        }

        return $record;
    }

    private function getRecordObject(): ?object
    {
        $record = $this->getRecord();

        return is_object($record) ? $record : null;
    }
}
