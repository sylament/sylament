# Testing

The reasoning behind `.claude/rules/tests.md`, the rules for writing a test in the root suite under `tests/`, `.claude/rules/test-renders.md`, how such a test asserts a render, `.claude/rules/test-scope.md`, how much a package test covers and what it leaves out, and `.claude/rules/testing-api.md`, how the testing API in `packages/*/src/Testing` is ported. The rules say what to do; this file says why. How the suite's kernel, base class and writable paths work is in [test-infrastructure.md](test-infrastructure.md).

## Why does a test mirror `src/`?

A test is found from the class it pins, without searching and without a naming convention to remember: `packages/forms/src/Components/TextInput.php` is tested in `tests/Packages/Forms/Components/TextInputTest.php`. The same holds for the suite's own machinery, which mirrors `tests/` (`tests/TestCase.php` is pinned by `tests/TestCaseTest.php`). Package tests sit one level down, in `tests/Packages/`, so a package folder such as `Support` never sits beside the suite's own `Infrastructure/` or `Fixtures/` and is never read as one of them.

A class keeps one test file however many cases it needs. Splitting a large class's cases into files by prefix (`AssociationReader<Shape>Test.php`) leaves a reader to search for every file whose name starts with the class's, and every split invites a case to land in the wrong part; Pest's `describe()` blocks give a long file the same structure inside one path.

A trait with behaviour of its own gets its own file at its mirrored path, and a class's file holds only that class's own behaviour. Filament composes most components from traits, so a test of `TextInput` that also pinned `HasAffixes` would repeat in every component composing the trait; with one file per trait, each behaviour has exactly one home. A class or trait with nothing to pin gets no file, so a missing file is not a gap to fill. A trait with a single case still gets its file: the file is where a reader of the trait looks, however little it holds.

A case that crosses packages, such as deleting a table row, is filed under the class the user configures to get it, here `DeleteAction`. A flows folder would hold tests that no class leads to, and it tends to collect whatever does not obviously belong elsewhere.

Pest test files declare no namespace: Pest derives the test class's name from the file's path, so a namespace would only restate it. The scans in `tests/Architecture/` may keep `Sylament\Tests\Architecture`, the namespace of the classes beside them.

A test file declares no function at all. A run of one file or one folder loads only those test files, so a function another file declares is missing there, while two files declaring one name fatal only in a run that loads both; each shows only in some runs. A scan's helper is therefore a static method of a class under `tests/Architecture/`, which Composer autoloads in every run and PHPStan analyses, and a test's helper is a `TestCase` method, or a static method when it needs no container. `FreeHelperFunctionTest` enforces it.

A scan exists only for a rule no tool checks: PHPStan, Rector, PHP-CS-Fixer, Twig-CS-Fixer and the suites cover the rest, and a second check of their rules only drifts from them. A scan that collects a list of violations keeps one case that feeds it a planted violation, because a pattern that matches nothing passes as green as a clean repository. A scan run over a dataset needs none: Pest fails a test whose dataset is empty.

## Why is there one kind of test file?

The per-package suites split tests into `Unit/` and `Integration/`, and the folder decided the base class, so a test filed in the wrong one ran on the wrong base. The split is not needed: `KernelTestCase::getContainer()` boots the kernel on demand, and its teardown shuts down only a booted kernel. One base class serves both.

A component that needs a service must be made after the kernel boots. Until then, `make()` has no container to resolve the class through and falls back to `new`, which runs no `#[Required]` setter, so a `Notification` made before the boot cannot `send()`: it throws for want of the container-built instance. A helper that boots the kernel does not help when the component is built in its argument (`$this->renderViewComponent(Notification::make()->title('Saved'))`), since PHP evaluates the argument first and the instance is a silent `new`-built one. `tests/Pest.php` therefore boots the kernel in a `beforeEach` over `tests/Packages/`, which costs no measurable time, and every component a package test makes is container-built; a test elsewhere, such as the infrastructure pins, asks for the container itself.

Inside a Pest closure, `$this` is bound to the test case, so `$this::getContainer()` reaches `KernelTestCase`'s statics; `static::` and `self::` resolve against the scope the closure was declared in, which is not the test case.

`toThrow()` tells an exception class from a message by `class_exists()`, which answers false for an interface. Handed `Throwable::class`, or a closure whose parameter is typed by an interface, it asserts that the message contains the interface's name; negated, `not->toThrow(Throwable::class)` passes when the closure throws, as long as the message does not contain the word "Throwable". A class name takes the type check either way.

A call that must not throw is asserted by what it returns: the call then runs in the test itself, and any exception it throws fails the test with its own message. `not->toThrow(LogicException::class)` checks only that no exception of that class came out, so a `RuntimeException` the call threw instead passes it, and a `void` call returns `null`, which `toBeNull()` reads.

