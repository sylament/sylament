<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use Sylament\Schemas\Components\Concerns\CanBeAside;
use Sylament\Schemas\Components\Concerns\CanBeCompact;
use Sylament\Schemas\Components\Concerns\CanBeDivided;
use Sylament\Schemas\Components\Concerns\CanBeSecondary;
use Sylament\Schemas\Components\Concerns\HasContentBefore;
use Sylament\Schemas\Components\Concerns\HasDescription;
use Sylament\Schemas\Components\Concerns\HasHeading;
use Sylament\Schemas\Components\Concerns\HasLabel;
use Sylament\Schemas\Schema;
use Sylament\Support\Component\Concerns\CanBeContained;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Twig\Markup;

class Section extends Component
{
    use CanBeAside;
    use CanBeCompact;
    use CanBeContained;
    use CanBeDivided;
    use CanBeSecondary;
    use HasContentBefore;
    use HasDescription;
    use HasHeading;
    use HasLabel;

    public const string AFTER_HEADER_SCHEMA_KEY = 'after_header';

    public const string FOOTER_SCHEMA_KEY = 'footer';

    public const string BEFORE_LABEL_SCHEMA_KEY = 'before_label';

    public const string AFTER_LABEL_SCHEMA_KEY = 'after_label';

    public const string ABOVE_CONTENT_SCHEMA_KEY = 'above_content';

    public const string BELOW_CONTENT_SCHEMA_KEY = 'below_content';

    protected string|Closure|null $template = '@SylamentSchemas/components/Section.html.twig';

    final public function __construct(string|Markup|Closure|null $heading = null)
    {
        $this->heading($heading);
    }

    public static function make(string|Markup|Closure|null $heading = null): static
    {
        $section = new static($heading);

        $section->configure();

        return $section;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->key(function (Section $component): ?string {
            $heading = $component->getHeading();

            if ($heading === null || mb_trim((string) $heading) === '') {
                return null;
            }

            return new AsciiSlugger()->slug((string) $heading)->lower()->toString() . '::section';
        }, isInheritable: false);
    }

    /**
     * @param Schema|array<Component>|Closure|null $components
     */
    public function afterHeader(Schema|array|Closure|null $components): static
    {
        $this->childComponents($components, static::AFTER_HEADER_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component>|Closure|null $components
     */
    public function footer(Schema|array|Closure|null $components): static
    {
        $this->childComponents($components, static::FOOTER_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component>|Closure|null $components
     */
    public function beforeLabel(Schema|array|Closure|null $components): static
    {
        $this->childComponents($components, static::BEFORE_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component>|Closure|null $components
     */
    public function afterLabel(Schema|array|Closure|null $components): static
    {
        $this->childComponents($components, static::AFTER_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component>|Closure|null $components
     */
    public function aboveContent(Schema|array|Closure|null $components): static
    {
        $this->childComponents($components, static::ABOVE_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component>|Closure|null $components
     */
    public function belowContent(Schema|array|Closure|null $components): static
    {
        $this->childComponents($components, static::BELOW_CONTENT_SCHEMA_KEY);

        return $this;
    }

    protected function makeChildSchema(string $key): Schema
    {
        $schema = parent::makeChildSchema($key);

        if (in_array($key, [static::AFTER_HEADER_SCHEMA_KEY, static::AFTER_LABEL_SCHEMA_KEY], true)) {
            $schema->alignEnd();
        }

        return $schema;
    }

    protected function configureChildSchema(Schema $schema, string $key): Schema
    {
        $schema = parent::configureChildSchema($schema, $key);

        if (in_array($key, [
            static::AFTER_HEADER_SCHEMA_KEY,
            static::FOOTER_SCHEMA_KEY,
            static::BEFORE_LABEL_SCHEMA_KEY,
            static::AFTER_LABEL_SCHEMA_KEY,
            static::ABOVE_CONTENT_SCHEMA_KEY,
            static::BELOW_CONTENT_SCHEMA_KEY,
        ], true)) {
            $schema
                ->inline()
                ->embeddedInParentComponent();
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraTemplateData(): array
    {
        $id = $this->getId();
        if ($id === '') {
            $id = null;
        }

        $hasHeading = mb_trim((string) $this->getHeading()) !== '';
        $hasDescription = mb_trim((string) $this->getDescription()) !== '';
        $hasLabel = mb_trim((string) $this->getLabel()) !== '';

        $labelId = $id !== null && $hasLabel && !$hasHeading ? "{$id}-label" : null;
        $headingId = $id !== null && $hasHeading ? "{$id}-heading" : null;
        $descriptionId = $id !== null && $hasDescription ? "{$id}-description" : null;
        $contentId = $id !== null ? "{$id}-content" : null;

        return [
            'id' => $id,
            'labelId' => $labelId,
            'labelledById' => $headingId ?? $labelId,
            'headingId' => $headingId,
            'descriptionId' => $descriptionId,
            'contentId' => $contentId,
            'contentSchema' => $this->getChildSchema()?->extraAttributes(['class' => 'sy-section-content']),
            'afterHeaderSchema' => $this->getChildSchema(static::AFTER_HEADER_SCHEMA_KEY),
            'footerSchema' => $this->getChildSchema(static::FOOTER_SCHEMA_KEY),
            'beforeLabelSchema' => $this->getChildSchema(static::BEFORE_LABEL_SCHEMA_KEY),
            'afterLabelSchema' => $this->getChildSchema(static::AFTER_LABEL_SCHEMA_KEY),
            'aboveContentSchema' => $this->getChildSchema(static::ABOVE_CONTENT_SCHEMA_KEY),
            'belowContentSchema' => $this->getChildSchema(static::BELOW_CONTENT_SCHEMA_KEY),
        ];
    }

    public function getHeadingsCount(): int
    {
        if (mb_trim((string) $this->getHeading()) === '') {
            return 0;
        }

        return 1;
    }
}
