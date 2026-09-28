# Configuration

Sylament has a deliberately small configuration surface. One extension,
`sylament`, with four keys — everything else is done in PHP or in your own CSS.

```yaml
# config/packages/sylament.yaml
sylament:
    colors: []
    icons: []
    default_filesystem_disk: ~
    temporary_file_url_expiry_minutes: 30
```

`config:dump-reference sylament` prints this at any time.

| Key | Means |
| --- | --- |
| `colors` | Palettes keyed by name — see [Colours](#colours). |
| `icons` | Icon overrides keyed by alias — see [Icons](#icons). |
| `default_filesystem_disk` | The flysystem storage uploads write to when a component names none — see [Storage](#storage). Unset by default, and there is no fallback. |
| `temporary_file_url_expiry_minutes` | How long a temporary URL minted for a private file stays valid. Defaults to 30; must be at least 1. |

Higher packages use their own `sylament_<package>` alias where they need settings
of their own — a bundle cannot extend another bundle's config tree. `sylament_forms`
is the first, carrying the temporary-upload settings `FileUpload` reads
([forms-bundle.md](progress/forms/forms-bundle.md)).

## Colours

A colour in Sylament is a **palette name**, never a shade. You say
`->color('danger')`; which shade of danger gets painted is decided per component,
at render time, by measuring contrast against the surface it sits on. That is why
a palette must be complete: the toolkit picks from it.

Six palettes ship and need no configuration:

| Name | Palette |
| --- | --- |
| `primary` | Amber |
| `gray` | Zinc |
| `danger` | Red |
| `info` | Indigo |
| `success` | Green |
| `warning` | Amber |

`primary` and `warning` intentionally share Amber, matching Filament.

### Registering a palette

Add names under `sylament.colors`. A value is either a single colour to generate
eleven shades from, or an explicit shade map.

```yaml
sylament:
    colors:
        # Generated: one colour in, eleven shades out.
        brand: '#7c3aed'

        # Redefining a default works the same way.
        primary: '#0d9488'

        # Explicit: every shade from 50 to 950 must be listed.
        ocean:
            50: 'oklch(0.977 0.013 236.62)'
            100: 'oklch(0.951 0.026 236.824)'
            200: 'oklch(0.901 0.058 230.902)'
            300: 'oklch(0.828 0.111 230.318)'
            400: 'oklch(0.746 0.16 232.661)'
            500: 'oklch(0.685 0.169 237.323)'
            600: 'oklch(0.588 0.158 241.966)'
            700: 'oklch(0.5 0.134 242.749)'
            800: 'oklch(0.443 0.11 240.79)'
            900: 'oklch(0.391 0.09 240.876)'
            950: 'oklch(0.293 0.066 243.157)'
```

Any registered name is then usable anywhere a colour is:

```php
Action::make('publish')->color('brand');
Toggle::make('featured')->onColor('brand');
```

```twig
<twig:SylamentSupport:Badge color="brand">New</twig:SylamentSupport:Badge>
```

**An incomplete palette is rejected**, naming the colour and the missing shades.
This is deliberate: a shade with no corresponding CSS rule paints nothing and
reports nothing, so a partial palette would fail silently at render time instead
of loudly at boot.

### Using the built-in palettes

Sylament ships 26 Tailwind palettes as constants on `Sylament\Support\Colors\Color`
— Slate, Zinc, Rose, Violet, Teal and the rest. Only the six above are wired by
default; the others are there to register with. They are hand-tuned curves, so
they look better than generating a palette from a single hex value.

Reach them from YAML with Symfony's `!php/const` tag:

```yaml
sylament:
    colors:
        rose: !php/const Sylament\Support\Colors\Color::Rose
        accent: !php/const Sylament\Support\Colors\Color::Violet
```

Use the fully-qualified class name with no leading backslash.

Or from PHP configuration, where no tag is needed:

```php
// config/packages/sylament.php
use Sylament\Support\Colors\Color;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('sylament', [
        'colors' => [
            'rose' => Color::Rose,
            'brand' => '#7c3aed',
        ],
    ]);
};
```

**A palette name that was never registered is not resolved.** `->color('rose')`
without registering `rose` renders with the inherited colour — the catalog is a
source of values, not a lookup table.

### Emitting the palettes

Registered palettes reach the browser through one Twig call in your layout head:

```twig
{{ sylamentStyles() }}
```

It emits a `:root` override for each redefined palette and a swap class for each
new one, and an empty string when nothing is registered. The six defaults are
compiled into the stylesheet and do not need it.

**Forget this call and a registered palette renders colourless** — the component
still emits its classes, but the custom properties they reference are never
defined, so the browser has nothing to paint with and nothing appears in the
console. Add it when you install, not when you first register a colour.

### One-off colours without registration

A component also takes a shade map directly, which skips the registry entirely
and rides on inline custom properties. Useful for a colour computed at runtime:

```php
Badge::make()->color([
    50 => 'oklch(0.977 0.014 184.7)',
    // … through 950
]);
```

This needs no `sylamentStyles()` and no configuration, at the cost of repeating
the map at every call site.

### Changing how a component picks its shades

Which shade a component paints with is decided by a **colour policy** — a small
class that states what each part of the component needs, and lets a contrast
search find a shade that satisfies it. A badge's policy says "the label must be
readable against this palette's shade 50"; a button's says something more
involved, because it has a filled background and hover states.

If you disagree with a choice, replace the policy. `ColorManager` resolves
policies from the container by class-string, so redefining that service id in
your application swaps it everywhere the component is used:

```yaml
# config/services.yaml
services:
    Sylament\Support\Colors\Components\BadgeComponent:
        class: App\Colors\MyBadgePolicy
        autowire: true
        public: true
```

```php
namespace App\Colors;

use Sylament\Support\Colors\Contracts\HasColor;
use Sylament\Support\Colors\Contracts\HasDefaultGrayColor;
use Sylament\Support\Colors\Maps\ComponentColorMap;

final class MyBadgePolicy implements HasColor, HasDefaultGrayColor
{
    /**
     * @param array<int, string> $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        return ComponentColorMap::make($color)
            ->slot('text', surface: $color[50], minRatio: 7.0, fallback: 950)
            ->get();
    }
}
```

Four things to know:

- **`public: true` is required.** The lookup is `$container->get(SomeClass::class)`,
  and a private service is not reachable that way. Sylament's own policies are
  public for the same reason.
- **The slot keys are a closed set**: `bg` and `text`, each also available as
  `hover:`, `dark:` and `dark:hover:`. Any other key throws at render time.
- **Return only on-grid shades** — 50 through 950, plus `0` for a text slot
  meaning white. A shade like `150` produces a class with no CSS rule, so it
  paints nothing and reports nothing.
- **Drop `HasDefaultGrayColor` and gray becomes a real colour.** That marker is
  what makes `->color('gray')` emit no colour classes at all, leaving the
  component's own neutral styling to paint it.

Buttons have two policies — `ButtonComponent` for filled and
`OutlinedButtonComponent` for outlined — because text on a filled background and
text on the page are different problems. Override both, or extend
`ButtonComponent`, which the outlined one already does.

This is global for that component type; the template names the policy, so it is
not variable per instance. For a one-off, pass a shade array as above.

## Icons

Sylament renders icons through [`symfony/ux-icons`](https://symfony.com/bundles/ux-icons/current/index.html),
so any icon set it can resolve works. Where a component needs an icon of its own,
it asks for an **alias**, which you can point anywhere.

```yaml
sylament:
    icons:
        modal.close-button: lucide:x
        section.collapse-button: lucide:chevron-down
```

Two aliases exist today:

| Alias | Used by |
| --- | --- |
| `modal.close-button` | the modal header's close button |
| `section.collapse-button` | a collapsible section's toggle |

Keys are taken literally — a dash stays a dash.

Everywhere else you pass an icon name directly, and no configuration is involved:

```php
Action::make('edit')->icon('heroicons:pencil-square');
```

## Storage

Anything that handles a file — `FileUpload` today — writes through a **flysystem
storage**, named. Sylament ships no adapters and no storage configuration of its
own: it resolves a name to a `League\Flysystem\FilesystemOperator` your
application registered, and `sylament.default_filesystem_disk` says which name to
use when a component does not pick one.

The usual route is [`league/flysystem-bundle`](https://github.com/thephpleague/flysystem-bundle):

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

        private.storage:
            local:
                directory: '%kernel.project_dir%/var/storage'
```

```yaml
# config/packages/sylament.yaml
sylament:
    default_filesystem_disk: public.storage
```

Any component can then override it per instance, using the same name:

```php
FileUpload::make('contract')->disk('private.storage')->visibility('private');
```

The setter is called `disk()` because Filament's is — the value it takes is a
flysystem storage name, not a Laravel disk key.

**There is no fallback storage.** A component that names none, with
`default_filesystem_disk` unset, throws on first use and names the key; an
unknown name throws listing every storage that *is* registered. Both are
deliberate — a toolkit that guessed would relocate your uploads the day you
registered a second storage.

You do not need the bundle. Any service implementing `FilesystemOperator` joins
the registry by carrying the tag, with the name components will ask for:

```yaml
services:
    app.uploads_storage:
        class: League\Flysystem\Filesystem
        arguments: ['@app.uploads_adapter']
        tags:
            - { name: 'flysystem.storage', storage: 'public.storage' }
```

### Private files and temporary URLs

A file stored with `visibility('private')` has no public URL, so previews and
download links are minted as **temporary URLs** — signed links that expire.
`sylament.temporary_file_url_expiry_minutes` (default 30, minimum 1) sets how
long one stays valid.

This only works where the adapter supports it. `local` does not: it can neither
sign a URL nor serve one, so a private file on a local storage renders without a
preview and without download or open links. That is a real gap, not a
misconfiguration — private previews need an adapter that generates temporary
URLs (S3 and friends), or a route of your own.

## Theming without configuration

### Overriding a token

Every colour resolves through a `--sy-*` custom property, so you can retheme from
your own stylesheet with no build step:

```css
:root {
    --sy-primary-600: oklch(0.55 0.2 250);
}
```

This changes what the token holds, but **not which shade a component picks** —
that decision is made in PHP against the registered palette. For a nudge this is
fine; for a real change, register the palette instead so the contrast maths sees
it.

### Dark mode

Dark mode is class-based. Put `dark` on any ancestor — usually `<html>` — and
Sylament's components follow:

```html
<html class="dark">
```

Sylament does not decide when that class is present; wire it to your own toggle
or to `prefers-color-scheme`.

### Restyling components

Component classes are semantic and stable (`sy-btn`, `sy-badge`, `sy-fo-input`).
Sylament ships its CSS inside cascade layers, so your own unlayered CSS wins with
no `!important`:

```css
.sy-btn {
    border-radius: 0;
}
```

To start from nothing, drop the `autoimport` line from `controllers.json` and
style the `sy-` classes yourself.

## Overriding templates

Every component's markup is a Twig template you can replace, using Symfony's
standard bundle override path:

```
templates/bundles/SylamentSupportBundle/components/Button/index.html.twig
templates/bundles/SylamentFormsBundle/components/FieldWrapper/index.html.twig
```

Copy the original out of `vendor/sylament/<package>/templates/` and edit it. The
bundle directory name is `Sylament<Package>Bundle`.

## Global component defaults

To change every instance of a component, use `configureUsing()` — a class-keyed
closure applied at construction:

```php
use Sylament\Forms\Components\TextInput;

TextInput::configureUsing(
    fn (TextInput $input) => $input->maxLength(255),
);
```

Called this way it returns a closure that removes the registration again. It is
global and it persists for the process, which matters on a long-running worker —
so scope it when you can:

```php
TextInput::configureUsing(
    fn (TextInput $input) => $input->placeholder('Search…'),
    during: fn () => TextInput::make('query'),
);
```

Everything built inside `during:` gets the configuration; nothing outside does,
even if the closure throws.

## Next steps

- [Installation](installation.md) — getting the bundles registered in the first place
- [Styling architecture](styling.md) — the rules the CSS follows, and why
- [Composite keys](composite-keys.md) — entities keyed by several fields or by an association
- [Upgrading](upgrading.md) — changes that need attention when you update
