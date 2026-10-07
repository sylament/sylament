# Infolists — port trail

Ports [`filament/infolists`](../../../vendor/filament/infolists) into [`packages/infolists`](../../../packages/infolists): read-only entries and the display machinery they share with table columns.

## Files

<!-- trail-index:start -->
| File | Status | Open | Ports | Description |
| --- | --- | --- | --- | --- |
| [infolists-bundle.md](infolists-bundle.md) | in progress | 2 in progress · 3 todo | `InfolistsServiceProvider.php`, `Commands/MakeEntryCommand.php`, `resources/css/index.css` | The infolists bundle class and the package's stylesheet entry point. |
<!-- trail-index:end -->

## Unported

| Filament | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`Components/CodeEntry.php`](../../../vendor/filament/infolists/src/Components/CodeEntry.php) | blocked | a syntax-highlighting vendor sign-off | [detail](infolists-bundle.md#codeentry-waits-on-a-syntax-highlighting-vendor) |
| [`Components/Entry.php`](../../../vendor/filament/infolists/src/Components/Entry.php) | todo | — | [diverges](infolists-bundle.md#an-entry-is-a-schemas-component-not-a-second-tree-beside-the-schema) |
| [`Components/TextEntry.php`](../../../vendor/filament/infolists/src/Components/TextEntry.php) | todo | — | [diverges](infolists-bundle.md#textentry-shares-the-columns-formatting-trait-instead-of-porting-the-infolists-copy) |
| _rest not audited yet_ | — | — | — |
