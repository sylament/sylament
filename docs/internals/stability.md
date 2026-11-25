# Stability

What Sylament promises the people who extend it, and why each surface sits in the tier it does.
The extenders are application teams, who override templates, style `sy-` classes, attach Stimulus controllers, override catalogue keys and subclass components, and plugin authors, who build on the same surfaces.
The lines an agent follows on the next change are in `.claude/rules/stability.md`; this file is their reasoning.
Each row names one reason or precedent, with the URL of the page that states it; the Filament churn counts quoted here come from rough scans of the v4.0.0, v4.5.0, v5.0.0 and v5.12.0 release tarballs (`https://github.com/filamentphp/filament/archive/refs/tags/<tag>.tar.gz`).

## The promise in short

1. **Promise names, never insides.**
   The identifiers an extender writes, selects on, calls or overrides by are Stable; the markup, CSS values and JS behaviour inside them may change in any minor, with an upgrade note where an extender must act.
2. **A rename of a Stable name never forces a major.**
   The new name is added beside the old, the old keeps working and warns where a channel exists, and it is removed only at the next scheduled major.
3. **A cheap alias is kept until the next major, on every tier.**
   "Kept for one minor" is no window, because a caret `composer update` skips minors (`^1.2` resolves to the newest `1.x`, https://getcomposer.org/doc/articles/versions.md).
4. **Majors are scheduled, never triggered.**
   At most one per Symfony major, as a removal release, and a Filament major's renames reach Sylament as aliases in minors.
5. **Replacing a whole file is protected by detection, not by freezing.**
   An override check reports every application override whose shipped target is gone, renamed, deprecated or changed in a way the override must follow, which is how the promise reaches a copied template without freezing its markup; until that check exists, review enforces it.
6. **A surface is Experimental until its port has converged.**
   A component's `sy-` classes, props and Contract items graduate when its trail file rolls up to done and one minor has shipped since.
7. **The promise is generated and enforced, not remembered.**
   An inventory of every string-keyed surface and of the PHP signatures, checked in CI, fails a removal or rename that has no alias and no `UPGRADE` entry; until the inventory exists, review enforces it.

### Why names and not insides

Filament promises nothing about its Twig, CSS and JS, and its upgrade guides do not list their changes: its CSS-hooks page says to lock the version if you publish views (https://filamentphp.com/docs/5.x/styling/css-hooks).
Sylament keeps that freedom for insides and drops the silence: every change to an overridable surface is announced.
Filament reached five majors in five years (https://filamentphp.com/insights/alexandersix-filament-through-the-years), while Symfony keeps to one every two years by making every minor deprecation-only (https://symfony.com/doc/current/contributing/code/releases.html).
What changes often in Filament's minors is what this promise leaves free: between v5.0.0 and v5.12.0, 71 of 135 views and 86 of 124 stylesheets changed, while no anonymous-component prop and no icon alias was removed.

## Tiers

| Tier | What a minor may do | What a patch may do | How a change is softened |
| --- | --- | --- | --- |
| **Stable** | add; rename only by adding the new name beside the old; deprecate | fix only | the old name works until the next scheduled major, with a runtime notice where a channel exists and a hand-written `UPGRADE-x.y` entry, from which the `CHANGELOG` line and the next major's removal list are generated |
| **Evolving** | change or remove, when no alias can carry the change or the change is a redesign | fix only, plus security hardening | an `UPGRADE-x.y` Before/After entry when an extender must act; any cheap alias is kept until the next major; the override check reports the change |
| **Internal** | anything | anything | nothing promised: marked `@internal`, private, sealed, behind the private CSS prefix or simply unlisted; a release-level `CHANGELOG` line where a sync touches many files |
| **Experimental** (a lifecycle label on any surface) | anything, with a `CHANGELOG` entry saying how to upgrade | fix only | graduates to its target tier in a minor, when the owning trail file rolls up to done and one minor has shipped since, or by an explicit decision recorded in a trail file that keeps a blocked row (a blocked row never rolls up to done) |

### Rules that hold across tiers

- **Strength depends on use.**
  Calling, selecting and writing a name is what Stable protects; overriding a method that is not a listed hook is protected for its name and parameter order but may meet an added optional argument (row P2); replacing a whole file or controller is protected by the override check and the backward-markup rule below, never by freezing the file.
  Magento ties strength to use in the same way (https://developer.adobe.com/commerce/php/development/versioning/dependencies).
- **A PHP class or member not marked `@internal` is covered, and any other surface not listed here is Internal.**
  PHP is covered by default because its seams are what Filament's extensibility consists of, and narrowing them to make the promise cheaper is ruled out; every other surface is Internal by default, Magento's "any change not listed below is considered a PATCH level change" (https://developer.adobe.com/commerce/php/development/versioning/code-changes) turned into a default.
- **Filament's names are reserved, ported or not.**
  Every public or protected member and closure-injection name Filament v5 has may arrive in a minor with Filament's signature, so an extender who adds one to a subclass, or names a closure parameter after one, matches Filament's signature or accepts the change.
  Symfony leaves adding a method in a subclass uncovered too (https://symfony.com/doc/current/contributing/code/bc.html); the trail's Unported tables and Surface rows are the list.
- **The backward-markup rule.**
  Within a major, a shipped Stimulus controller never requires markup that an earlier minor of that major did not render: a new value has a default, and a new target is optional and guarded with `has…Target`.
  A partial's context variables only grow within a major.
  An attribute only our markup writes (row J10) may be renamed or removed in a minor, with the old attribute read as a fallback until the next major wherever one line carries it, and an `UPGRADE` entry where it cannot.
  A retirement under row T7 suspends the rule for that component and says so in its `UPGRADE` entry.
- **Security beats the promise,** as at Symfony (https://symfony.com/doc/current/contributing/code/bc.html) and Drupal (https://www.drupal.org/about/core/policies/core-change-policies/drupal-8-and-9-backwards-compatibility-and-internal-api), flagged in the `CHANGELOG` the way Symfony UX LiveComponent flagged its signed-payload change (`vendor/symfony/ux-live-component/CHANGELOG.md`).
- **A visual-only change is not a break,** as MUI writes for "small CSS changes" (https://mui.com/versions/) and Volvo for colour and theme updates (https://designsystem.volvocars.com/web/developing/releases/).
- **During 0.x every surface is Experimental,** because Composer already reads every 0.y minor as a major for a caret consumer (https://getcomposer.org/doc/articles/versions.md); the graduation rule starts at 1.0, and every name chosen before it is chosen as the name 1.0 will promise.

## PHP

| Row | Surface | Tier | A minor may | Announced by | Reason or precedent |
| --- | --- | --- | --- | --- | --- |
| **P1** | classes and public methods under Filament's names, as called | Stable | add classes and methods; add an optional last argument to a method that is not a listed hook (P2); never remove or rename a class or method | `@deprecated since sylament/<pkg> <version>, use … instead` plus `trigger_deprecation()`; a moved class stays behind as an empty subclass under its old name; a Filament rename arrives as a delegating alias that warns | Symfony's promise (https://symfony.com/doc/current/contributing/code/bc.html); Filament itself left 28 of 30 moved query-builder classes as empty subclasses in v4.5.0 |
| **P2** | public methods that are not listed hooks, as overridden by a subclass | Stable for name and parameter order; an added optional last argument is allowed | add an optional last argument, with an `UPGRADE-x.y` entry naming the method | the `UPGRADE` entry; the subclass fails at load with PHP's compatibility error, never silently | Magento: an implementer depends on MAJOR and MINOR (https://developer.adobe.com/commerce/php/development/versioning/dependencies); why Symfony's stricter rule is not taken is [below](#why-an-optional-argument-may-be-added) |
| **P3** | named extension hooks: `setUp()`, `configureUsing()` with its documented order, the documented page hooks (`getHeaderActions()` and its kind); `NavigationItem::configureUsing()` and `NavigationGroup::configureUsing()` among them | Stable | add hooks around the lifecycle; never change a hook's signature, never add an argument to one, never reorder `configureUsing()` | as P1; a planned `final` is announced with `@final since sylament/<pkg> <version>`, which `DebugClassLoader` reports in a debug kernel | Symfony covers overriding protected methods and requires `@final since` (https://symfony.com/doc/current/contributing/code/bc.html); Filament added lifecycle hooks in v5.8.0 (https://github.com/filamentphp/filament/pull/20189), the additive change this row allows |
| **P4a** | protected methods not marked `@internal` | Stable | add; never change a signature, never remove | as P1; an overrider of a renamed protected method cannot be aliased, so a rename waits for the major and is announced in its `UPGRADE` file | Symfony covers "accessing and overriding protected methods and properties" (https://symfony.com/doc/current/contributing/code/bc.html); 13 of Filament's 1,083 protected methods changed between v5.0.0 and v5.12.0, all appended parameters, removals or one made public |
| **P4b** | protected properties an application is documented to redeclare: a resource's and a page's statics (`$recordClass`, `$navigationIcon`, `$slug`, …) and `ViewComponent`'s `$template` | Stable, type included | add; never rename, never change a type | a rename or a type change waits for the major | PHP property types are invariant, so a subclass that redeclares one fatals when the parent's type changes, and every application resource redeclares them |
| **P4c** | every other protected property not marked `@internal` | Stable name; Evolving type | widen the type, with an `UPGRADE-x.y` entry | the `UPGRADE` entry | an application reaches these through their setters, and Filament widened 30 of its 2,365 protected property types between v5.0.0 and v5.12.0 (`string` to `Closure\|string`, an added `Htmlable`) |
| **P5** | trait names, and which trait declares a method | Internal, except the host traits, which are Stable | move, split, merge or delete a non-host trait; the methods it contributes stay promised through the class that uses it (P1) | a release-level `CHANGELOG` line; a renamed host trait stays behind as `trait Old { use New; }` with `@deprecated` | Filament aliased moved classes and not moved traits in v4.5.0, and deleted `CanBeConcealed` at v5.8.0; Symfony covers using its traits (https://symfony.com/doc/current/contributing/code/bc.html), but Sylament mirrors about 300 traits that upstream moves every few minors |
| **P6** | return types of public and protected methods | Stable against narrowing; Evolving for widening | widen, when the added type is returned only if the application configures it (`string` to `string\|Markup`), with an `UPGRADE-x.y` entry; never narrow, never add a native return type to a non-final method | widening: the `UPGRADE` entry; narrowing waits for the major; a native type where there was none starts as an `@return` docblock, which `DebugClassLoader` reports to a child class | Symfony allows a return-type change only to a child type on final methods (https://symfony.com/doc/current/contributing/code/bc.html); a widening never breaks an overrider and breaks a caller only once it configures the new arm, and Filament widened 26 return types in twelve minors |
| **P7** | interfaces a shipped trait or base class implements: the host contracts `InteractsWithTable`, `InteractsWithActions` and `InteractsWithSchemas`, and the component capability contracts under `Components/Contracts/` | Stable for existing methods | add a method that the shipped trait or base class implements | an `UPGRADE-x.y` entry for anyone implementing the contract by hand | Magento: "New method added" to an `@api` interface is MINOR (https://developer.adobe.com/commerce/php/development/versioning/code-changes); Filament's `HasTable` gained `isTableRecordSelectableUsingQuery()` in v5.12.0 |
| **P8** | interfaces an application implements by hand: the enum contracts `HasLabel`, `HasColor`, `HasIcon` and `HasDescription`, `StateCast`, `GlobalSearchProvider`, and the colour-policy `Sylament\Support\Colors\Contracts\HasColor` | Stable | never add a method; add a new interface beside it | the new interface, with the old one deprecated | Symfony forbids adding a method to an interface (https://symfony.com/doc/current/contributing/code/bc.html), because every implementer fails to load |
| **P9** | closure-injection parameter names, as (name, evaluation context) pairs: `$record` in the `column` context, `$get` in `component`, … | Stable | add a pair, with a `CHANGELOG` line naming the name and the context; never rename or remove a pair | the `Invoker` resolves the old name and calls `trigger_deprecation()` naming the new; an added pair is announced because a closure such as `fn ($state, $locale = 'en')` silently starts receiving it | Symfony covers parameter names only by explicit tags (https://symfony.com/doc/current/contributing/code/bc.html), but here the name is the API; Filament's v4 Rector renamed `Schema`-typed parameters to `$schema` (https://raw.githubusercontent.com/filamentphp/filament/4.x/packages/upgrade/src/Rector/RenameSchemaParamToMatchTypeRector.php) |
| **P10** | parameter names and order of public and protected methods | Stable: the order of every parameter, append-only, and the names of optional parameters; Evolving: the names of required parameters | rename a required parameter, with an `UPGRADE` entry; rename an optional one only by adding the new parameter last and deprecating the old | the `UPGRADE` entry; a reorder is never allowed | Symfony's `@stable-parameter-names` and `@optional-arguments-by-name` (https://symfony.com/doc/current/contributing/code/bc.html); `CLAUDE.md` rule 6 names optional arguments while Filament code passes them positionally (`->required(false)`), so both are held |
| **P11** | template-facing getters a shipped partial calls (`component.getX`) | as P1 when public and not `@internal`, else Internal | as P1 | as P1; every getter a shipped template calls is public and not `@internal` | templates read PHP, so the PHP promise is what an override can call |
| **P12** | service constructors, `@internal` classes and members, private members, sealed classes | Internal, save that a class an application extends never gains a constructor | anything | none | Drupal treats service constructors as internal (https://www.drupal.org/node/2562903); Symfony exempts `@internal` (https://symfony.com/doc/current/contributing/code/bc.html) |
| **P13** | Sylament's own classes with no Filament counterpart (`CurrentPanel`, `AssociationReader`, `RecordKey`, `ColorManager`, …) | as P1, unless marked `@internal` | as P1 | as P1 | Symfony's rule for its own classes: public unless `@internal` (https://symfony.com/doc/current/contributing/code/bc.html); a machinery class, such as a compiler pass, a subscriber, a resolver or a Twig runtime class, takes `@internal` when it is added, which is what keeps this row narrow |

### Host traits

The host traits of row P5 are every trait Filament's documentation shows an application `use`-ing in its own class: `HasJsRenderer`, `HasFiltersForm` and `InteractsWithPageFilters` are Filament's examples.
Sylament's host traits are tables' `HasTable`, actions' `HasActions` and schemas' `HasSchemas`, which a host class uses beside implementing `InteractsWithTable`, `InteractsWithActions` or `InteractsWithSchemas`.
A host trait is promised because the application writes its name; any other trait is a way of filing methods, and the methods stay promised through the class that composes them.

### The listed hooks

Row P3 names the hooks by family.
A seam is what Filament's documentation or a trail entry shows an application overriding, and the complete list is the set of such members.
A hook never gains an argument in a minor: the new data arrives through a new hook, or through a new getter on the object the hook already receives.

### A class an application extends never gains a constructor

`Component` and its subclasses, `Page`, `Resource` and `PanelProvider`, and `Plugin` and `RenderHookProvider` once they ship, declare no constructor, and none of today's declares one.
Constructor injection in the application's own subclass is the documented route for its services, so a parent constructor added in a minor breaks every subclass that declares one:

```php
// application, written against 1.2
final class PostResource extends Resource
{
    public function __construct(private readonly SluggerInterface $slugger) {}
}
// 1.3 adds Resource::__construct(PanelRegistry $panels): PostResource never calls it,
// so Resource's $panels stays uninitialised and the first read throws an Error
```

A service therefore reaches these classes through a `#[Required]` setter, which `.claude/rules/php.md` already requires of components.

### Why an optional argument may be added

Symfony forbids adding an argument with a default to a non-final method, because a subclass that declares fewer parameters fails to load (https://symfony.com/doc/current/contributing/code/bc.html, footnotes 7 and 8), and its workaround reads the argument with `func_get_arg()`, which hides it from PHPStan.
A new method beside the old (`getRawLabel()` beside `getLabel()`) would diverge from Filament's name whenever Filament added the argument.
So row P2 lets a minor add the argument with an `UPGRADE` entry, and an overrider gets a load-time fatal it fixes by copying the signature: loud, immediate and one line, the opposite of the silent breaks this promise exists to stop.

## Configuration and routing

| Row | Surface | Tier | A minor may | Announced by | Reason or precedent |
| --- | --- | --- | --- | --- | --- |
| **C1** | bundle config keys under `sylament:` and `sylament_forms:`, and every `sylament_<pkg>:` a package adds | Stable | add keys and accepted values; never rename, remove or narrow | `NodeDefinition::setDeprecated()` on the old key, mapped onto the new | Drupal: configuration "must remain compatible" (https://www.drupal.org/node/2562903) |
| **C2** | DI tag names an application's classes carry, by autoconfiguration or by hand: `sylament.component`, `sylament.panels.page`, `sylament.panels.resource`, `sylament.panels.relation_manager`, `sylament.panels.panel_provider`, and `sylament.panels.global_search_provider` for a global search provider (whose interface is row P8) | Stable | add tags; never rename | the collecting pass reads both tags and calls `trigger_deprecation()` for the old one | Sylius: service names cannot change (https://docs.sylius.com/public/open-source/release-cycle/backwards-compatibility-promise) |
| **C3** | service ids documented as decoration or replacement seams, the colour-policy ids included ([color-policies.md](../progress/support/color-policies.md#a-policy-is-a-public-container-service-resolved-by-class-string)) | Stable | add | `Alias::setDeprecated()` or `Definition::setDeprecated()` on the old id | as C2 |
| **C4** | every other service id | Internal | anything | none | Drupal keeps subscriber service names private (https://www.drupal.org/node/2562903), and most of Sylament's services are machinery |
| **C5** | route names and the per-panel naming scheme (`sylament.<panel>.pages.<slug>`) | Stable | add routes and optional parameters; never rename | a deprecated route alias (`Symfony\Component\Routing\Alias::setDeprecated()`) | Sylius: route names cannot change (https://docs.sylius.com/public/open-source/release-cycle/backwards-compatibility-promise) |
| **C6** | URL query parameter names and their value encoding: `page`, `sort`, `search`, `filters`, `grouping`, `tab`, and the per-table prefix (`ListRecords`) | Stable | add parameters; never rename or re-encode | the old name is read as a fallback and the URL rewritten to the new | bookmarks and e-mailed links carry these, the stored-reference reason Magento treats a renamed configuration path as MAJOR for (https://developer.adobe.com/commerce/php/development/versioning/code-changes) |

## Twig

| Row | Surface | Tier | A minor may | Announced by | Reason or precedent |
| --- | --- | --- | --- | --- | --- |
| **T1** | `<twig:SylamentX:Name>` tag names | Stable once the component graduates | add components; never rename or remove | the old tag stays as an anonymous wrapper that renders the new one under a top-level `{% deprecated %}` | Sylius: templates cannot be deleted or renamed (https://docs.sylius.com/public/open-source/release-cycle/backwards-compatibility-promise) |
| **T2** | a tag's props and slots: every name in its `{% props %}`, and every block it renders | Stable once the component graduates | add props with defaults, and slots; never rename or remove | a prop keeps its old name with a `null` sentinel and a conditional `{% deprecated %}`; a slot keeps its old block, read with `block()` and rendered inside the new one | Shoelace treats slots, parts, events and properties as API (https://shoelace.style/getting-started/customizing); no Filament anonymous-component prop was removed in twelve minors |
| **T3** | prop defaults and the markup a tag renders | Evolving | change | a `CHANGELOG` entry; a Before/After entry when a `sy-` role or a slot's position moves | Drupal: "We will change the markup and CSS that core generates" (https://www.drupal.org/about/core/policies/core-change-policies/frontend-bc-policy) |
| **T4** | every `{% block %}` name in a shipped template | Stable | add blocks that do not change variable scope; move a block only within its parent; never rename or remove | the old block nests inside the new one; no runtime notice is possible, so the override check reports overrides of the old name | Shopware: "Create a new surrounding block with the new name and deprecate the old one", moves "Only within the same scope/parent block" (https://developer.shopware.com/docs/v6.6/resources/guidelines/code/backward-compatibility.html) |
| **T5** | the context variable names a shipped template receives: in a partial `component` and every `getExtraTemplateData()` key, in a page block the page or component object and the variables a trail entry lists | Stable names; Evolving values | add variables; never remove or rename one while the template is rendered | the old name keeps being passed with the same value until the next major; a retirement goes through T7 | Shopware: removing a Twig variable is not allowed, and a rename is "a new variable in the same scope" (https://developer.shopware.com/docs/v6.6/resources/guidelines/code/backward-compatibility.html); a missing variable throws in development and renders empty in production |
| **T6** | markup between and inside blocks | Evolving | change | a `CHANGELOG` entry; the override check | Shopware allows moving HTML "Only within the same TWIG Block" (https://developer.shopware.com/docs/v6.6/resources/guidelines/code/backward-compatibility.html) |
| **T7** | partial template paths (`templates/bundles/Sylament*Bundle/partials/…` in an application) | Stable against rename; Evolving against retirement | never rename or move; split by keeping the old path as the entry point that includes the new pieces; retire a path when its component stops rendering through it | an `UPGRADE` entry; the override check reports every orphaned override | Shopware: moving a template file is not allowed (https://developer.shopware.com/docs/resources/guidelines/code/core/backward-compatibility.html); retirement stays possible because Filament deleted the counterparts of about 20 of Sylament's partials within 5.x by building their HTML in PHP |
| **T8** | the contents of a partial | Evolving | change | a `CHANGELOG` entry; the backward-markup rule; the override check | WooCommerce bumps a template's `@version` and lists changes (https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/templates/cart/cart-empty.php); Filament: publishing a view asserts "the code inside that view may never change again" (https://filamentphp.com/content/danharrin-publishing-views-in-laravel) |
| **T9** | per-instance templates set with `ViewComponent::template('…')` | Evolving: the template is the extender's, and what is promised is T5's context and P11's getters | as T8 | the override check finds such a copy by an origin stamp | Drupal's Starterkit records the origin version of a copied theme (https://www.drupal.org/node/3309392) |
| **T10** | Twig functions an application calls, under the names 1.0 carries: `sylament_styles()`, `sy_attrs()`, `sy_classes()`, `sy_styles()`, `sy_render_schema()` and `sy_render_table()` | Stable | add optional arguments; never rename or remove | `DeprecatedCallableInfo` on the old registration, which fires at compile time once per use site with file and line, so it reaches copied overrides | Twig's own deprecations use it (`vendor/twig/twig/src/Extension/CoreExtension.php`) |
| **T11** | every other global Twig function, filter and test, under the names 1.0 carries: `sy_render_action()`, `sy_render_component()`, `sy_markup()`, `sy_grid()`, `sy_grid_column()`, `sy_color_attributes()`, `sy_generate_icon_html()`, the `sy_plain_text` filter and the `sy_filled` and `sy_markup` tests | Stable names; signatures add optional arguments only | as T10 | as T10, kept until the next major | every copied partial calls them and an alias is one registration; a Twig function cannot be subclassed, so an added optional argument is safe |
| **T12** | macros in our macro files | Internal unless a trail entry documents one | anything | a `{% deprecated %}` inside a documented macro's body fires on call | none found |
| **T13** | an application's overrides of an anonymous component (`templates/components/Sylament*/`) | Evolving: the override is the extender's, and what is promised is T2 | add props with defaults | the override check reports props the shipped component gained that the override does not declare, since a stale override leaks them as HTML attributes | Drupal's single-directory components replace a component together with its props schema (https://www.drupal.org/node/3352951) |

### The global names 1.0 carries

Support's global Twig functions are unprefixed today (`attrs`, `classes`, `renderSchema`, …), as are the `plain_text` filter and the `filled` and `markup` tests, so a host application defining its own `attrs()` collides with one.
1.0 carries every one of them under the `sy_` prefix in snake case, `sylamentStyles()` as `sylament_styles()`, and the render-hook functions as `sy_render_hook()` and `sy_has_render_hook()`.
A new global function, filter or test takes a `sy_<snake_case>` name from the start, so the set that has to move does not grow.
After 1.0 each is a Stable name (rows T10 and T11), because every copied partial calls several, and an alias costs one registration.

## CSS

| Row | Surface | Tier | A minor may | Announced by | Reason or precedent |
| --- | --- | --- | --- | --- | --- |
| **S1** | `sy-` hook class names of a graduated component, on the element playing that role, the per-page and per-resource scope classes among them | Stable | add classes; rename only by emitting both names until the next major | both classes in the shipped markup and both selectors in the shipped stylesheet, so a copied partial carrying the old class keeps its styling; a generated find-and-replace table in `UPGRADE` | Web Awesome: renamed classes "continue to work as aliases" (https://webawesome.com/docs/resources/changelog); Shopware: renaming a CSS selector is not allowed in a minor (https://developer.shopware.com/docs/resources/guidelines/code/core/backward-compatibility.html) |
| **S2** | `sy-` classes of a component whose trail file is not done | Experimental | anything, with a `CHANGELOG` entry | graduates with its component | Symfony: experimental code is outside the promise, and its `CHANGELOG` "must explain any backward incompatible change and how to upgrade" (https://symfony.com/doc/current/contributing/code/experimental.html); Filament added 139 `fi-` names in twelve minors |
| **S3** | element type, nesting, combinators and sibling order around a `sy-` class; a role a redesign removes | Evolving | change; retire a role with its class | an `UPGRADE` entry when a role retires; the retired class's rules stay in the stylesheet until the next major wherever they still match the old markup | Primer guarantees only that a hook exists on the most relevant element (https://github.com/primer/react/blob/main/contributor-docs/adrs/adr-023-stable-selectors-api.md) |
| **S4** | the bare `sy` scope marker, and the dark-mode contract (`.dark` on `<html>`, `:root.dark { color-scheme: dark }`) | Stable | nothing | n/a | a plugin's standalone markup without `sy` gets no scoped preflight, and an application's dark styles key on `.dark` ([styling.md](styling.md#why-is-dark-mode-class-based)) |
| **S5** | structural and visual CSS: layout, `display`, `position`, `z-index`, colours, spacing, radius, shadows, typography | Internal | anything | one release-level `CHANGELOG` line per minor that syncs CSS with Filament, with the list of changed stylesheets; an `UPGRADE` entry only when a role changes (S3) | MUI: "small CSS changes" are not breaking (https://mui.com/versions/); Filament changed 86 of 124 stylesheets in twelve minors, and an application's unlayered CSS still beats ours (S9) |
| **S6** | the `--sy-<palette>-<shade>` palette tokens, and a short list of layout and font tokens, today `--sy-font` and `--sy-sidebar-width` | Stable | add tokens; rename only with an alias | the alias by double emission or by a default read from the old name; a generated `UPGRADE` table | Spectrum deprecates a token for at least two minors and records its replacement (https://opensource.adobe.com/spectrum-design-data/spec/evolution) |
| **S7** | the colour contract: the eight policy slot keys, the `sy-color` and `sy-color-<name>` marker classes, the `--sy-<slot>` variables and the per-instance `--sy-color-<shade>` palette | Stable | add slots or shades; never rename | `ColorManager` emits both names on a rename | replacing a colour policy is an application-level extension point ([color-policies.md](../progress/support/color-policies.md#a-policy-is-a-public-container-service-resolved-by-class-string)), and component CSS reads the slots (`text-(--sy-text)`); no outside precedent found |
| **S8** | component-local knobs, the Filament-verbatim grid set (`--cols-*`), and Tailwind's own variables | Internal | anything | none | Primer: component custom properties are not public unless listed (https://github.com/primer/react/blob/main/contributor-docs/versioning.md) |
| **S9** | cascade layer names and order, the plugin layer, and the rule that unlayered application CSS beats ours without `!important` | Stable | nothing, since a layer cannot be aliased | n/a | EasyAdmin documents its layers as its precedence contract (https://symfony.com/bundles/EasyAdminBundle/5.x/design.html); no studied project promises layer names |
| **S10** | colour palette names (`primary`, `gray`, `danger`, `warning`, `success`, `info`) and shade steps | Stable | add palettes | the registry resolves an old name with a notice | the names are Filament's, kept by rule 1 of `CLAUDE.md`; no outside precedent found |
| **S11** | the stylesheet entry paths: the precompiled `@sylament/support/styles/index.css`, and the source entry a custom theme imports | Stable | add entries; never rename or move one | the old path stays as an entry that imports the new | an application writes these paths ([below](#stylesheet-entries)); Filament's `make:filament-theme` is the precedent for a theme the application's Tailwind compiles |

### Tokens and knobs

Row S6 names today's public layout and font tokens, `--sy-font` and `--sy-sidebar-width`; the token set fixed before the first tag completes the list, and decides which `@theme` variables a custom theme may override (S11).
A component-local knob is Internal whatever its prefix.
Today's seven are written `--sy-dropdown-width`, `--sy-dropdown-max-height`, `--sy-dropdown-overflow`, `--sy-off-text`, `--sy-off-bg`, `--sy-off-dark-bg` and `--sy-line-clamp`, and 1.0 carries them behind the private `--_sy-` prefix, so that only the promised set reads `--sy-`.
A new knob takes `--_sy-<name>` from the start, and a token applications set or read takes `--sy-<name>`.
The stylesheet emits Tailwind's own variables on `:root` today (`--spacing`, `--radius-*`); they are Internal, and 1.0 stops emitting them rather than giving them a private prefix.

### Layers and the `!important`s that stay

Support's `index.css` declares `theme, base, components, utilities` today, the same names a host's Tailwind declares.
A layer cannot be aliased, because renaming or reordering one changes precedence silently, so its names are fixed before the first tag and then frozen.
1.0 keeps Tailwind's outer order and nests all of Sylament inside `components`, as `components.sylament.base`, `components.sylament.components`, `components.sylament.utilities` and `components.sylament.plugins`.
In a browser a host's utilities then win in either load order, a plugin beats Sylament and a host's components beat a plugin; a top-level `sylament.*` layer was rejected, because a host utility on our element loses to it when our sheet loads last.

An application overrides Sylament with unlayered CSS and no `!important`, which a layered `!important` defeats: a layered important declaration beats every unlayered normal one.
So in 1.0 a component stylesheet carries no `!important` and no Tailwind `!` modifier, save where it must beat an inline style a bundled library writes, and the ones 1.0 ships are these, each of which an application can override only with an `!important` of its own:

- the FilePond drop label's `opacity` in forms' `file-upload.css`, which must beat the inline style FilePond's JavaScript writes;
- `base.css`'s `.sy-cloak`, whose `display: none` must beat any component's `display` until a controller reveals the element;
- `base.css`'s reduced-motion overrides, which must beat every component-layer transition without a guard on each rule;
- the scoped preflight's `[hidden]`, copied from Tailwind's preflight so a `display` rule never reveals a hidden element;
- the scoped FilePond and Cropper.js vendor stylesheets, which carry their vendors' own.

The other hand-written `!` modifiers that `file-upload.css` and `repeater.css` carry on `sy-` hooks today are not part of what 1.0 ships.

### Stylesheet entries

The precompiled stylesheet is the default: support's `core` controller entry autoimports `@sylament/support/styles/index.css`, and an application turns that off in its `assets/controllers.json` to load a stylesheet of its own.
The source entry is for a custom theme compiled by the application's Tailwind, as `make:filament-theme` builds one for Filament.
Every package export-ignores `/assets/src` today, so the source entry is part of what 1.0 ships rather than of what an install holds now.

## JavaScript and the live protocol

| Row | Surface | Tier | A minor may | Announced by | Reason or precedent |
| --- | --- | --- | --- | --- | --- |
| **J1** | controller identifiers (`sylament--forms--select`) | Stable | add controllers; never rename | the old identifier registered as a class extending the new one, which warns in debug | Symfony UX LiveComponent protects "documented features and exports from the main JavaScript file" (https://symfony.com/bundles/ux-live-component/current/index.html) |
| **J2** | values listed in a controller's Contract | Stable once the component graduates | add values with defaults; never rename, retype or remove | the old `data-*-value` read in `connect()`, copied to the new value, warned once in debug | Shoelace and Web Awesome treat attributes as API (https://shoelace.style/getting-started/customizing, https://webawesome.com/docs/resources/changelog) |
| **J3** | targets and markers an application writes, listed in a Contract (the sortable item and handle markers) | Stable once the component graduates | never rename | guarded with `has…Target`, the old marker read as a fallback | the Contract in [sortable.md](../progress/support/sortable.md#contract) |
| **J4** | actions listed in a Contract (`identifier#method`) | Stable once the component graduates | add | the old method kept as a one-line alias that warns; Stimulus throws only when such an action fires | as J1 |
| **J5** | dispatched events listed in a Contract: the name and the `detail` keys | Stable once the component graduates | add events and `detail` keys; never rename or remove | the new name dispatched, then the old with the same `detail`, until the next major | Filament renamed `notificationClosed` to `notificationsClosed` in a 5.x minor and kept listening to the old name |
| **J6** | per-controller lifecycle events `<identifier>:pre-connect` (the options about to be used, in `detail`) and `<identifier>:connect` (the instance), the promised path for extending a controller | Stable for each controller that ships them, added as its Contract is written | add | as J5 | Symfony UX Chart.js dispatches `chartjs:pre-connect` and `chartjs:connect` (https://symfony.com/bundles/ux-chartjs/current/index.html); Stimulus: controllers that need to communicate "should use events" (https://stimulus.hotwired.dev/reference/controllers) |
| **J7** | window events an application dispatches to us, listed in a Contract | Stable once the component graduates | add | both names listened to, the old warned in debug | as J5 |
| **J8** | the declarative action protocol `data-sylament-action-*`, written by actions' `Button`, `IconButton`, `Badge`, `Link` and `Grouped` partials and read by the `dispatch` and `notification` controllers | Stable | add attributes; never rename | the old attribute read as a fallback, warned in debug | copied partials and an application's own markup both carry it |
| **J9** | every `#[LiveAction]` name, and the LiveProp wire names shipped partials reference | Stable | add; never rename | the old action kept as a delegating `#[LiveAction]` that calls `trigger_deprecation()` | application markup calls them as Filament's custom views call `mountAction()`, and an alias is one delegating method |
| **J10** | targets, values and actions only our templates write | Internal names, under the backward-markup rule | add, guarded by a default or `has…Target`; rename or remove, reading the old attribute as a fallback until the next major wherever one line carries it | a change to a `data-*-target`, `data-*-value`, `data-action` or `data-model` attribute of a shipped partial is reported by the override check | Symfony UX does not protect exports from internal files (https://symfony.com/bundles/ux-live-component/current/index.html) |
| **J11** | outlets, private members, `dist/` import paths, eager load order, subclassing a shipped controller | Internal | anything | none; J6 is the promised alternative to subclassing | Angular excludes deep imports and treats classes as final unless stated (https://github.com/angular/angular/blob/main/contributing-docs/public-api-surface.md) |
| **J12** | persisted client state (`sylament.theme` in `localStorage`) | Internal, migrated on read | rename only with a reader for the old key | migrate on read | none found |

A controller's public values, targets, actions and events are its trail file's `## Contract` section, and a member not listed there is Internal; today [sortable.md](../progress/support/sortable.md#contract) and [copyable.md](../progress/support/copyable.md#contract) carry one, and no per-member JSDoc tag repeats it.
How long a controller honours older markup is the whole major for additions, since an addition guarded by a default or `has…Target` stays guarded for free, and a one-line fallback until the next major for renames of attributes only our markup writes.

## Translations and icons

| Row | Surface | Tier | A minor may | Announced by | Reason or precedent |
| --- | --- | --- | --- | --- | --- |
| **L1** | catalogue keys an application overrides or translates | Evolving, for Filament-driven changes only | rename, move or retire a key when Filament did, a move into a new domain included; a Sylament-own re-homing waits for the next major | a generated `UPGRADE` table of renamed, removed and added keys per domain; the profiler's Translation panel marks a message served from the fallback locale | Contao lets keys be added or removed in any minor (http://docs.contao.org/5.x/dev/internals/bc-promise/); every key derives from Filament's key path, so Filament's renames win, and the table matters because a renamed or added key shows English in another locale until the application follows |
| **L2** | ICU argument names inside a message (`{label}`, `{count}`) | Evolving | add arguments; rename or drop one only with an `UPGRADE` entry | the `UPGRADE` entry | as L1 |
| **L3** | message text, our English | Internal | anything | none | Symfony keeps its security and validation translations internal (https://symfony.com/doc/current/contributing/code/bc.html) |
| **L4** | domain names (`sylament`, `sylament-panels`, …) | Stable | add domains; never rename one; keys may move into a new domain under L1 | n/a | the domains are Filament's namespaces renamed by one rule, so an application's override files are named after them |
| **L5** | icon aliases, the shell's (`PanelsIconAlias`) among them | Stable | add aliases; never rename | the alias registry resolves the old alias and warns | no Filament icon alias was removed in twelve 5.x minors and 74 were added, while v4.5.0 renamed six with no alias |
| **L6** | literal icon names in shipped templates | Internal | anything | none; an application that wants control uses an alias | none found |

## Events and hooks

| Row | Surface | Tier | A minor may | Announced by | Reason or precedent |
| --- | --- | --- | --- | --- | --- |
| **E1** | EventDispatcher events `ActionCalling` and `ActionCalled`: class names, getters and order (`ActionCalling` before the action's closure runs, `ActionCalled` after) | Stable; the exact call site inside the action lifecycle is Internal | add getters and events | the PHP channel on the event class | Symfony: event-name constants cannot change value (https://symfony.com/doc/current/contributing/code/bc.html) |
| **E2** | the UX TwigComponent events we listen to, and our subscribers' behaviour | Internal | anything | none | Symfony UX changed these with `[BC BREAK]` notes outside a major (`vendor/symfony/ux-twig-component/CHANGELOG.md`) |
| **E3** | render-hook names, once render hooks ship | Experimental until graduated, then Stable | while Experimental, anything with a `CHANGELOG` entry; once Stable, add hooks and data keys only | a renamed hook is caught at registration: registering the old name calls `trigger_deprecation()` naming the new one, and the hook is stored under the new name | WordPress deprecates a hook through the call that fires it (https://developer.wordpress.org/reference/functions/apply_filters_deprecated/); here the one registration entry point replaces dual firing at every render site |
| **E4** | the position a render hook renders at, and its priority order | Evolving | move within the same region | a `CHANGELOG` entry | Sylius 2.1 moved hook content and changed priorities with "please review your priority configuration" (https://github.com/Sylius/Sylius/blob/2.1/UPGRADE-2.1.md) |

Render hooks are not built yet; as 1.0 promises them, they register through one registry (`ViewManager`) and render through `sy_render_hook()` and `sy_has_render_hook()`, and since a hook name is the string an application registers against, the rename channel sits at registration.

## Testing API

| Row | Surface | Tier | A minor may | Announced by | Reason or precedent |
| --- | --- | --- | --- | --- | --- |
| **X1** | `src/Testing` helpers and traits | Experimental during 0.x; Evolving after 1.0; Stable per helper once its trail row is done and it has gone one minor unchanged | while Evolving, change with an `UPGRADE` entry | the `UPGRADE` entry; no `@experimental` docblock during 0.x, because every surface is Experimental then | a helper is ported only when a test first uses it (`.claude/rules/testing-api.md`), so the API is incomplete by design at 1.0; Symfony UX tags single test methods `@experimental` inside a stable class (`setRouteLocale()` in `vendor/symfony/ux-live-component/src/Test/TestLiveComponent.php`) |
| **X2** | selectors into rendered markup | not shipped | n/a | n/a | shipping one would freeze Sylament's markup as public API (`.claude/rules/testing-api.md`) |

## Finding the surface

A promise nobody can list is not usable, so each family names the command that lists it on an installed application:

- `debug:twig` lists every global function, filter and test (rows T10 and T11), and the loader paths an override must sit under (T7);
- `debug:twig-component` lists every `<twig:>` component with its template, and for one name its props (T1, T2);
- `debug:config sylament` and `config:dump-reference sylament` (and `sylament_forms`) show the config tree and its defaults (C1);
- `debug:container --tag=sylament.component` and the other tags list what each tag collects (C2), and `debug:container --deprecations` prints the deprecations raised while the container compiled;
- `debug:router` lists the route names (C5);
- `debug:asset-map` lists each package's `@sylament/<pkg>` path, so the stylesheet entries and controller files (S11, J1) are visible.

A block (T4), a context variable (T5), a `sy-` class (S1) and a Contract item (J2–J5) are read from the shipped template or the trail file.

## Mechanics

One artefact per kind of surface, each the recommended form rather than a menu.
Each runtime channel is built, with a test, in the change that first renames something on its surface; the row is Stable from 1.0 regardless, because no rename can land without its channel.
The PHP channel needs no further artefact: `.claude/rules/stability.md` says how to write a deprecation, and the suite reports a silenced deprecation (`ignoreSuppressionOfDeprecations` on `phpunit.dist.xml`'s `<source>`), so a test pins one with `expectUserDeprecationMessage()`; nothing calls it yet.

### A PHP method

```php
/**
 * @deprecated since sylament/tables 1.3, use newName() instead
 */
public function oldName(array|Closure $value): static
{
    trigger_deprecation('sylament/tables', '1.3', 'Method "%s()" is deprecated, use "newName()" instead.', __METHOD__);

    return $this->newName($value);
}
```

`trigger_deprecation()` emits `Since sylament/tables 1.3: …` as a silenced `E_USER_DEPRECATED`, and the package that first calls it requires `symfony/deprecation-contracts` (`^2.5|^3`, the constraint Symfony's own packages declare).
The version is the release that first ships the deprecation, always a tagged one, since before the first tag a replaced name is simply removed.
PHP's native `#[\Deprecated]` is not used: no Symfony package uses it, and it cannot mark a class, a config key, a Twig function or a tag.
The change hand-writes one `UPGRADE-x.y` entry, and the `CHANGELOG` line and the next major's removal list are generated from it.
A planned `final` uses `@final since sylament/<pkg> <version>`, and a planned native return type on a method that has none an `@return` docblock, both reported to subclasses by `DebugClassLoader`; narrowing an existing native return type has no runtime channel and waits for the major.
A moved class stays behind as `class Old extends New {}` with `@deprecated`, and a moved host trait as `trait Old { use New; }`.

### A closure-injection name

The `Invoker` resolves names in `resolveDefaultClosureDependencyForEvaluationByName()`, so a renamed injection is served under both names in its evaluation context, the old one calling `trigger_deprecation()` naming the new.
An added injection is a `CHANGELOG` line naming the name and the context, because it changes what an existing closure with a same-named defaulted parameter receives.

### A config key, a DI tag, a service id, a route, a query parameter

```php
$node->scalarNode('old_key')->setDeprecated('sylament/support', '1.3', 'The "%node%" option is deprecated, use "new_key" instead.');
```

`Alias::setDeprecated()` and `Definition::setDeprecated()` do the same for a service id, and a deprecated route alias for a route.
A DI tag has no channel of its own, so the collecting compiler pass reads both tags and calls `trigger_deprecation()` for the old one.
A query parameter is read under both names, and the URL is rewritten to the new one.

### A Twig block

```twig
{% block page_header %}{% block header %}…{% endblock %}{% endblock %}
```

An application's override of `header` still renders, and one of `page_header` replaces both.
No runtime notice is possible: `{% deprecated %}` inside the old block fires for applications that did not override it and stays silent for those that did, and Twig says "Don't use the `deprecated` tag to deprecate a `block`" (https://twig.symfony.com/doc/3.x/tags/deprecated.html).
The override check is the channel, since Twig's parser exposes an override's parent and block list statically.

### A `<twig:>` prop, slot and tag

A prop keeps its old name beside the new, and fires exactly when a caller passed the old one:

```twig
{% props label = null, oldLabel = null %}
{% if oldLabel is not null %}
    {% deprecated 'Prop "oldLabel" is deprecated, use "label".' package='sylament/support' version='1.3' %}
    {% set label = oldLabel %}
{% endif %}
```

A slot, `footer` renamed `actions`, keeps its old block where it never renders and pours it into the new one:

```twig
{% if false %}{% block footer %}{% endblock %}{% endif %}
{% set legacy = block('footer') %}
{% if legacy|trim is not empty %}
    {% deprecated 'Slot "footer" is deprecated, use "actions".' package='sylament/support' version='1.3' %}
{% endif %}
<div>{% block actions %}{{ legacy|raw }}{% endblock %}</div>
```

A caller filling `footer` gets its content in the new place and one notice, and a caller filling `actions` or nothing gets none.
A tag stays as an anonymous wrapper rendering the new one under a top-level `{% deprecated %}`.

### A context variable

The old name keeps being passed with the same value from `getExtraTemplateData()` until the next major, and the override check reports every override whose parse tree reads it.
No runtime notice fires on a variable read, and `strict_variables` is off in production by default, which is why the name is Stable (T5).

### A Twig function

```php
new TwigFunction('sy_old_name', $callable, ['deprecation_info' => new DeprecatedCallableInfo('sylament/support', '1.3', 'sy_new_name')]);
```

It fires at compile time once per use site, with file and line, so it reaches every copied partial; the old registration stays until the next major.

### A `sy-` class

Both names go in the markup (`<div class="sy-ta-empty-state sy-ta-empty">`) and in the stylesheet (`.sy-ta-empty-state, .sy-ta-empty { … }`) until the next major, so an extender who selects the old class and a replacer whose copied partial carries it both keep working.
CSS has no runtime channel, so the announcements are the generated `UPGRADE` find-and-replace table and a scan that names the successor wherever the old name appears in the application's CSS.

### A colour-contract name

`ColorManager` writes slot and palette declarations itself, so a renamed slot variable or palette token is emitted under both names with the same value; setters go through policies and config, not CSS.

### A `--sy-` token

The alias depends on whether extenders read the token or set it, and both cannot be aliased at once, because two custom properties that reference each other form a cycle and both become invalid.
A token `ColorManager` writes as a literal, a palette token, is emitted under both names with the same literal.
A token declared in CSS with a default and mostly set by extenders defaults its new name from the old (`--sy-sidebar-size: var(--sy-sidebar-width, 18rem);`), which covers setters on the declaring element or an ancestor, and an extender who reads the old name is named by the scan and the `UPGRADE` table.

### A Stimulus value, target, action, identifier and event

- **Value:** in `connect()`, the old `data-<identifier>-<old>-value` is copied to the new value with one warning.
- **Target or marker:** guarded with `has…Target`, the old marker read as a fallback.
- **Action:** the old method stays as a one-line alias that warns and calls the new.
- **Identifier:** the old identifier is registered as a class extending the new one that warns in `connect()`.
- **Event:** the new name is dispatched, then the old with the same `detail`, until the next major; a listener-side rename cannot be detected, because the platform exposes no listener list.
- **Warnings** log once per message, only when `this.application.debug` is true, which StimulusBundle sets from `kernel.debug`.

### A translation key

The key is renamed in the catalogue and at its call site in one change, and the old and new key, and every added key, are listed in the generated `UPGRADE` table per domain.
No duplicate key is kept: the code reads only the new key, so an application's override of the old one would not apply anyway, and a catalogue holds only keys its code uses (`.claude/rules/catalogues.md`).
The profiler's fallback marking and `LoggingTranslator`'s debug line "Translation use fallback catalogue." show an application which of its keys no longer match.

### An icon alias or a palette name

The registry keeps an old-to-new map, resolves the old name and calls `trigger_deprecation()`.

### A hook name

Registering a hook under its old name calls `trigger_deprecation()` naming the successor and stores the hook under the new name, so every render site reads one name.

## Cadence and support

- **Patches** fix only: no features, options or deprecations, Symfony's patch rule (https://symfony.com/doc/current/contributing/code/maintenance.html).
- **Minors** ship whenever features are ready, at least one per Symfony minor; deprecations may land in any minor, and a Filament sync is a minor, never a patch.
- **Majors** come at most once per Symfony major, and only when deprecated surface exists to remove or a dependency floor genuinely cannot widen; a major is a removal release with no new features.
- **The last minor of a major carries every deprecation, and the next major only removes them,** so an application with no deprecation notices on the last minor upgrades with no code change, as Symfony states for 6.4 and 7.0 (https://symfony.com/doc/current/contributing/code/releases.html).
- **Dependency floors widen in minors:** widening Symfony `^8.1` to `^8.1|^9.0` once the code is clean of Symfony 8 deprecations avoids spending a major on a dependency, the Filament v5 case (https://filamentphp.com/content/alexandersix-the-filament-v5-beta-is-here).
- **Sylament keeps its own version numbers**, and tracks a Filament major's API without matching its number.
  A Filament rename enters a Sylament minor as an alias, and the old name is removed at the next scheduled Sylament major, which may move by up to one Symfony minor to absorb a Filament major rather than add a second one.
  When Filament deprecates a member Sylament already ships, Sylament deprecates it through its own channel and removes it at its own next major; a member Filament deprecated before Sylament ported it is never ported ([the trail README](../progress/README.md#statuses)).
  Filament's v4 Rector configuration renamed about 100 classes (https://raw.githubusercontent.com/filamentphp/filament/4.x/packages/upgrade/src/rector.php), so without this rule a Filament major would force a Sylament one.
- **The previous major gets security fixes for 12 months, and no bug-fix branch:** a bug-fix branch means porting each Filament fix into two lines while Filament ships about one minor a month; Filament itself gives a year of bug fixes and two of security fixes (https://filamentphp.com/docs/5.x/introduction/version-support-policy).
- **Experimental surfaces graduate in batches in a minor,** never in a patch, as Ruff batches promotions (https://docs.astral.sh/ruff/versioning/).
- **From the first tag,** a root `CHANGELOG.md` records each release and `UPGRADE-x.y.md` files carry the upgrade notes, each entry with an impact level (Filament's High, Medium and Low, https://raw.githubusercontent.com/filamentphp/filament/4.x/docs/14-upgrade-guide.md) and a Before/After pair in the shape of Symfony UX's `UPGRADE-3.0.md` (https://github.com/symfony/ux/blob/3.x/UPGRADE-3.0.md).

With this cadence, five years from 1.0 means at most three majors, each a removal release, against Filament's five majors in five years.

## Plugin authors

A plugin sits between Sylament and an application, and suffers most from a minor it did not test against.

- **Seams:** a plugin builds on `<twig:>` tags, props and slots, the colour contract, render hooks and the controllers' lifecycle events (J6), and never copies partials; one that must copy a partial runs the override check against its own template path in its own CI.
- **Constraint:** a plugin that touches only Stable rows declares `^1.x`; one that touches Evolving rows declares `~1.x.0` and tests every minor.
- **CSS:** plugin CSS goes in the plugin layer (`components.sylament.plugins` in 1.0), so a plugin beats Sylament and an application beats both.
- **Reserved names:** a plugin that adds a Filament-named member Sylament has not ported matches Filament's signature, because the port may arrive in any minor.

## Dependencies the promise does not need

The promise works without any package outside the Symfony team, Twig, Doctrine and established tooling, and each of these needs explicit sign-off if proposed:

- `roave/backward-compatibility-check` (https://github.com/Roave/BackwardCompatibilityCheck), which cannot run before a first tag and whose role the inventory's reflection snapshot plays more coarsely;
- `phpstan/phpstan-deprecation-rules`, which mainly serves extenders' own pipelines, where they can install it themselves;
- a shipped Rector rule set with `rector/rector` as a `suggest`, which would turn root dev tooling into something a package offers;
- any CSS or JS lint plugin for extenders, since a scan of the application's CSS against the inventory gives the same signal.

A grant, once given, is a row in [the third-party grants table](dependencies.md#third-party-grants).
