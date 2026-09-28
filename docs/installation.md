# Installation

Sylament is a set of Symfony bundles. Installing it means adding the packages,
registering the bundles, and pointing your asset pipeline at the stylesheet it
ships.

> **Project status.** Sylament is early-stage and **not yet released**. There are
> no tags and nothing is on Packagist, so the instructions below install the
> development branch from GitHub. Expect the API to move. Today `support`,
> `schemas`, `forms`, `actions`, `notifications`, `tables` and `panels` carry
> real functionality; `infolists` and `widgets` are placeholders that ship a
> bundle class and nothing else.

## Requirements

| | |
| --- | --- |
| PHP | 8.5 or newer, with `ext-mbstring` |
| Symfony | 8.1 components (`symfony/property-access`, `symfony/string`, `symfony/validator`) |
| Symfony UX | `ux-twig-component` ^3.1, `ux-live-component` ^3.1, `ux-icons` ^3.3, `stimulus-bundle` ^3.1 |
| Twig | 3.27 or newer |

Doctrine is **not** required. Schemas bind to plain PHP objects; an entity is
just the case where the bound object happens to be managed by Doctrine. It
becomes conditional the way storage does below: nothing reaches for an entity
manager until a field says `unique()` or `exists()`, or a `Select` /
`CheckboxList` / `Repeater` says `relationship()` — and then the application
needs `doctrine/doctrine-bundle` registered, which a record-bound form needs
anyway.

