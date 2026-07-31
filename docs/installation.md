# Installation

Sylament is a set of Symfony bundles. Installing it means adding the packages,
registering the bundles, and pointing your asset pipeline at the stylesheet it
ships.

> **Project status.** Sylament is early-stage and **not yet released**. There are
> no tags and nothing is on Packagist, so the instructions below install the
> development branch from GitHub. Expect the API to move. Today `support`,
> `schemas`, `forms` and `actions` carry real functionality; `infolists`,
> `notifications`, `panels`, `tables` and `widgets` are placeholders that ship a
> bundle class and nothing else.

## Requirements

| | |
| --- | --- |
| PHP | 8.5 or newer, with `ext-mbstring` |
| Symfony | 8.1 components (`symfony/property-access`, `symfony/string`, `symfony/validator`) |
| Symfony UX | `ux-twig-component` ^3.1, `ux-live-component` ^3.1, `ux-icons` ^3.3, `stimulus-bundle` ^3.1 |
| Twig | 3.27 or newer |

Doctrine is **not** required. Schemas bind to plain PHP objects; an entity is
just the case where the bound object happens to be managed by Doctrine.

`symfony/asset-mapper` is optional but recommended — it is how the compiled CSS
and the Stimulus controllers reach your pages without you wiring anything. See
[Front-end assets](#front-end-assets) for what to do without it.

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
            "dropdown":{ "enabled": true, "fetch": "eager" }
        }
    },
    "entrypoints": []
}
```

The `core` controller's `autoimport` is what pulls in the stylesheet. The other
three power collapsible sections, modals and dropdown positioning; leave one
disabled if you do not use that component.

<details>
<summary>Without AssetMapper</summary>

The bundles detect AssetMapper and stay out of the way when it is absent, so
nothing breaks — but nothing is delivered either. Serve the compiled files
yourself from `vendor/sylament/support/assets/dist/`:

- `styles/index.css` — the whole stylesheet
- `controllers/*.js` — the Stimulus controllers, if you want the interactive
  components

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

## 5. Check it works

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
