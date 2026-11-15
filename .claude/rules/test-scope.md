---
paths:
  - "/tests/Packages/**"
description: How much a package test covers, how a bug is pinned, and what never gets a test.
---
# Package test scope rules

## How much is enough

1. **One case per trail row a user can observe** (`done` rows and the shipped part of `in progress` rows); its semantics and divergence entries become assertions in that case, or an extra case only when they describe a different outcome a user can trigger.
2. **One case per branch a user can trigger:** a different input leading to a different visible outcome; code branches do not count.
3. **One deep case where the risk is:** a composite key over both flavours (scalar fields; an association plus a scalar), a platform difference, record identity; a composite case runs as a two-row dataset over schemas' `OrderLine` and `InvoiceLine`, since a by-key site can handle one flavour and miss the other.
4. **One case per fixed bug,** unless an existing case already fails without the fix.
5. **A dataset row only for a different class of input,** never more of the same.
6. **One failing case per testing-API assertion helper with logic of its own;** an alias that only forwards to another helper is covered by use, since its failure message is that helper's.

There is no size limit per feature: a change whose test lines exceed its `src/` and template lines is checked case by case against these rules and the list below.

## Bug tests

- **Name it after the behaviour it guarantees** (`'refuses a malformed key without running a statement'`), never the bug, an issue or a commit, because the name must still mean something once the bug is gone.
- **Put it in the mirrored file** like any test of that behaviour: no regression folder and no comment linking the commit; the trail's semantics entry holds the why.
- **Start a fix with a case that fails without it;** if an existing case is one assertion short, extend it instead.
- **Pin a parity fix that changes what a user perceives** like any bug; log one that changes nothing observable here `parity` (item 7 below), or `no harness` when only a browser would show it.

## What never gets a test

1. **Reading a setter back through its getter** (`prefix('€')` then `getPrefixLabel()`), at any level; render it instead, once.
2. **A closure or `Markup` variant of a property:** `evaluate()` and `Markup` rendering are each tested once, where they live.
3. **A default nobody sees** (`getPrefixIconColor()` is `null`); a default a user sees (the gray icon) is behaviour.
4. **What PHPStan or PHP's types enforce:** return types, a contract's method list, that a class implements an interface, and a divergence a declared type alone makes (PHP's `TypeError` where Filament falls back), even one a user triggers.
5. **Symfony, Doctrine, Twig, UX or Pest behaviour** (that Twig escapes, that Doctrine flushes).
6. **A trait's behaviour again per component;** its cases run once, through one real component that composes it.
7. **A Filament-parity detail no user relies on:** the trail documents it, and no test pins it.

Setters that interact (`inlinePrefix()->prefix('kg')`, where the last call on a side wins) are logic, not item 1: they get a case, asserted through what renders.

Say in one word why a trail row or entry gets no case, in the commit body of the change that adds or changes the row, so a reviewer checks it against one rule; the words are in `docs/internals/testing.md`, where `none needed` is `absence` and `by use` is kept for a wiring row with no class or trait file of its own.

Background: `docs/internals/testing.md`.
