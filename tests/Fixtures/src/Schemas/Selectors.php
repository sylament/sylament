<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

/**
 * A schema's root, the root of one a live host renders as its own, which carries the
 * schema's Stimulus controller, a schema carrying none, its gap, density and inline
 * layout, and the cells its children and its actions sit in, a growing one included; the roots of schemas'
 * layout, prose, icon and action-row components, named by the `sy-sc-` class that marks each, for an area
 * asserting what a trait puts on them; a section's content schema, the child schema a
 * component's gap, density and columns reach, which the schema every render wraps a
 * component in would otherwise answer for; and a control bound to a state path, by the
 * `data-model` value schemas' binding modifiers write.
 */
final class Selectors
{
    public const string SCHEMA = '.sy-sc';

    public const string ROOT_SCHEMA = '.sy-sc[data-controller="sylament--schemas--schema"]';

    public const string COMPONENT_CELL = '.sy-sc-component';

    public const string ACTION_CELL = '.sy-sc-action';

    public const string UNCONTROLLED = ':not([data-controller])';

    public const string GAP = '.sy-sc-has-gap';

    public const string DENSE = '.sy-sc-dense';

    public const string INLINE = '.sy-inline';

    public const string GROWABLE = '.sy-growable';

    public const string FIELDSET = '.sy-sc-fieldset';

    public const string FLEX = '.sy-sc-flex';

    public const string SECTION = '.sy-sc-section';

    public const string TEXT = '.sy-sc-text';

    public const string ICON = '.sy-sc-icon';

    public const string ACTIONS = '.sy-sc-actions';

    public const string SECTION_CONTENT = '.sy-sc.sy-section-content';

    /**
     * A control bound to the absolute state path without a live modifier.
     */
    public static function boundTo(string $statePath): string
    {
        return "[data-model=\"norender|{$statePath}\"]";
    }

    public const string TABS = '.sy-sc-tabs';
}
