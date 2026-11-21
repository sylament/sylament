---
paths:
  - "/tests/**"
description: Where a root-suite test lives, and what every test file follows.
---
# Test rules

## Where a test lives

- **Mirror each package's `src/` under `tests/Packages/`:** `packages/support/src/Colors/Color.php` → `tests/Packages/Support/Colors/ColorTest.php`, so a test is found from its class without searching and a package never shares a level with the suite's machinery; that machinery mirrors `tests/` the same way (`tests/TestCase.php` → `tests/TestCaseTest.php`).
- **One file per class or trait, however many cases it holds:** never split its cases into prefix files (`AssociationReader<Shape>Test.php`), because a test is found from its class's path; a long file groups its cases with Pest's `describe()`, one block per behaviour.
- **A trait with behaviour to pin gets its own file** at its mirrored path (forms' `Components/Concerns/HasAffixesTest.php`), even for one case; a class or trait with nothing to pin gets none, and a missing file is not a gap.
- **Drive a trait that no component of its own package composes through a component of the lowest package that does** (support's `HasWeight` through schemas' `Text`), so its one home stays at its own package's mirrored path.
- **Test an anonymous `<twig:>` component where its class would sit:** support's `templates/components/Button/index.html.twig` → its mirror's `Twig/Components/ButtonTest.php`, beside the class-backed ones in `src/Twig/Components/`, so its test is found from the tag and stays put if it gains a class.
- **A class's file holds only that class's own behaviour;** what it composes from a trait is tested in the trait's file, so each behaviour has one home.
- **A case that crosses packages goes in the file of the class the user configures for it** (a table row's delete belongs to `DeleteAction`, so to actions' `DeleteActionTest.php`); there is no flows folder, because a flow has no class to be found from.
- **Declare no namespace in a test file** outside `tests/Architecture/`; Pest names the class from its path.
- **A scan of the repository's files lives in `tests/Architecture/`,** since no class leads to it, and exists only for a rule no tool checks, keeping one case that proves it can fail.
- **Declare no function in a test file:** a run of one file loads no other test file, so a scan's helper is a static method of a class under `tests/Architecture/` and a test's helper a `TestCase` method, or a static method when it needs no container.
- **Define an expectation more than one test file uses once, in `tests/Expectations/`** (`toBeRecord()` in `Records.php`), never in a test file: Pest loads that folder in every run before any test, while a test file's `expect()->extend()` exists only in a run that loaded that file.
- **Define a dataset more than one test file uses once, in its area's `tests/Datasets/<Area>.php`** (`'trigger looks'` in `Actions.php`), for the same reason: Pest loads that folder in every run, and a dataset's name is global.

## Writing any test

- **Use `$this::` for `KernelTestCase` statics** inside `it()`/`test()` closures (`$this::getContainer()`), never `static::` or `self::`: Pest binds `$this` to the test case, while `static::` resolves against the closure's scope.
- **Give `toThrow()` a class, never an interface such as `Throwable`,** as its argument or its closure's parameter type: Pest reads a name `class_exists()` rejects as a message substring, so `not->toThrow(Throwable::class)` passes when the closure throws.
- **Assert a call that must not throw by what it returns** (`expect($host->validateSchema($schema))->toBeNull()`), never with `not->toThrow(SomeException::class)`, which passes when the call throws any other exception.
- **Pure logic states its cases as a dataset,** and only for a dense algorithm where a table of inputs and outputs is the clearest statement (`Color::calculateContrastRatio()`).
- **Derive a list a test must keep complete** (every component that offers a trait's behaviour) from `ComponentRegistry`, by the trait's contract where Filament has one (`componentsImplementing()`), else by the trait (`componentsUsing()`), never by typing it, so a new component is covered without anyone editing the test; name an exception with its reason, never the members; derive by the trait even where a contract exists when the behaviour hangs on the contract, since a list by contract agrees with a composer that drops it.
- **Derive no list for a trait a shared base class carries without a contract:** every subclass inherits it, so nothing signals "offers it" and the list would be every component minus exceptions.

Background: `docs/internals/testing.md`.
