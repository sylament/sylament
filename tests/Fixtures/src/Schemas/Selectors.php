<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

/**
 * A schema's root, the root of one a live host renders as its own, which carries the schema's
 * Stimulus controller, a schema carrying none, its gap and density, and the cells its children
 * and its actions sit in, a growing one included; the roots of schemas' layout, prose, icon and
 * action-row components, named by the `sy-sc-` class that marks each, and the action row's
 * container spanning the row in place of aligning it, for an area asserting
 * what a trait puts on them; a section's content schema, the child schema a component's gap,
 * density and columns reach, which the schema every render wraps a component in would otherwise
 * answer for, and a section's content by the id its key gives it; a schema root by the key its
 * controller is given; a control bound to a state path, by the `data-model` value schemas'
 * binding modifiers write; a tab, the selected one, and its panel, by the roles and the
 * selection state the Tabs and Tab partials give them, and the targets of the tabs controller;
 * a wizard step's header button and panel, by the step key the Wizard and Step partials put on
 * both, its header entry and its cancel and submit slots, by their controller targets, and the
 * values its controller reads.
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

    public const string GROWABLE = '.sy-growable';

    public const string FIELDSET = '.sy-sc-fieldset';

    public const string FLEX = '.sy-sc-flex';

    public const string SECTION = '.sy-sc-section';

    public const string TEXT = '.sy-sc-text';

    public const string ICON = '.sy-sc-icon';

    public const string ACTIONS = '.sy-sc-actions';

    /**
     * The action row's container (`partials/Actions`) when the row spans its width in place of aligning its actions.
     */
    public const string FULL_WIDTH_ACTIONS_CONTAINER = '.sy-sc-actions-ctn.sy-width-full';

    public const string SECTION_CONTENT = '.sy-sc.sy-section-content';

    /**
     * A control bound to the absolute state path without a live modifier.
     */
    public static function boundTo(string $statePath): string
    {
        return "[data-model=\"norender|{$statePath}\"]";
    }

    public const string TABS = '.sy-sc-tabs';

    public const string TAB = '[role="tab"]';

    /**
     * The selected tab, the one a tab strip marks `aria-selected="true"`.
     */
    public const string SELECTED_TAB = self::TAB . '[aria-selected="true"]';

    public const string TAB_PANEL = '[role="tabpanel"]';

    public const string WIZARD = '.sy-sc-wizard';

    public const string WIZARD_STEP_BUTTON = 'button[data-step-key]';

    public const string WIZARD_STEP = '[role="group"][data-step-key]';

    public const string WIZARD_HEADER_STEP = '[data-sylament--schemas--wizard-target="headerStep"]';

    public const string WIZARD_CANCEL_SLOT = '[data-sylament--schemas--wizard-target="cancelSlot"]';

    public const string WIZARD_SUBMIT_SLOT = '[data-sylament--schemas--wizard-target="submitSlot"]';

    /**
     * A value the wizard controller reads, or, without one, the value's presence.
     */
    public static function wizardValue(string $name, ?string $value = null): string
    {
        return $value === null ? "[data-sylament--schemas--wizard-{$name}-value]" : "[data-sylament--schemas--wizard-{$name}-value=\"{$value}\"]";
    }

    /**
     * A target of the tabs controller (`nav`, `tab`, `panel`, `dropdown`, `dropdownItem`, `triggerTab`, `moreButton`, `badgePlaceholder`).
     */
    public static function tabsTarget(string $target): string
    {
        return "[data-sylament--schemas--tabs-target=\"{$target}\"]";
    }

    public static function sectionContentOf(string $key): string
    {
        return "[id=\"{$key}::section-content\"]";
    }

    public static function schemaKeyed(string $key): string
    {
        return "[data-sylament--schemas--schema-schema-key-value=\"{$key}\"]";
    }
}
