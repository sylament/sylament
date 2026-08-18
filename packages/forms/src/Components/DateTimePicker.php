<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use LogicException;
use Sylament\Forms\Components\Concerns\CanBeNative;
use Sylament\Forms\Components\Concerns\CanBeReadOnly;
use Sylament\Forms\Components\Concerns\HasDatalistOptions;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Forms\Components\Concerns\HasStep;
use Sylament\Forms\Validation\ValidDate;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\DateTimeStateCast;
use Sylament\Schemas\State\RecordTarget;

use function Sylament\Support\filled;

class DateTimePicker extends Field
{
    use CanBeNative;
    use CanBeReadOnly;
    use HasDatalistOptions;
    use HasExtraInputAttributes;
    use HasStep;

    protected string|Closure|null $template = '@SylamentForms/partials/DateTimePicker/index.html.twig';

    protected string|Closure|null $format = null;

    protected bool|Closure $hasDate = true;

    protected bool|Closure $hasSeconds = true;

    protected bool|Closure $hasTime = true;

    protected DateTimeInterface|string|Closure|null $maxDate = null;

    protected DateTimeInterface|string|Closure|null $minDate = null;

    protected string|Closure|null $timezone = null;

    protected int|Closure|null $hoursStep = null;

    protected int|Closure|null $minutesStep = null;