`symfony/asset-mapper` is optional but recommended — it is how the compiled CSS
and the Stimulus controllers reach your pages without you wiring anything. See
[Front-end assets](#3-front-end-assets) for what to do without it.

Storage is conditional in a similar way. `league/flysystem` comes in with
`forms`, but nothing reaches for it until you use a field that writes a file —
and when you do, those files need somewhere to go. See
[Storage for file uploads](#5-storage-for-file-uploads).

## 1. Add the packages

Sylament is not on Packagist yet, so add the repositories explicitly. Each
package is published to its own read-only repository under the `sylament`
organisation.

```jsonc
// composer.json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/sylament/support" },
        { "type": "vcs", "url": "https://github.com/sylament/schemas" },
        { "type": "vcs", "url": "https://github.com/sylament/forms" },
        { "type": "vcs", "url": "https://github.com/sylament/actions" }
    ],
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Then require what you need. `forms` pulls in `schemas`, `actions` and `support`
transitively, so this one line is enough for a form-building app:

```bash
composer require sylament/forms:dev-main
```

Add `sylament/actions:dev-main` explicitly if you want buttons, modals and
action groups — it is a dependency of `schemas`, but requiring it directly keeps
your intent visible and pins it in your own lockfile.

<details>
<summary>Working from a local checkout instead</summary>

If you have the monorepo cloned beside your app, use path repositories. Composer
symlinks them, so edits in the monorepo are live in your app immediately.

```jsonc
{
    "repositories": [
        { "type": "path", "url": "../sylament" },
        { "type": "path", "url": "../sylament/packages/*" }
    ]
}
```

```bash
composer require sylament/sylament:@dev
```

`sylament/sylament` is a meta-package requiring all nine bundles. It exists for
this case; prefer the individual packages otherwise.
</details>

## 2. Register the bundles

There is no Flex recipe yet, so add them to `config/bundles.php` yourself, along
with the Symfony UX bundles they depend on:

```php
// config/bundles.php
return [
    // … your existing bundles

    Symfony\UX\StimulusBundle\StimulusBundle::class => ['all' => true],
    Symfony\UX\TwigComponent\TwigComponentBundle::class => ['all' => true],
    Symfony\UX\LiveComponent\LiveComponentBundle::class => ['all' => true],
    Symfony\UX\Icons\UXIconsBundle::class => ['all' => true],

    Sylament\Support\SylamentSupportBundle::class => ['all' => true],
    Sylament\Schemas\SylamentSchemasBundle::class => ['all' => true],
    Sylament\Forms\SylamentFormsBundle::class => ['all' => true],
    Sylament\Actions\SylamentActionsBundle::class => ['all' => true],
];
```

Register only the bundles whose packages you installed. Order does not matter.

## 3. Front-end assets

Sylament ships **compiled** CSS and JavaScript. You never need Tailwind or Node
to use it — Tailwind is a private build-time tool of the toolkit itself.

With AssetMapper installed, each bundle registers its `assets/dist` directory
under the alias `@sylament/<package>` automatically. You opt into the stylesheet
and the Stimulus controllers through `assets/controllers.json`:

```json
{
    "controllers": {
        "@sylament/actions": {
            "dispatch": { "enabled": true, "fetch": "eager" }
        },
        "@sylament/forms": {
            "checkbox-list": { "enabled": true, "fetch": "eager" },
            "select":        { "enabled": true, "fetch": "eager" },
            "repeater":      { "enabled": true, "fetch": "eager" },
            "file-upload":   { "enabled": true, "fetch": "lazy" }
        },
        "@sylament/notifications": {
            "notifications": { "enabled": true, "fetch": "eager" },
            "notification":  { "enabled": true, "fetch": "eager" }
        },
        "@sylament/panels": {
            "sidebar":         { "enabled": true, "fetch": "eager" },
            "theme":           { "enabled": true, "fetch": "eager" },
            "global-search":   { "enabled": true, "fetch": "eager" },
            "unsaved-changes": { "enabled": true, "fetch": "eager" }
        },
        "@sylament/schemas": {
            "schema": { "enabled": true, "fetch": "eager" },
            "tabs":   { "enabled": true, "fetch": "eager" },
            "wizard": { "enabled": true, "fetch": "eager" }
        },
        "@sylament/support": {
            "core": {
                "enabled": true,
                "fetch": "eager",
                "autoimport": {
                    "@sylament/support/styles/index.css": true
                }
            },
            "section": { "enabled": true, "fetch": "eager" },
            "modal":   { "enabled": true, "fetch": "eager" },
            "dropdown":{ "enabled": true, "fetch": "eager" },
            "sortable":{ "enabled": true, "fetch": "eager" }
        },
        "@sylament/tables": {
            "table": { "enabled": true, "fetch": "eager" }
        }
    },
    "entrypoints": []
}
```

List a block for each Sylament package you installed. In a Flex app you do not
write this by hand — `composer` runs the package-json synchronizer, which copies
each package's declared `enabled`/`fetch` in for you; the block above is what it
produces for `support`, `schemas`, `forms`, `notifications`, `actions`, `tables`
and `panels` together.

Support's `core` controller carries the `autoimport` that pulls in the
stylesheet; the other four power collapsible sections, modals, dropdown
positioning and drag-and-drop reordering, and you can disable one you do not
use. Notifications' two controllers drive the toast stack and each toast's
auto-dismiss, hover-pause and close. Forms' four are per-field:
`checkbox-list` supplies a checkbox list's search filter and bulk toggle,
`select` is the searchable select control, `repeater` owns a repeater
item's collapse state and turns a drag into the reorder the server records, and
`file-upload` is the drop zone, previews and upload of `FileUpload`.
`select` is not optional for the fields that use it: a `searchable()`,
`multiple()`, `native(false)` or `allowHtml()` select renders a plain `<select>`
whose binding belongs to the controller rather than to a `data-model` attribute,
so omitting the block leaves a box that neither searches nor sends what the user
picked. A select that is none of those four is a native `<select>` and needs no
JavaScript at all. `repeater` takes support's `sortable` with it: leave them
out and a repeater's collapse buttons do nothing, and any markup the field
cloaks for a stored `persistCollapsed` state stays hidden, since `sy-cloak` is
revealed only once a controller applies its first state. A `reorderable()`
repeater falls back to whatever it was given otherwise — the move buttons if
`reorderableWithButtons()` is on, nothing if the drag handle is the only route.
`file-upload` is the one controller declared `"fetch": "lazy"`: it carries
FilePond and Cropper.js with it, and a page with no upload field on it should
not pay for that. It is also the one whose omission fails total rather than
partial — `FileUpload` falls back to the bare `<input type="file">` under its
label, with nothing listening to it, so there is no drop zone, no preview, and
a picked file never leaves the page.

Schemas' three are per-schema: `tabs` and `wizard` drive tab switching and the
wizard's step navigation, and `schema` sits on every root schema — it reveals the
field a failed validation points at, expanding whatever section, tab or step
hides it, and resets client-side state when the server asks. Leave `tabs` or
`wizard` out and the component renders its initially active tab or step — the one
`activeTab()` / `startOnStep()` or the query string names, which the server
resolves — and stays there.

Tables' single `table` controller is record selection. It sits on any table
whose rows are selectable — one carrying a visible bulk action, or one you
turned on with `selectable()` — and owns every tick: it writes the selection
into the live component's client-side store, so ticking a hundred rows costs no
requests at all, and it repaints the row checkboxes, the header checkbox's
part-selected state, the "N records selected" banner and each bulk action's
trigger after every render. Like `@sylament/actions` below, omitting it fails
silently and fails total: the checkboxes render, nothing reacts to them, the
banner never appears and no bulk action ever becomes available. A table with no
bulk action and no `selectable()` renders no checkboxes and needs none of this.

Panels' four are the admin shell, and every one of them is `eager` because each
has to be right on the first paint. `sidebar` owns the mobile drawer and the
per-group collapse state it restores from local storage; `theme` applies the
stored light/dark/system choice and keeps the switcher in sync — the *first*
paint is handled earlier still, by an inline script the panel document renders
before its stylesheet, so leaving `theme` out costs you the switcher, not a flash
of the wrong theme. `global-search` is the topbar field's keyboard half: without
it the field still renders and still searches, but there is no ⌘K binding, no
Escape, no click-away and no arrow-key walk through the results.
`unsaved-changes` is the navigation guard — it watches the page's form state and
any open modal action, and raises the browser's own "leave site?" prompt when you
navigate away with either outstanding. Omit that block and the guard is simply
absent: the page renders and saves exactly as before, and unsaved edits are lost
without a word. A panel that never arms the alert — the default, until a page
composes the alert trait or the panel calls `unsavedChangesAlerts()` — renders no
guard element at all and needs none of it.

**`@sylament/actions` is easy to leave out and fails silently.** Its single
`dispatch` controller binds to no element of its own — it registers one
page-wide click listener that delivers every `dispatch()` / `dispatchSelf()` /
`dispatchTo()` action on the page, wherever the trigger renders. Omit the block
and nothing throws and nothing is logged: the buttons still render, an action
that also closes still closes, and the events simply never arrive. If you use
action event dispatch at all — including on a notification toast — keep it
enabled.

<details>
<summary>Without AssetMapper</summary>

The bundles detect AssetMapper and stay out of the way when it is absent, so
nothing breaks — but nothing is delivered either. Serve the compiled files
yourself from `vendor/sylament/support/assets/dist/`:

- `styles/index.css` — the whole stylesheet, every package's rules compiled into
  this one file
- `controllers/*.js` — the Stimulus controllers, if you want the interactive
  components
- `utils/*.js` — the helpers those controllers import, in that directory beside
  them

The stylesheet is support's alone, but the controllers are not: serve
`vendor/sylament/notifications/assets/dist/controllers/*.js`,
`vendor/sylament/actions/assets/dist/controllers/*.js`,
`vendor/sylament/forms/assets/dist/controllers/*.js`,
`vendor/sylament/schemas/assets/dist/controllers/*.js` and
`vendor/sylament/tables/assets/dist/controllers/*.js` too if you installed those
packages and want toasts, action event dispatch, the checkbox-list and
searchable-select controls, repeater item collapse and drag reorder, file
uploads, tabs and wizards, or table record selection. Where a package's `dist/`
also holds a `utils/` directory — `support`, `forms`, `schemas` and `tables` all
do — serve it alongside that package's controllers and keep the relative layout:
those controllers ship unbundled and import their helpers as
`../utils/<name>.js`.

Copy them into your public directory, or point your own build at them.
</details>

## 4. Add the colour style block

Add `{{ sylamentStyles() }}` once, in your base layout's `<head>`:

```twig
{# templates/base.html.twig #}
<head>
    {# … #}
    {{ sylamentStyles() }}
</head>
```

This emits the palettes you register through configuration. It renders **nothing
at all** until you register one, so it costs you an empty line today — but
without it, a palette you register later will silently render colourless. Add it
now. See [Configuration → Colours](configuration.md#colours).

## 5. Storage for file uploads

Skip this unless you use `FileUpload`, or anything else that writes a file.
Sylament ships no adapters and no storage configuration of its own: it writes
through a **flysystem storage** your application registers, and finds it by
name. The usual way to register one is `league/flysystem-bundle`:

```bash
composer require league/flysystem-bundle
```

```php
// config/bundles.php
League\FlysystemBundle\FlysystemBundle::class => ['all' => true],
```

Define at least one storage, then point the toolkit at it:

```yaml
# config/packages/flysystem.yaml
flysystem:
    storages:
        public.storage:
            local:
                directory: '%kernel.project_dir%/public/uploads'
            visibility: public
            directory_visibility: public
            public_url: '/uploads'
```

```yaml
# config/packages/sylament.yaml
sylament:
    default_filesystem_disk: public.storage
```

Leave `default_filesystem_disk` unset and every component that does not name a
storage of its own throws on first use, naming the key. A component names one
with `->disk('private.storage')` — the setter is called `disk()` because
Filament's is, and the value it takes is a flysystem storage name.

A file waits on a storage between the upload and the save, and unless you say
otherwise that is the same one, under `sylament-tmp/`. Move it if you would
rather keep in-flight bytes off a public disk:

```yaml
# config/packages/sylament_forms.yaml
sylament_forms:
    temporary_file_upload:
        disk: private.storage   # default: whatever default_filesystem_disk names
        directory: sylament-tmp
        cleanup: true           # sweep leftovers older than 24 h on each upload
```

See [Configuration → Storage](configuration.md#storage) for the rest: private
files and their temporary URLs, and how a service of your own joins the registry
without the bundle.

## 6. Check it works

Drop a component into any template:

```twig
<twig:SylamentSupport:Button color="primary">It works</twig:SylamentSupport:Button>
```

You should see a filled amber button. If it renders as unstyled text, the
stylesheet is not reaching the page — check step 3.

For a form, build a schema in a live component:

```php
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Schema;

public function schema(Schema $schema): Schema
{
    return $schema
        ->statePath('data')
        ->components([
            TextInput::make('title')->label('Title')->required(),
        ]);
}
```

## Next steps

- [Configuration](configuration.md) — colours, icons, theming and template overrides
- [Styling architecture](styling.md) — how the CSS is put together, and the rules it follows
- [Composite keys](composite-keys.md) — entities keyed by several fields or by an association
- [Upgrading](upgrading.md) — changes that need attention when you update
