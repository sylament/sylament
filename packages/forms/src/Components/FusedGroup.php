<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\Concerns\HasExtraFieldWrapperAttributes;
use Sylament\Forms\Components\Concerns\HasHelperText;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\CanBeMarkedAsRequired;
use Sylament\Schemas\Components\Concerns\HasLabel;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Size;
use Twig\Markup;

use function Sylament\Support\blank;

class FusedGroup extends Component
{
    use CanBeMarkedAsRequired;
    use HasExtraFieldWrapperAttributes;
    use HasHelperText;
    use HasLabel;

    /**
     * The one {@see Schema::fieldWrapperView()} value forms honors: an opaque token
     * switching `FieldWrapper` to its plain branch — root plus screen-reader label,
     * no chrome.
     */
    public const string PLAIN_FIELD_WRAPPER_VIEW = 'sylament-forms::plain-field-wrapper';

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

    protected string|Closure|null $template = '@SylamentForms/partials/FusedGroup/index.html.twig';

    /**
     * @param array<Component|Action|ActionGroup|string|Markup>|Closure $schema
     */
    public static function make(array|Closure $schema = []): static
    {
        $fusedGroup = static::newInstance();

        $fusedGroup->components($schema);

        $fusedGroup->configure();

        return $fusedGroup;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->gap(false);
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function aboveLabel(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::ABOVE_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function belowLabel(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BELOW_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function beforeLabel(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BEFORE_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function afterLabel(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::AFTER_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function aboveContent(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::ABOVE_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function belowContent(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BELOW_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function beforeContent(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BEFORE_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function afterContent(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::AFTER_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function aboveErrorMessage(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::ABOVE_ERROR_MESSAGE_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function belowErrorMessage(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BELOW_ERROR_MESSAGE_SCHEMA_KEY);

        return $this;
    }

    /**
     * The messages of the first path-bearing child field carrying any.
     *
     * @return list<string>
     */
    public function getErrorMessages(): array
    {
        foreach ($this->getChildSchema()?->getComponents(withActions: false) ?? [] as $childComponent) {
            if (!$childComponent instanceof Field) {
                continue;
            }

            if (blank($childComponent->getStatePath())) {
                continue;
            }

            $errorMessages = $childComponent->getErrorMessages();

            if ($errorMessages !== []) {
                return $errorMessages;
            }
        }

        return [];
    }

    public function isRequired(): bool
    {
        foreach ($this->getChildSchema()?->getComponents(withActions: false) ?? [] as $childComponent) {
            if (!$childComponent instanceof Field) {
                continue;
            }

            if ($childComponent->isMarkedAsRequired()) {
                return true;
            }
        }

        return false;
    }

    protected function makeChildSchema(string $key): Schema
    {
        $schema = parent::makeChildSchema($key);

        if ($key === static::AFTER_LABEL_SCHEMA_KEY) {
            $schema->alignEnd();
        }

        $schema->fieldWrapperView(static::PLAIN_FIELD_WRAPPER_VIEW);

        return $schema;
    }

    protected function configureChildSchema(Schema $schema, string $key): Schema
    {
        $schema = parent::configureChildSchema($schema, $key);

        if (in_array($key, [
            static::BEFORE_LABEL_SCHEMA_KEY,
            static::AFTER_LABEL_SCHEMA_KEY,
            static::ABOVE_CONTENT_SCHEMA_KEY,
            static::BELOW_CONTENT_SCHEMA_KEY,
            static::ABOVE_ERROR_MESSAGE_SCHEMA_KEY,
            static::BELOW_ERROR_MESSAGE_SCHEMA_KEY,
        ], true)) {
            $schema
                ->inline()
                ->embeddedInParentComponent()
                ->modifyActionsUsing(fn (Action $action): Action => $action
                    ->defaultSize(Size::Small)
                    ->defaultTemplate(Action::LINK_TEMPLATE))
                ->modifyActionGroupsUsing(fn (ActionGroup $actionGroup): ActionGroup => $actionGroup->defaultSize(Size::Small));
        }

        return $schema;
    }
}
