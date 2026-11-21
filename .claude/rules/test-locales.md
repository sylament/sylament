---
paths:
  - "/tests/Fixtures/translations/**"
  - "/tests/Fixtures/config/routes/*.php"
description: How a fixture overrides a shipped domain for one locale, how a test reaches a key in the default domain, and where a localised live route lives.
---
# Test locale rules

- **Override a shipped domain for one locale in `tests/Fixtures/translations/<domain>+intl-icu.<locale>.yaml`** (`sylament+intl-icu.sk.yaml`), owned by the lowest area whose tests read it and holding only the units they read, and switch to it with the kernel translator's `setLocale()`; never `addResource()` on that translator, which ignores a resource added once it has loaded its catalogues.
- **Keep the `+intl-icu` suffix on every override,** because without it the translator formats the message with its plain formatter, which replaces a parameter's bare name inside the braces.
- **Give a test that reads the default `messages` domain (`translateLabel()`) its own `Translator`** with an `ArrayLoader` resource, set on the component with `setTranslator()`, because no area owns a `messages` catalogue.
- **Declare a localised live route in the area's own `config/routes/<area>.php`** as `sylament_tests_<area>_localised_live` on `/{_locale}/_components/{_live_component}/{_live_action}`, name it in the fixture host's `#[AsLiveComponent(route: …)]` and drive the host with `setRouteLocale()`, because the shared live route carries no locale and a URL-locale application's live call must keep one.

Background: `docs/internals/i18n.md`, `docs/progress/schemas/section.md`.
