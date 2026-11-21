---
paths:
  - "packages/*/translations/**"
description: How a package's translation catalogues are named, keyed and written, which domains a package may name, and how validator messages differ.
---
# Catalogue rules

- **A package's messages live in `packages/<pkg>/translations/<domain>+intl-icu.en.yaml`,** which the bundle loads with no wiring (`bundles.md`), and the `+intl-icu` suffix makes the translator format them as ICU; the gate refuses any other file name or format.
- **The domain is Filament's Laravel namespace renamed:** `filament::` → `sylament` (support), `filament-schemas::` → `sylament-schemas`, `filament-actions::` → `sylament-actions`, `filament-forms::` → `sylament-forms`, `filament-tables::` → `sylament-tables`, `filament-notifications::` → `sylament-notifications`, `filament-panels::` → `sylament-panels`, so a Filament user finds each key under the matching domain.
- **The key is Filament's key path verbatim** (`filament-actions::create.single.label` → `create.single.label` in `sylament-actions`), so importing Filament's locales stays mechanical.
- **Nest a key's segments as YAML maps, as Filament's lang arrays nest them** (`create.single.label` → `create:` › `single:` › `label:`), so Filament's locales import as a dump of its arrays; the loader joins the segments back with `.`, and no key may be both a leaf and a prefix.
- **Single-quote every value** (`label: 'Save'`, doubling a `'`), because an ICU message opening with `{` otherwise parses as a YAML map; double-quote only a value holding a line break, written `\n`.
- **Quote a key YAML reads as another type** (`'true'`, `'false'`, `'null'`, a number), since the parser refuses a non-string key.
- **Every key is a string literal at its call site** (`new TranslatableMessage('literal', …, 'sylament-x')`, `'literal'|trans(domain: 'sylament-x')`); turn a key Filament interpolates into a `match` over literal keys, because the extractor the catalogue gate runs sees only literals.
- **Name only the package's own domain, `validators`, or the domain of a `sylament/*` package its `composer.json` requires:** the split publishes each package alone, so a higher package's domain renders a raw key there; a key Filament reaches upward for is re-homed into the using package's domain under Filament's key path.
- **A catalogue holds only the keys its own package's code uses;** a key a dependent reads from a lower domain is added to the lower catalogue together with the dependent's code, so no catalogue carries an unused key.
- **Our English wins under a shared key,** and the trail entry recording where it differs from Filament's `en` text stays, because Filament's locales map onto the key, not the text.
- **A twin text Filament keys twice takes the key of the package whose code renders it** (Section's collapse labels: `components/section.actions.*` in `sylament`), so one catalogue owns it.
- **A port-added text gets a new key beside Filament's in the same shape,** recorded in the trail entry of the code using it, never an ICU `select` folded into Filament's key, so Filament's locales still map one-to-one.
- **Write messages in ICU:** `{label}` arguments and `{count, plural, one {…} other {…}}`; Filament's `:label` placeholders and `trans_choice()` plurals map onto them.
- **Type an argument a plural's arms also print** (`{total, plural, one {{total, number} result} other {…}}`), because `MessageFormatter` refuses an argument used both as the plural selector and untyped (`U_ARGUMENT_TYPE_MISMATCH`); an untyped argument elsewhere prints an integer as bare digits, and `number` groups it (`5,000`).
- **A composed text is one parameterised message** (`Search {label}`), never two messages joined, because word order differs by locale; and no toolkit message is ever wrapped in `Markup`, since an argument carrying record data would then render raw.
- **A count text a controller updates keeps the shown number a separate argument, never `#`** (`{count, plural, one {{shown} record selected} other {{shown} records selected}}`): the server renders `one` with `count` = 1 and `other` with `count` = 2, passing `shown` = `__COUNT__` for the controller to replace, and a client-substituted value is a token argument the same way (`tag` = `__TAG__`).
- **Validator messages go in `packages/<pkg>/translations/validators.en.yaml`,** flat, each English id a quoted key, with `{{ param }}` placeholders, not ICU, because Symfony's validator translates a constraint's `message` in that domain under that id.
- **A validator message id never interpolates:** pass a label or a limit as a constraint parameter, so the id stays one catalogue entry.

Background: `docs/internals/i18n.md`.
