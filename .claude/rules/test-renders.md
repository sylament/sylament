---
paths:
  - "/tests/Packages/**"
  - "/tests/Fixtures/src/*/Selectors.php"
description: How a root-suite test queries a render, what it asserts first, and where a selector it shares is defined.
---
# Test render rules

## How a render is asserted

- **Query the rendered HTML with DomCrawler and CSS selectors,** never by matching an HTML string, so the test survives markup changes around what it asserts.
- **Assert a render through `toContainElement($selector, $text)` and `toContainButtons($selector, $names)`** (`tests/Expectations/Crawler.php`), so a failure names the selector and the texts or accessible names it found; filter the `Crawler` by hand only for a reading neither gives.
- **Assert what a user perceives,** in this order: text; a role or an accessible name (a button named "copy", `aria-invalid`, `input[type=email]`); a `data-` attribute or Stimulus controller when the behaviour is a JS hook; a semantic `sy-` class only when the class is the behaviour, since those classes are the stylesheet's contract.
- **Tell icons apart by the fixture SVG's `<title>`,** in an area's icons and the shared `heroicons` alike, read as the icon's text, because a drawn shape names no icon.
- **A selector more than one test file uses is defined once, in the lowest area whose package owns the markup, and higher areas import it** (`Support\Selectors` for support's colour, size and button looks), as a constant, or a static helper for a family of classes, of that area's `tests/Fixtures/src/<Area>/Selectors.php`, so a markup rename edits one line; a selector one file uses may stay inline in it.
- **Never assert document structure** (`div > span:nth-child(2)`), a whole HTML string or a snapshot: each breaks on every markup change, and a snapshot is rewritten without anyone reading it.

Background: `docs/internals/testing.md`.