    protected int|Closure|null $secondsStep = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->constraint(new ValidDate());
    }

    /**
     * The native `min` / `max` attributes: a control without a time part takes a
     * date alone, so a bound carrying one is truncated rather than rejected by
     * the browser.
     *
     * @return array<array-key, mixed>
     */
    public function getExtraTemplateData(): array
    {
        return [
            'minAttribute' => $this->getBoundaryAttribute($this->getMinDate()),
            'maxAttribute' => $this->getBoundaryAttribute($this->getMaxDate()),
        ];
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            new DateTimeStateCast(
                format: $this->getFormat(),
                internalFormat: $this->getInternalFormat(),
                timezone: $this->getTimezone(),
                objectClass: $this->getDateTimeObjectClass(),
            ),
        ];
    }

    public function getInternalFormat(): string
    {
        if (! $this->isNative()) {
            return 'Y-m-d H:i:s';
        }

        if (! $this->hasTime()) {
            return 'Y-m-d';
        }

        if (! $this->hasDate()) {
            return $this->hasSeconds() ? 'H:i:s' : 'H:i';
        }

        return $this->hasSeconds() ? 'Y-m-d H:i:s' : 'Y-m-d H:i';
    }

    public function format(string|Closure|null $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function maxDate(DateTimeInterface|string|Closure|null $date): static
    {
        $this->maxDate = $date;

        $this->beforeOrEqual(static fn (DateTimePicker $component): ?string => $component->getMaxDate());

        return $this;
    }

    public function minDate(DateTimeInterface|string|Closure|null $date): static
    {
        $this->minDate = $date;

        $this->afterOrEqual(static fn (DateTimePicker $component): ?string => $component->getMinDate());

        return $this;
    }

    public function hoursStep(int|Closure|null $hoursStep): static
    {
        $this->hoursStep = $hoursStep;

        return $this;
    }

    public function minutesStep(int|Closure|null $minutesStep): static
    {
        $this->minutesStep = $minutesStep;

        return $this;
    }

    public function secondsStep(int|Closure|null $secondsStep): static
    {
        $this->secondsStep = $secondsStep;

        return $this;
    }

    public function timezone(string|Closure|null $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function date(bool|Closure $condition = true): static
    {
        $this->hasDate = $condition;

        return $this;
    }

    public function seconds(bool|Closure $condition = true): static
    {
        $this->hasSeconds = $condition;

        return $this;
    }

    public function time(bool|Closure $condition = true): static
    {
        $this->hasTime = $condition;

        return $this;
    }

    public function getFormat(): string
    {
        $format = $this->evaluate($this->format);

        if ($format) {
            return $format;
        }

        $format = $this->hasDate() ? 'Y-m-d' : '';

        if (! $this->hasTime()) {
            return $format;
        }

        $format = $format ? "{$format} H:i" : 'H:i';

        if (! $this->hasSeconds()) {
            return $format;
        }

        return "{$format}:s";
    }

    public function getMaxDate(): ?string
    {
        return $this->formatBoundary($this->evaluate($this->maxDate));
    }

    public function getMinDate(): ?string
    {
        return $this->formatBoundary($this->evaluate($this->minDate));
    }

    public function getTimezone(): string
    {
        return $this->evaluate($this->timezone) ?? date_default_timezone_get();
    }

    public function hasDate(): bool
    {
        return (bool) $this->evaluate($this->hasDate);
    }

    public function hasSeconds(): bool
    {
        return (bool) $this->evaluate($this->hasSeconds);
    }

    public function hasTime(): bool
    {
        return (bool) $this->evaluate($this->hasTime);
    }

    public function getHoursStep(): int
    {
        return $this->evaluate($this->hoursStep) ?? 1;
    }

    public function getMinutesStep(): int
    {
        return $this->evaluate($this->minutesStep) ?? 1;
    }

    public function getSecondsStep(): int
    {
        return $this->evaluate($this->secondsStep) ?? 1;
    }

    public function getStep(): int|float|string|null
    {
        $step = $this->evaluate($this->step);

        if (filled($step)) {
            return $step;
        }

        if (! $this->hasTime()) {
            return null;
        }

        $secondsStep = $this->getSecondsStep();

        if ($secondsStep > 1) {
            return $secondsStep;
        }

        $minutesStep = $this->getMinutesStep();

        if ($minutesStep > 1) {
            return $minutesStep * 60;
        }

        $hoursStep = $this->getHoursStep();

        if ($hoursStep > 1) {
            return $hoursStep * 3600;
        }

        if (! $this->hasSeconds()) {
            return null;
        }

        return 1;
    }

    public function getType(): string
    {
        if (! $this->isNative()) {
            throw new LogicException(sprintf(
                'The [%s] picker is declared native(false), which needs the JS calendar this port does not ship — see docs/progress/forms/date-time-picker.md.',
                $this->getName() ?? static::class,
            ));
        }

        if (! $this->hasDate()) {
            return 'time';
        }

        if (! $this->hasTime()) {
            return 'date';
        }

        return 'datetime-local';
    }

    /**
     * The write-side type the bound record declares, when it is one this field
     * can produce. Null — no container, no bound record, a path the record does
     * not declare, or any other target type — leaves the cast writing a string in
     * {@see getFormat()}, which is what Filament's own cast always writes.
     *
     * @return class-string<DateTimeInterface>|null
     */
    protected function getDateTimeObjectClass(): ?string
    {
        if (! isset($this->container)) {
            return null;
        }

        $recordClass = $this->getRootContainer()->getRecordClass();
        $recordPath = $this->getRecordStatePath();

        if ($recordClass === null || $recordPath === null) {
            return null;
        }

        $target = $this->getRecordTypeReader()->describe($recordClass, $recordPath);

        if (! $target instanceof RecordTarget) {
            return null;
        }

        $objectClass = $target->objectClass;

        return in_array($objectClass, [DateTime::class, DateTimeImmutable::class], true) ? $objectClass : null;
    }

    private function formatBoundary(DateTimeInterface|string|null $date): ?string
    {
        return $date instanceof DateTimeInterface ? $date->format('Y-m-d H:i:s') : $date;
    }

    private function getBoundaryAttribute(?string $date): ?string
    {
        if ($date === null || $this->hasTime()) {
            return $date;
        }

        try {
            return new DateTimeImmutable($date)->format('Y-m-d');
        } catch (Exception) {
            // A bound the browser could not read either renders no attribute.
            return null;
        }
    }
}
