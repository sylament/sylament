<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

/**
 * The markup panels' templates give a test to find: the document title; the page's root, an
 * ordinary page's and a simple page's, the root a page with a sub-navigation band marks, its
 * heading, header actions, form and form actions row; the shell's sidebar navigation, a
 * navigation group by its label with its disclosure toggle, a navigation item with its label,
 * the active item, the topbar, the user menu trigger, the theme switcher, the logo, the global
 * search's live root and the main content area by its width; the sub-navigation band beside a
 * page and its active item; the simple layout; a relation manager's root; and a field by its
 * name. Each is named by its accessible name or role, the `data-` hook it carries, or the
 * `sy-pa-` class that is the template's one hook on it, and a selector a sub-navigation band
 * would also match is scoped to the landmark it reads.
 */
final class Selectors
{
    public const string DOCUMENT_TITLE = 'head > title';

    /**
     * The page's heading, which `Header` (an ordinary page) and `SimpleHeader` (a simple page) render as the one `<h1>`.
     */
    public const string PAGE_HEADING = 'h1';

    /**
     * An ordinary page's live root (`partials/Page/index.html.twig`), carrying the page's classes; its class is its one hook.
     */
    public const string PAGE = '.sy-pa-page';

    /**
     * A simple page's live root (`partials/SimplePage/index.html.twig`), carrying the page's classes; its class is its one hook.
     */
    public const string SIMPLE_PAGE = '.sy-pa-simple-page';

    /**
     * The row `Header` renders the page's header actions in, only when it has some; its class is its one hook.
     */
    public const string HEADER_ACTIONS = '.sy-pa-header-actions';

    /**
     * The `<form>` a record page's form variant (`partials/Page/form.html.twig`) wraps its form and actions row in.
     */
    public const string FORM = 'form.sy-pa-form';

    /**
     * The form actions row below the form, by the id its `form-actions` key gives it in the `formActions` schema; the row's
     * alignment or full-width class sits on its inner container.
     */
    public const string FORM_ACTIONS = '[id="formActions.form-actions"]';

    /**
     * The shell's navigation landmark in the sidebar (`Sidebar`), holding the panel's navigation groups.
     */
    public const string NAVIGATION = 'nav[aria-label="Navigation"]';

    /**
     * A navigation item in the sidebar (`SidebarItem`), never one of a sub-navigation band; its class is its one hook.
     * Scoped to the `Sidebar` landmark, so it matches nothing in a bare `SidebarItem` render.
     */
    public const string NAVIGATION_ITEM = self::NAVIGATION . ' .sy-pa-sidebar-item';

    /**
     * The label inside a navigation item (`SidebarItem`), in the sidebar or a band, read as the item's text without its badge.
     */
    public const string NAVIGATION_ITEM_LABEL = '.sy-pa-sidebar-item-label';

    /**
     * The sidebar item of the page being shown, the one link `SidebarItem` marks the current page in the sidebar.
     * Scoped to the `Sidebar` landmark, so it matches nothing in a bare `SidebarItem` render.
     */
    public const string ACTIVE_NAVIGATION_ITEM = self::NAVIGATION . ' a[aria-current="page"]';

    /**
     * The disclosure inside a navigation group (`SidebarGroup`) that collapses it, rendered only on a collapsible group.
     */
    public const string NAVIGATION_GROUP_TOGGLE = 'button.sy-pa-sidebar-group-btn[aria-expanded]';

    /**
     * The shell's topbar landmark (`Topbar`), holding the logo, the global search and the user menu.
     */
    public const string TOPBAR = 'nav[aria-label="Topbar"]';

    /**
     * The button opening the user menu (`UserMenu`), in the topbar or a simple layout's strip.
     */
    public const string USER_MENU_TRIGGER = 'button[aria-label="Open user menu"]';

    /**
     * The theme switcher's button group (`ThemeSwitcher`) inside the user menu.
     */
    public const string THEME_SWITCHER = '[role="group"][aria-label="Theme"]';

    /**
     * The brand's logo, name or image (`Logo`), in the sidebar header, the topbar or a simple page's header; its class is its one hook.
     */
    public const string LOGO = '.sy-pa-logo';

    /**
     * The global search's live root (`GlobalSearch`), which the topbar mounts only for a panel with global search on.
     */
    public const string GLOBAL_SEARCH = '[data-live-name-value="SylamentPanels:GlobalSearch"]';

    /**
     * The main content area of either layout (`Layout`, `SimpleLayout`), which carries the page's width class.
     */
    public const string MAIN = 'main';

    /**
     * The band `SubNavigationSidebar` renders beside the page at the `start` or `end` position; a `top` band is support's
     * tab strip (`SubNavigationTabs`) and the narrow-screen dropdown is `SubNavigationMobileMenu`. Its class is its one hook.
     */
    public const string SUB_NAVIGATION = '.sy-pa-sub-navigation-sidebar-ctn';

    /**
     * The band item of the page being shown, the one link `SidebarItem` marks the current page in a start or end band.
     */
    public const string ACTIVE_SUB_NAVIGATION_ITEM = self::SUB_NAVIGATION . ' a[aria-current="page"]';

    /**
     * The simple layout's root (`SimpleLayout`), which a simple page's document renders in place of the shell; its class is its one hook.
     */
    public const string SIMPLE_LAYOUT = '.sy-pa-simple-layout';

    /**
     * A relation manager's live root (`partials/RelationManager/index.html.twig`), holding its tabs, table and modals; its
     * class is its one hook.
     */
    public const string RELATION_MANAGER = '.sy-pa-relation-manager';

    public static function field(string $name): string
    {
        return "input[name=\"{$name}\"]";
    }

    /**
     * A navigation group in the sidebar (`SidebarGroup`), by the label its `data-group-label` carries; a group with no label carries none.
     * Scoped to the `Sidebar` landmark, so it matches nothing in a bare `SidebarGroup` render.
     */
    public static function navigationGroup(string $label): string
    {
        return self::NAVIGATION . " [data-group-label=\"{$label}\"]";
    }

    /**
     * The main content area at a width, by the `sy-width-` class the page's width resolves to (`7xl` for the shell's default).
     */
    public static function contentWidth(string $width): string
    {
        return self::MAIN . ".sy-width-{$width}";
    }

    /**
     * An ordinary page's root showing a sub-navigation band, or, given one, a band at that position (`start`, `end`, `top`).
     */
    public static function pageWithSubNavigation(?string $position = null): string
    {
        return $position === null ? self::PAGE . '.sy-pa-page-has-sub-navigation' : self::PAGE . ".sy-pa-page-has-sub-navigation-{$position}";
    }
}
