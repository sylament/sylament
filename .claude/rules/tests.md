---
paths:
  - "/tests/**"
description: Where a root-suite test lives, what kind it is, how much it covers, how it asserts a render, and what never gets a test.
---
# Test rules

## Where a test lives

- **Mirror `src/` per package:** `packages/support/src/Colors/Color.php` is tested in `tests/Support/Colors/ColorTest.php`, so a test is found from its class without searching; the suite's own machinery mirrors `tests/` the same way (`tests/TestCase.php` → `tests/TestCaseTest.php`).
- **A trait with behaviour to pin gets its own file** at its mirrored path (forms' `Components/Concerns/HasAffixesTest.php`); a class or trait with nothing to pin gets none, and a missing file is not a gap.
- **A class's file holds only that class's own behaviour;** what it composes from a trait is tested in the trait's file, so each behaviour has one home.
- **A case that crosses packages goes in the file of the class the user configures for it** (a table row's delete belongs to `DeleteAction`, so to actions' `DeleteActionTest.php`); there is no flows folder, because a flow has no class to be found from.
- **Declare no namespace in a test file** outside `tests/Architecture/`; Pest names the class from its path.
- **A scan of the repository's files lives in `tests/Architecture/`,** since no class leads to it; one that declares functions takes `namespace Sylament\Tests\Architecture`, which keeps them out of the global namespace.
- **Prefix each function you add to a scan with its subject** (`trailDocs()`), because every scan shares that namespace.
- **Call a function a test file declares only from that file:** under `--parallel` a worker loads only its share of the test files, so a call from another file fails there alone; `TestFileFunctionsTest` enforces it.
- **Define an expectation more than one test file uses once, in `tests/Expectations/`** (`toBeRecord()` in `Records.php`), never in a test file: Pest loads that folder in every process before any test, while a test file's `expect()->extend()` exists only in a worker that has loaded that file.

## Kinds of test

- **One base class, no `Unit/`/`Integration/` split, no marker:** a test boots the kernel only by asking for the container, because `getContainer()` boots on demand; never call `bootKernel()` just to get one.
- **Use `$this::` for `KernelTestCase` statics** inside `it()`/`test()` closures (`$this::getContainer()`), never `static::` or `self::`: Pest binds `$this` to the test case, while `static::` resolves against the closure's scope.
- **Behaviour is the default kind:** drive a component as its user does, rendering it or calling it, and assert what comes out.
- **Drive a live host through `$this->createLiveComponent()` and the testing API** (`fillForm()`, `callAction()`, `assertFormSet()`), never through UX's `TestLiveComponent` itself, because the tester answers a failed validation as a browser sees it and rethrows any other exception the host threw.
- **Render a schema component through `$this->renderSchemaComponent()`,** which places it in a schema as a page renders it, so the markup asserted is the markup a page shows.
- **Open a page through `$this->visit()`,** a GET through its route as a browser sends it: any answer but a success rethrows the exception behind it, so a broken page fails with its cause, never as an error page's markup, and a redirect, which it never follows, fails naming its `Location`.
- **Read what a request did from a fixture service through `$this::getContainer()` after the call** (`HookLog`), never from a `LiveProp` kept for the test: the client reboots the kernel before every request after its first, so the container read after a call is the one that call ran in.
- **Pure logic touches no container and states its cases as a dataset,** and only for a dense algorithm where a table of inputs and outputs is the clearest statement (`Color::calculateContrastRatio()`).
- **Derive a list a test must keep complete** (every component that offers a trait's behaviour) from `ComponentRegistry`, by the trait's contract where Filament has one (`componentsImplementing()`), else by the trait (`componentsUsing()`), never by typing it, so a new component is covered without anyone editing the test; name an exception with its reason, never the members.
- **Derive no list for a trait a shared base class carries without a contract:** every subclass inherits it, so nothing signals "offers it" and the list would be every component minus exceptions.
- **A database test sits in the file of the class whose behaviour it pins,** beside that class's other cases, seeding with `$this->seed()` and reading back with `$this->reload()` and `$this->statementsRun()`, which counts from the last `seed()` or kernel reboot, whichever came later, so a count is asserted within one request; the platform comes from the run's environment (`composer test:mysql`), never from the test.
- **The platform rule.** A database test runs on SQLite, MySQL and PostgreSQL, or is skipped by premise with `->skip(<condition>, '<the platform fact>')`, and a differing expectation is written literally per platform with `match (TestPlatform::current())`, never computed by a helper that mirrors the product and would agree with it either way.

## How a render is asserted

- **Query the rendered HTML with DomCrawler and CSS selectors,** never by matching an HTML string, so the test survives markup changes around what it asserts.
- **Assert what a user perceives,** in this order: text; a role or an accessible name (a button named "copy", `aria-invalid`, `input[type=email]`); a `data-` attribute or Stimulus controller when the behaviour is a JS hook; a semantic `sy-` class only when the class is the behaviour, since those classes are the stylesheet's contract.
- **Define each selector once per area,** as a constant of `tests/Fixtures/<Area>/Selectors.php`, so a markup rename edits one line.
- **Never assert document structure** (`div > span:nth-child(2)`), a whole HTML string or a snapshot: each breaks on every markup change, and a snapshot is rewritten without anyone reading it.

## How much is enough

1. **One case per trail row a user can observe** (`done` rows and the shipped part of `in progress` rows); its semantics and divergence entries become assertions in that case, or an extra case only when they describe a different outcome a user can trigger.
2. **One case per branch a user can trigger:** a different input leading to a different visible outcome; code branches do not count.
3. **One deep case where the risk is:** a composite key, a platform difference, record identity.
4. **One case per fixed bug,** unless an existing case already fails without the fix.
5. **A dataset row only for a different class of input,** never more of the same.

There is no size limit per feature, since dense logic rightly gets more test than code; a change whose test lines exceed its `src/` and template lines is checked case by case against these rules and the list below.

## Bug tests

- **Name it after the behaviour it guarantees** (`'refuses a malformed key without running a statement'`), never the bug, an issue or a commit, because the name must still mean something once the bug is gone.
- **Put it in the mirrored file** like any test of that behaviour: no regression folder and no comment linking the commit; the trail's semantics entry holds the why.
- **Start a fix with a case that fails without it;** if an existing case is one assertion short, extend it instead.

## What never gets a test

1. **Reading a setter back through its getter** (`prefix('€')` then `getPrefixLabel()`), at any level; render it instead, once.
2. **A closure or `Markup` variant of a property:** `evaluate()` and `Markup` rendering are each tested once, where they live.
3. **A default nobody sees** (`getPrefixIconColor()` is `null`); a default a user sees (the gray icon) is behaviour.
4. **What PHPStan proves:** return types, a contract's method list, that a class implements an interface.
5. **Symfony, Doctrine, Twig, UX or Pest behaviour** (that Twig escapes, that Doctrine flushes).
6. **A trait's behaviour again per component;** its cases run once, through one real component that composes it.
7. **A case added only because a mutant survived.**
8. **A Filament-parity detail no user relies on:** the trail documents it, and no test pins it.

Setters that interact (`inlinePrefix()->prefix('kg')` leaves the prefix outside, because the last call on a side wins) are logic, not item 1: they get a case, asserted through what renders.

A pin of the suite's own infrastructure (`TestCaseTest`) may observe state through any reader, a getter or a default included, because the mechanism under test is the reset, not the reader.

Background: `docs/internals/testing.md`.
