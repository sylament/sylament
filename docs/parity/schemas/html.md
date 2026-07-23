# Html (schemas)

Renders raw HTML content. Also the coercion target `Schema::getComponents()`
normalizes non-string, non-component children to (see [schema.md](schema.md)).

Reference:

- [`vendor/filament/schemas/src/Components/Html.php`](../../../vendor/filament/schemas/src/Components/Html.php)

## Divergences

- **Renders raw via the `markup()` Twig function**, not Blade's
  `toEmbeddedHtml()` string concatenation. `Html` is the deliberate raw-output
  escape hatch: its content — string or `Markup` — is emitted unescaped
  (`markup(component.getContent)` wraps a plain string in `Markup` so Twig's
  autoescaper passes it through). This is the one component whose plain-string
  content is not escaped; the future
  [html-sanitizer](../infrastructure.md#html-sanitizer) concern applies here for
  user-derived content. (Reached through the `getComponents()` coercion, `Html`
  only ever receives `Markup` — a string is caught by the `is_string → Text`
  branch first — so the raw-string exposure is confined to a direct
  `Html::make('…')` call.)
- **`new static()` over `app()`**; two-layer Twig template over Filament v5's
  inlined `toEmbeddedHtml()` (same rationale as [section.md](section.md)).
- **No wrapper element.** Like Filament, `Html` emits its content with no
  surrounding tag — the schema's grid cell provides any wrapper.
