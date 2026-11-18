<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

/**
 * The markup panels' templates give a test to find: the document title, the skip link and the
 * main content area it jumps to, at a width; the page's root, an ordinary page's and a simple
 * page's, the root a page with a sub-navigation band marks at its position, its heading, its
 * header with breadcrumbs, its header actions with their container, its content area, its form
 * submitting to a live action, the form actions row and its submit button, and a field by its
 * name; the simple layout and the simple header with its subheading; the shell's sidebar and
 * its navigation landmark, a navigation group, any or by its label, with its label and
 * disclosure toggle, a navigation item with its label, and the active item; the topbar, the
 * user menu trigger, the theme switcher, and the logo with its light and dark renditions; the
 * sub-navigation band beside a page and its active item, the top band's tab strip and the
 * band's narrow-screen menu; the global search's live root, its field and controller, its
 * results panel, a category and its heading, and a result's link and heading; the
 * unsaved-changes guard; and a relation manager's root, any or by its class, with a tab of the
 * record page's bar for one, any or under its key. Each is named by its accessible name or
 * role, the `data-` hook or id it carries, or the `sy-pa-` class that is the template's one
 * hook on it, and a selector a sub-navigation band would also match is scoped to the landmark
 * it reads.
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
     * The content area of an ordinary page's chrome, holding what `content()` builds or a child template's content block;
     * its class is its one hook.
     */
    public const string PAGE_CONTENT = '.sy-pa-page-content';

    /**
     * The form actions row below the form, by the id its `form-actions` key gives it in the `formActions` schema; the row's
     * alignment or full-width class sits on its inner container.
     */
    public const string FORM_ACTIONS = '[id="formActions.form-actions"]';

    /**
     * The form actions row's submit button, which submits the record page's `<form>` rather than calling a live action.
     */
    public const string FORM_SUBMIT_BUTTON = self::FORM_ACTIONS . ' button[type="submit"]:not([data-action])';

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
     * The global search's field, by the stable id its label points at.
     */
    public const string GLOBAL_SEARCH_INPUT = '#sy-pa-global-search-input';

    /**
     * The element the `sylament--panels--global-search` controller binds to inside the global search's live root.
     */
    public const string GLOBAL_SEARCH_CONTROLLER = '[data-controller="sylament--panels--global-search"]';

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

    /**
     * The shell's sidebar (`Sidebar`), the drawer holding the logo header and the navigation landmark, by its stable landmark id.
     */
    public const string SIDEBAR = '#sy-pa-sidebar';

    /**
     * Any navigation group (`SidebarGroup`), labelled or not, in the sidebar or a band; its class is its one hook.
     * Unscoped, so compose it under `NAVIGATION` for the sidebar's groups.
     */
    public const string NAVIGATION_GROUP = '.sy-pa-sidebar-group';

    /**
     * The light rendition of a brand logo that has a dark-mode one beside it (`Logo`), which the stylesheet hides in dark mode.
     */
    public const string LOGO_LIGHT = self::LOGO . '.sy-pa-logo-light';

    /**
     * The dark-mode rendition of a brand logo (`Logo`), which the stylesheet shows only in dark mode.
     */
    public const string LOGO_DARK = self::LOGO . '.sy-pa-logo-dark';

    /**
     * The band `SubNavigationTabs` renders above the page at the `top` position, support's tab strip; its class is its one hook.
     */
    public const string SUB_NAVIGATION_TABS = '.sy-pa-sub-navigation-tabs';

    /**
     * The narrow-screen dropdown `SubNavigationMobileMenu` renders ahead of every band, listing its items; its class is its one hook.
     */
    public const string SUB_NAVIGATION_MENU = '.sy-pa-sub-navigation-dropdown';

    /**
     * The label of a labelled navigation group (`SidebarGroup`), in the sidebar or a band: a disclosure button on a collapsible
     * group, a plain element on a static one; its class is its one hook.
     */
    public const string NAVIGATION_GROUP_LABEL = '.sy-pa-sidebar-group-btn';

    /**
     * The container `Header` wraps its actions row in, rendered only when the header has actions; its class is its one hook.
     */
    public const string HEADER_ACTIONS_CONTAINER = '.sy-pa-header-actions-ctn';

    /**
     * A page header that renders breadcrumbs, by the class `Header` marks it with for the actions' offset.
     */
    public const string HEADER_WITH_BREADCRUMBS = 'header.sy-pa-header-has-breadcrumbs';

    /**
     * The header `SimpleHeader` renders above a simple page's card, holding its logo, heading and subheading; its class is its one hook.
     */
    public const string SIMPLE_HEADER = 'header.sy-pa-simple-header';

    /**
     * The subheading `SimpleHeader` renders under the simple page's heading, only when the page has one.
     */
    public const string SIMPLE_HEADER_SUBHEADING = self::SIMPLE_HEADER . ' p.sy-pa-simple-header-subheading';

    /**
     * The id of either layout's main content area, which the skip link jumps to.
     */
    public const string SKIP_LINK_TARGET = '#sy-pa-main';

    /**
     * The skip link either layout renders first, by the main content area it jumps to.
     */
    public const string SKIP_LINK = 'a[href="' . self::SKIP_LINK_TARGET . '"]';

    /**
     * The element the `sylament--panels--unsaved-changes` controller binds to inside a page's live root; its class is its one hook.
     */
    public const string UNSAVED_CHANGES_GUARD = '.sy-pa-unsaved-changes';

    /**
     * The results panel `GlobalSearch` renders only once a term is searched, holding the groups or the no-results message.
     */
    public const string GLOBAL_SEARCH_RESULTS = '.sy-pa-global-search-results-ctn';

    /**
     * One category of global search results, its heading and its results; its class is its one hook.
     */
    public const string GLOBAL_SEARCH_RESULT_GROUP = '.sy-pa-global-search-result-group';

    /**
     * The heading naming a category of global search results.
     */
    public const string GLOBAL_SEARCH_RESULT_GROUP_HEADING = '.sy-pa-global-search-result-group-header';

    /**
     * The link a global search result opens its record through, holding the result's heading and details.
     */
    public const string GLOBAL_SEARCH_RESULT_LINK = 'a.sy-pa-global-search-result-link';

    /**
     * The heading inside a global search result's link, the result's title.
     */
    public const string GLOBAL_SEARCH_RESULT_HEADING = '.sy-pa-global-search-result-heading';

    /**
     * A tab of the bar a record page shows its relation managers in, by the id its `relationManagerTabs` key gives it, never a
     * tab of a manager's own strip.
     */
    public const string RELATION_MANAGER_TAB = '[role="tab"][id^="content.relationManagerTabs."]';

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

    /**
     * A record page's `<form>` (`partials/Page/form.html.twig`) submitting, its default prevented, to the live action named.
     */
    public static function formSubmittingTo(string $method): string
    {
        return self::FORM . "[data-action~=\"submit->live#action:prevent\"][data-live-action-param=\"{$method}\"]";
    }

    /**
     * The record page's relation-manager tab registered under a key.
     */
    public static function relationManagerTab(string $key): string
    {
        return "[role=\"tab\"][id=\"content.relationManagerTabs.{$key}-tab\"]";
    }

    /**
     * A relation manager's live root by its class, through the live name `RelationManager::getComponentName()` derives from it.
     */
    public static function relationManager(string $class): string
    {
        return self::RELATION_MANAGER . '[data-live-name-value="' . str_replace('\\', ':', $class) . '"]';
    }
}
