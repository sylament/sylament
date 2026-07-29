<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Sylament\Forms\Components\Concerns\CanBeAutofocused;
use Sylament\Forms\Components\Concerns\CanBeMarkedAsRequired;
use Sylament\Forms\Components\Concerns\CanBeValidated;
use Sylament\Forms\Components\Concerns\HasHelperText;
use Sylament\Forms\Components\Concerns\HasHint;
use Sylament\Forms\Components\Concerns\HasPlaceholder;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\HasLabel;
use Sylament\Schemas\Components\Concerns\HasName;
use Sylament\Schemas\Components\Contracts\HasValidationConstraints;
use Sylament\Schemas\Schema;
use Twig\Markup;

use function Sylament\Support\filled;
use function Symfony\Component\String\u;

abstract class Field extends Component implements HasValidationConstraints
{
    use CanBeAutofocused;
    use CanBeMarkedAsRequired;
    use CanBeValidated;
    use HasHelperText;
    use HasHint;
    use HasLabel {
        getLabel as getBaseLabel;
    }
    use HasName;
    use HasPlaceholder;

    public const string ABOVE_LABEL_SCHEMA_KEY = 'above_label';

    public const string BELOW_LABEL_SCHEMA_KEY = 'below_label';

    public const string BEFORE_LABEL_SCHEMA_KEY = 'before_label';

    public const string AFTER_LABEL_SCHEMA_KEY = 'after_label';

    public const string ABOVE_CONTENT_SCHEMA_KEY = 'above_content';

    public const string BELOW_CONTENT_SCHEMA_KEY = 'below_content';

    public const string BEFORE_CONTENT_SCHEMA_KEY = 'before_content';

    public const string AFTER_CONTENT_SCHEMA_KEY = 'after_content';

    public const string ABOVE_ERROR_MESSAGE_SCHEMA_KEY = 'above_error_message';

    public const string BELOW_ERROR_MESSAGE_SCHEMA_KEY = 'below_error_message';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpHint();
    }

    public static function make(?string $name = null): static
    {
        $field = static::newInstance();

        $field->name($name);
        $field->statePath($name);

        $field->configure();

        return $field;
    }

    public function getLabel(): string|Markup|null
    {
        if (filled($label = $this->getBaseLabel())) {
            return $label;
        }

        return $this->getDefaultLabel();
    }

    protected function getDefaultLabel(): ?string
    {
        $name = $this->getName();

        if ($name === null) {
            return null;
        }

        return (string) u($name)->afterLast('.')->kebab()->replace('-', ' ')->title();
    }

    /**
     * @return array<string>
     */
    public function getErrorMessages(): array
    {
        return [];
    }

    /**
     * @param Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null $components
     */
    public function aboveLabel(Schema|array|Component|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::ABOVE_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null $components
     */
    public function belowLabel(Schema|array|Component|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BELOW_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null $components
     */
    public function beforeLabel(Schema|array|Component|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BEFORE_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null $components
     */
    public function afterLabel(Schema|array|Component|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::AFTER_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null $components
     */
    public function aboveContent(Schema|array|Component|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::ABOVE_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null $components
     */
    public function belowContent(Schema|array|Component|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BELOW_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null $components
     */
    public function beforeContent(Schema|array|Component|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BEFORE_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null $components
     */
    public function afterContent(Schema|array|Component|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::AFTER_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null $components
     */
    public function aboveErrorMessage(Schema|array|Component|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::ABOVE_ERROR_MESSAGE_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|string|Markup>|Component|string|Markup|Closure|null $components
     */
    public function belowErrorMessage(Schema|array|Component|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BELOW_ERROR_MESSAGE_SCHEMA_KEY);

        return $this;
    }

    protected function makeChildSchema(string $key): Schema
    {
        $schema = parent::makeChildSchema($key);

        if (in_array($key, [static::AFTER_LABEL_SCHEMA_KEY, static::AFTER_CONTENT_SCHEMA_KEY], true)) {
            $schema->alignEnd();
        }

        return $schema;
    }

    protected function configureChildSchema(Schema $schema, string $key): Schema
    {
        $schema = parent::configureChildSchema($schema, $key);

        if (in_array($key, [
            static::ABOVE_LABEL_SCHEMA_KEY,
            static::BELOW_LABEL_SCHEMA_KEY,
            static::BEFORE_LABEL_SCHEMA_KEY,
            static::AFTER_LABEL_SCHEMA_KEY,
            static::ABOVE_CONTENT_SCHEMA_KEY,
            static::BELOW_CONTENT_SCHEMA_KEY,
            static::BEFORE_CONTENT_SCHEMA_KEY,
            static::AFTER_CONTENT_SCHEMA_KEY,
            static::ABOVE_ERROR_MESSAGE_SCHEMA_KEY,
            static::BELOW_ERROR_MESSAGE_SCHEMA_KEY,
        ], true)) {
            $schema
                ->inline()
                ->embeddedInParentComponent();
        }

        return $schema;
    }
}
