# Testing

The reasoning behind `.claude/rules/tests.md`, the rules for writing a test in the root suite under `tests/`. The rules say what to do; this file says why. How the suite's kernel, base class and writable paths work is in [test-infrastructure.md](test-infrastructure.md).

## Why does a test mirror `src/`?

A test is found from the class it pins, without searching and without a naming convention to remember: `packages/forms/src/Components/TextInput.php` is tested in `tests/Forms/Components/TextInputTest.php`. The same holds for the suite's own machinery, which mirrors `tests/` (`tests/TestCase.php` is pinned by `tests/TestCaseTest.php`).

A trait with behaviour of its own gets its own file at its mirrored path, and a class's file holds only that class's own behaviour. Filament composes most components from traits, so a test of `TextInput` that also pinned `HasAffixes` would repeat in every component composing the trait; with one file per trait, each behaviour has exactly one home. A class or trait with nothing to pin gets no file, so a missing file is not a gap to fill.

A case that crosses packages, such as deleting a table row, is filed under the class the user configures to get it, here `DeleteAction`. A flows folder would hold tests that no class leads to, and it tends to collect whatever does not obviously belong elsewhere.

Pest test files declare no namespace: Pest derives the test class's name from the file's path, so a namespace would only restate it.

## Why is there one kind of test file?

The per-package suites split tests into `Unit/` and `Integration/`, and the folder decided the base class, so a test filed in the wrong one ran on the wrong base. The split is not needed: `KernelTestCase::getContainer()` boots the kernel on demand, and its teardown shuts down only a booted kernel. A test is pure as long as it never asks for the container, and one base class serves both.

Inside a Pest closure, `$this` is bound to the test case, so `$this::getContainer()` reaches `KernelTestCase`'s statics; `static::` and `self::` resolve against the scope the closure was declared in, which is not the test case.

Behaviour is the default: a component is driven the way its user drives it, by rendering it or calling it, and the test asserts what comes out. Pure logic stated as a dataset is kept for dense algorithms where a table of inputs and outputs is the clearest statement, such as `Color::calculateContrastRatio()`; elsewhere a dataset tends to multiply cases without adding a class of input.

A database test sits beside the other cases of the class whose behaviour it pins, rather than in a database folder, because the database is how that behaviour is reached, not what the test is about. The platform comes from the run's environment, so the same file runs on SQLite by default and on a server under `composer test:mysql` or `composer test:postgresql`.

## Why does every database test run on every platform?

A behaviour that passes on SQLite and fails on MySQL or PostgreSQL is a bug users of those servers meet, so a database test runs on all three. Where a test's premise cannot exist on one platform, the test is skipped there, and the skip message states the platform fact, so the reader learns why the premise is missing rather than that the test was turned off. Why a differing expectation is written out literally per platform is in [test-infrastructure.md](test-infrastructure.md#why-is-a-per-platform-expectation-written-out-literally).

## Why is a render queried with selectors?

Matching an HTML string couples the test to whitespace, attribute order and every element around the one it is about, so any markup change breaks it whether the behaviour changed or not. DomCrawler with CSS selectors finds the one element the test asserts and ignores the rest.

The order of preference follows what a user perceives: the text first, then a role or an accessible name, then a `data-` attribute or a Stimulus controller when the behaviour is a JavaScript hook. A semantic `sy-` class is asserted only when the class is itself the behaviour, as `sy-inline` places a prefix inside the border, because those classes are already the stylesheet's contract.

Document structure (`div > span:nth-child(2)`), whole HTML strings and snapshots are never asserted. Each breaks on any markup change, and a snapshot is regenerated on every such change without anyone reading what changed, so it pins nothing.

## How much is enough?

The port trail already lists what a user can observe, row by row, so one case per observable row covers the feature, and the row's semantics and divergence entries become assertions in that case. An extra case is added only for a different outcome a user can trigger. Code branches do not count, because they describe the implementation, not what the user sees. Where the risk is concentrated (a composite key, a platform difference, record identity) one deep case goes further than the rest. A dataset row is added only for a different class of input; more inputs of the same class add run time and nothing else.

There is no size limit per feature. A small method with dense logic, such as `IdentifierConstraint`, rightly gets more test than code. A change whose tests outgrow its code is checked case by case against these rules and the list of what never gets a test.

As a worked example, `TextInput::email()` gets two cases: the `type="email"` render, and a submit that is rejected and then accepted. Filament's own suite has a third, reading the type back through a getter, which the list below excludes.

## Why is a bug test named after the behaviour?

Once the bug is fixed, its name, issue number or commit means nothing to a reader of the test, while the behaviour it guarantees ("refuses a malformed key without running a statement") stays true and readable. The test lives in the mirrored file like any other test of that behaviour, with no regression folder and no comment linking the commit: git holds the history, and the trail's semantics entry holds the why. A fix starts with a case that fails without it, which proves the case pins the bug; when an existing case is one assertion short of that, extending it keeps the behaviour in one place.

## Why does some code never get a test?

Each item on the list is checkable against a diff:

- **A setter read back through its getter** passes as long as the property is stored, even when nothing renders it; rendering it once tests what the user sees, and the getter along with it.
- **A closure or `Markup` variant of a property** goes through `evaluate()` or `Markup` rendering, each tested once where it lives; repeating it per property tests the same code again.
- **A default nobody sees** is an implementation detail; a default a user sees, such as the gray icon, is behaviour and gets its case.
- **What PHPStan proves**, such as return types and implemented interfaces, is already checked on every run at level max.
- **Symfony, Doctrine, Twig, UX and Pest behaviour** is tested by those projects; a test here would pin their release, not ours.
- **A trait's behaviour repeated per component** multiplies cases without adding risk, so its cases run once, through one real component that composes it; a stub class built for the test would pin a component nobody uses.
- **A case added only because a mutant survived** pins the implementation rather than an outcome a user can observe.
- **A Filament-parity detail no user relies on**, such as the affix-action cache not refilling after a late setter, is documented in the trail and left unpinned, so the implementation stays free to change.

Setters that interact are the boundary case. `inlinePrefix()->prefix('kg')` leaves the prefix outside the border, because the last call on a side wins; that is logic rather than a stored value, so it gets a case, asserted through what renders rather than through `isPrefixInline()`.