Behaviour is the default: a component is driven the way its user drives it, by rendering it or calling it, and the test asserts what comes out. Pure logic stated as a dataset is kept for dense algorithms where a table of inputs and outputs is the clearest statement, such as `Color::calculateContrastRatio()`; elsewhere a dataset tends to multiply cases without adding a class of input.

A render goes through `renderSchemaComponent()`, `renderViewComponent()` or UX's `renderTwigComponent()`, the three paths that place a component as a page places it, so the markup a test asserts is the markup a page shows. An area helper may still build what a component needs before it renders, as `TablesHelpers::renderSummarizer()` attaches a summarizer to a column of a table and points it at a query, because the setup is what every summarizer's file would otherwise repeat and the render it ends in is one of the three; a helper that rendered on a path of its own would assert markup no page produces.

A database test sits beside the other cases of the class whose behaviour it pins, rather than in a database folder, because the database is how that behaviour is reached, not what the test is about. The platform comes from the run's environment, so the same file runs on SQLite by default and on a server under `composer test:mysql` or `composer test:postgresql`. `seed()` flushes, clears the identity map and empties the statements DoctrineBundle's profiling collected, so the next read goes to the database and `statementsRun()` holds only the statements under test; each request after a kernel's first starts on reset services or a rebooted kernel, whose collector is empty, so a count spans one request at most. `reload()` re-reads a record's row even when the manager already holds the record, which a plain `find()` would answer from memory. A manager kept across a request belongs to the kernel that request replaced, so `entityManager()` is fetched on every use. How every test gets its own empty database is in [test-infrastructure.md](test-infrastructure.md#how-does-the-root-suite-give-every-test-one-database).

## What does a live test read outside a request?

`createLiveComponent()`'s tester mounts its host in the test process on its first use, whatever that use is, and sends its first request from there ([the divergence](../progress/actions/testable.md#the-tester-mounts-its-component-outside-any-request)). The mount and that request run in the test's own kernel, so a fixture service a mount hook records into, such as `HookLog`, holds the mount's record once `render()` returns. Before that nothing has mounted, and every request after the first reboots the kernel, so a read after a call sees the service the call's kernel built, which never saw the mount.

A panel page learns its panel from the request: its route carries `_sylament_panel`, and a live request, which goes to UX's own route, carries it only through the page's `panelId` prop, minted from that attribute the first time the page dehydrates ([the stamp](../progress/panels/custom-pages.md#the-stamp-runs-above-actions-own-hydration-hook-and-the-priority-is-the-whole-mechanism)). A page the tester mounts has no request to read, so it mints no `panelId` and answers for the default panel on every live request. A test of a non-default panel's page therefore hands the id in the mount data, the value a GET of the page's route would have minted, since the failure it prevents, a route of the default panel that does not exist, names nothing of the cause.

## Why does every database test run on every platform?

A behaviour that passes on SQLite and fails on MySQL or PostgreSQL is a bug users of those servers meet, so a database test runs on all three. Where a test's premise cannot exist on one platform, the test is skipped there, and the skip message states the platform fact, so the reader learns why the premise is missing rather than that the test was turned off. Why a differing expectation is written out literally per platform is in [test-infrastructure.md](test-infrastructure.md#why-is-a-per-platform-expectation-written-out-literally).

## Why is a render queried with selectors?

Matching an HTML string couples the test to whitespace, attribute order and every element around the one it is about, so any markup change breaks it whether the behaviour changed or not. DomCrawler with CSS selectors finds the one element the test asserts and ignores the rest.

The order of preference follows what a user perceives: the text first, then a role or an accessible name, then a `data-` attribute or a Stimulus controller when the behaviour is a JavaScript hook. A semantic `sy-` class is asserted only when the class is itself the behaviour, as `sy-inline` places a prefix inside the border, because those classes are already the stylesheet's contract.

A selector is a constant, or a static helper for a family of classes (`size('lg')`), so a markup rename edits one line. It lives in the lowest area whose package owns the markup: the `sy-` looks support's components give every area (such as colour, size, alignment, state and each component's own look) are in `tests/Fixtures/src/Support/Selectors.php`, which a higher area imports under an alias (`Selectors as SupportSelectors`) beside its own, so two areas never define one `sy-` class string.

An icon is told apart by its fixture SVG's `<title>`. ux-icons copies the SVG file's markup into the page, so the title arrives as text the test can read, while the drawn paths name nothing a reader can check. The SVG's attributes are no substitute: CssSelector lower-cases an attribute name in an HTML document, while the HTML5 parser DomCrawler uses keeps an SVG attribute's case, so `svg[viewBox="0 0 20 20"]` matches nothing, and only `attr('viewBox')` reads it.

Document structure (`div > span:nth-child(2)`), whole HTML strings and snapshots are never asserted. Each breaks on any markup change, and a snapshot is regenerated on every such change without anyone reading what changed, so it pins nothing.

## How much is enough?

The port trail already lists what a user can observe, row by row, so one case per observable row covers the feature, and the row's semantics and divergence entries become assertions in that case. An extra case is added only for a different outcome a user can trigger. Code branches do not count, because they describe the implementation, not what the user sees. Where the risk is concentrated (a composite key, a platform difference, record identity) one deep case goes further than the rest. A dataset row is added only for a different class of input; more inputs of the same class add run time and nothing else.

A composite key is the one deep case that runs twice, over both of its flavours: two scalar fields (`OrderLine`) and an association plus a scalar (`InvoiceLine`, under an `Invoice`), as a two-row dataset. The flavours are different classes of input, because an association half reaches a query, a bind and a comparison through its parent's identifier rather than as a column value of its own, so a by-key site that handles two scalars can still drop or misread the association half, and [record-key.md](../progress/schemas/record-key.md) promises every role over both. A find or delete row seeds a sibling that differs from the target only in the first half and one that differs only in the second, so a site that ignores either half finds or deletes the wrong row. Both are shared entities, so every package that keys a record hosts them without a copy.

There is no size limit per feature. A small method with dense logic, such as `IdentifierConstraint`, rightly gets more test than code. A change whose tests outgrow its code is checked case by case against these rules and the list of what never gets a test.

An assertion helper of the testing API is a test's own tool, so it gets one case that makes it fail, which proves it can: a helper that passed whatever it read would leave every test using it green. The case is for a helper with logic of its own, a matcher or a message built from what it read. An alias that only forwards, such as forms' `assertFormSet()` over schemas' `assertSchemaStateSet()`, fails with the other helper's message from the same code, so a failing case of its own would pin that code twice; the tests that use it cover the forwarding.

As a worked example, `TextInput::email()` gets two cases: the `type="email"` render, and a submit that is rejected and then accepted. Filament's own suite has a third, reading the type back through a getter, which the list below excludes.

## Why is a list of components derived?

A trait's behaviour is tested once, through one component, but when each component's template passes the trait's output on by hand, one case runs over every component that offers it, to catch a template that forgot. A list typed into that test goes stale the day a component is added, and nothing fails: the new component is simply not covered. Derived from the container's registered components, the list grows with them, and a subclass counts too (`DatePicker` extends `DateTimePicker`, so it offers `HasAffixes` as well).

The filter is the trait's contract wherever Filament has one, because Filament sometimes puts a trait on a base class that only some subclasses use (`HasColumns` on `Component`). Implementing the contract is the component saying "I offer this"; a component that merely inherits the trait does not. A trait with no contract is matched through the component's parents and through the traits its traits use. The exception is a behaviour that hangs on the contract itself, such as a builder that reads the nested constraints only from a component implementing their contract: a list by the contract would drop a component that composes the trait but forgot the contract, the very component the case exists to catch, so that list is derived by the trait.

A trait that a shared base class carries with no contract gets no derived list at all. Every subclass inherits it, so no component says "I offer this", and the list would be every component minus exceptions: the "everything except" list the next paragraph rules out.

An exception is named with its reason, never by listing the members to keep: a list of members, even one written as "everything except", is a typed list again and goes stale the same way. How the list leaves the container is in [test-infrastructure.md](test-infrastructure.md#how-does-a-test-get-every-component-that-offers-something).

## Why is a bug test named after the behaviour?

Once the bug is fixed, its name, issue number or commit means nothing to a reader of the test, while the behaviour it guarantees ("refuses a malformed key without running a statement") stays true and readable. The test lives in the mirrored file like any other test of that behaviour, with no regression folder and no comment linking the commit: git holds the history, and the trail's semantics entry holds the why. A fix starts with a case that fails without it, which proves the case pins the bug; when an existing case is one assertion short of that, extending it keeps the behaviour in one place.

A fix that brings Sylament closer to Filament is a bug fix like any other when a user perceives the change, so it gets its case. When nothing observable here changes, it is the parity item of the list below and is logged `parity`. When only a browser would show the difference, a user still perceives it, so the parity item does not apply: it is logged `no harness`, because this suite cannot observe it.

## Why does some code never get a test?

Each item on the list is checkable against a diff:

- **A setter read back through its getter** passes as long as the property is stored, even when nothing renders it; rendering it once tests what the user sees, and the getter along with it.
- **A closure or `Markup` variant of a property** goes through `evaluate()` or `Markup` rendering, each tested once where it lives; repeating it per property tests the same code again.
- **A default nobody sees** is an implementation detail; a default a user sees, such as the gray icon, is behaviour and gets its case.
- **What PHPStan or PHP's types enforce**, such as return types and implemented interfaces, is already checked on every run at level max. A divergence whose only mechanism is a declared type, such as a toast's duration closure returning `null` where Filament falls back to persistent and Sylament's `int|string` getter raises a `TypeError`, is a different outcome a user can trigger, but PHP produces it, not Sylament: a case would pin the language's check, so this item outranks "a case per different outcome".
- **Symfony, Doctrine, Twig, UX and Pest behaviour** is tested by those projects; a test here would pin their release, not ours.
- **A trait's behaviour repeated per component** multiplies cases without adding risk, so its cases run once, through one real component that composes it; a stub class built for the test would pin a component nobody uses.
- **A Filament-parity detail no user relies on**, such as the affix-action cache not refilling after a late setter, is documented in the trail and left unpinned, so the implementation stays free to change.

Setters that interact are the boundary case. `inlinePrefix()->prefix('kg')` leaves the prefix outside the border, because the last call on a side wins; that is logic rather than a stored value, so it gets a case, asserted through what renders rather than through `isPrefixInline()`.

A trail row or entry left without a case says why in one word, so a reviewer checks the omission against one rule instead of weighing an argument. The word goes in the commit body of the change that adds or changes the row: a reviewer reads it beside the diff, and git keeps it with the change. Each item above has its word (`getter`, `closure`, `default`, `PHPStan`, `framework`, `trait`, `parity`). Six more words name reasons that are not items, because they say why this suite cannot or need not pin a behaviour rather than that nobody should:

- **`no harness`:** CSS and JavaScript behaviour, such as a transition or a controller's timer. The suite runs no browser, so it pins only the PHP half, such as the `data-` value a controller reads.
- **`no rig`:** a premise the root suite's database rig cannot build: each run prepares one database on one connection, and a live request's registry serves one manager over it, so a second database, a schema-qualified table, or a second manager that a live request's registry serves has nowhere to live. A second manager a fixture service builds over the world's connection, or the SQLite one `platformEntityManager()` answers, is within the rig, so a premise built on either gets a case. The behaviour is reachable in an application, and `no harness` stays for what only a browser shows.
- **`absence`:** a divergence that only says Sylament lacks something Filament has; there is nothing to observe. It is also the word for a row one would call `none needed`, which names no rule to check.
- **`elsewhere`:** another class's behaviour that this one only passes through, pinned in that class's file, its one home. The trait item is the narrower case of a trait's behaviour repeated per component; this word covers any class.
- **`unreachable`:** a defensive throw that no path through the API reaches; a case could only reach it by forging a state no user can.
- **`by use`:** a wiring row with no class or trait file of its own, such as a service registration or a guard's condition, which every case that runs through the wiring already exercises. It is kept to such rows: a row whose class or trait has a file gets its case there.

## Why is the testing API ported only when a test needs it?

Filament's testing helpers are many, and most assert features the port reaches later. A helper ported ahead of any test is public API nothing exercises, and the first test to use it often shows that its signature, the instance it reads or its failure message must change. Ported in the commit whose test first uses it, a helper is exercised from the start, and the rest keep their `todo` rows. Filament's upstream-deprecated aliases are `skipped`, and so is its Pest `helpers.php`, which reaches the running test globally where the tester's chained methods already cover it.

A helper or arm that is ported but that no committed case exercises stays `in progress`, and its Details entry names the case that would close it, because `done` tells a reader that a test pins the row. A ported helper keeps Filament's visibility unless the trail records a reason to differ: an application's tests and a tester subclass, such as tables' `Testable`, reach it as Filament declares it, so `parseNestedActions()` stays `public`.

`sylament/notifications` is a `suggest` of actions, so the tester's notification assertions guard on it, and the guard must run before the class is named. PHP resolves the class of a static call before it evaluates the call's arguments, so a guard inside the argument, as in `TestsNotifications::assertNotified($this->takeSentNotifications(), …)`, never runs without the package: the call fails with a class-not-found `Error` instead of the `LogicException` that names what to install. The tester takes the list into a variable first.

The selector helpers that query a render stay in the suite, in each area's `Selectors.php`. They encode Sylament's markup, so shipping them would freeze that markup as public API, and Filament ships nothing like them. The public testing API asserts state, records and notifications; the one render it reads, in `assertCanSeeTableRecords()`, is the row ids the tables template gives every record.
