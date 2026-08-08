🇬🇧 English | 🇵🇹 [Português](PROGRESS.pt.md)

# Development Progress

A running, public log of where Elo stands. Full technical detail lives in
[`docs/adr/`](docs/adr); this page is the plain-language version, updated
as things move forward.

---

## Current stage: "PostResource end to end" is closed

**What's done:**

- Architecture frozen across three ADRs: hierarchy and contracts, philosophy
  and boundaries, official vocabulary.
- Composer package configured, along with the full quality stack: PHPStan,
  Pint, Pest, and continuous integration.
- First architecture test in place and passing, it automatically fails CI
  if the core ever ends up depending on a specific third-party module,
  turning a written rule into an enforced one.
- The seven public contracts (`Module`, `Resource`, `Blueprint`, `Field`,
  `Action`, `Layout`, `Repository`) implemented as typed skeletons, each
  with unit tests covering their core behavior: Blueprint's immutability,
  Field's identity-separate-from-persistence rule, Module's optional hooks,
  and the five v1 Layout types (Section, Tabs, Grid, Card, Column).
- Design tokens (`tokens.css`) and a minimal base stylesheet (`elo.css`),
  plain CSS custom properties, no Tailwind, no third-party UI kit, scoped
  under `.elo-panel` so the package never leaks styles into the host site.
- `EloquentRepository`, the only `Repository` in v1, and the
  `Resource::repository()` contract completion.
- `ResourceForm`, the first real Livewire component: renders a Resource's
  Blueprint as a create/edit form, resolves field order from the Layout
  (or falls back to declaration order when there isn't one), derives
  validation directly from each Field's `isRequiredOn()`, and saves through
  the Resource's Repository. Tested end to end against a real Eloquent
  model on an in-memory database, creating, validating, editing, and the
  `elo-resource-saved` event.
- Routing: a generic `ResourceController` (create/edit, never one
  controller per Resource) plus routes generated automatically from a
  slug-to-Resource map in a publishable `config/elo.php`. Full Module
  discovery isn't wired in yet, this is deliberately the minimal real
  version, a hand-maintained map, until a real case asks for more.
  `php artisan vendor:publish --tag=elo-assets` publishes `elo.css` where
  routes can actually load it from.
- The `elo()` front-end helper: `elo()->resource('posts')->where(...)->get()`,
  resolving a registered Resource by slug and returning its
  `RepositoryQuery` directly. Only `resource()` exists, `settings()`,
  `menu()`, and `form()` from the original ADR sketch don't have a real
  case behind them yet.
- A complete, realistic `PostResource` in `examples/`, reference code
  meant to be copied into a real app, deliberately not shipped as part of
  the installable package (a blog Post is domain-specific, the core stays
  small on purpose).
- The `elo:sync` engine: `ColumnDefinition`, `Operation` and its two v1
  implementations (`CreateTable`, `AddColumn`), `MigrationWriter`,
  `SchemaSnapshot`, and `SchemaDiff`, all tested, including generated
  migrations run for real against SQLite. Additive only by construction,
  an existing column is never altered or dropped, so a column added by
  hand outside Elo always survives a sync run.
- `elo:sync`, the artisan command itself. Reads every Resource in
  `config('elo.resources')`, diffs its Fields against its Repository's
  table, and writes a single migration with whatever's missing across all
  of them, nothing at all when everything's already in sync. Closes a
  contract amendment along the way, `Repository::table()`, the same kind
  of after-the-fact completion as `Resource::repository()` was, the
  contract stayed silent on it until a real implementation needed to
  know.
- `ResourceTable`, the second real Livewire component: lists a Resource's
  records, sortable per column, paginated, with delete inline. Completes
  `RepositoryQuery` with `paginate()`, the same kind of after-the-fact
  contract completion `Repository::table()` was, `RepositoryQuery` stayed
  a plain `where()/orderBy()/get()/first()` set until a real consumer
  needed a page instead of the whole set. Reads `Blueprint::getFields()`
  directly, the same as `SchemaDiff`, not `getLayout()` the way
  `ResourceForm` does, a table's columns aren't grouped into sections the
  way a form's inputs are, so this consumer still doesn't resolve
  composition the way `ResourceForm` does, see the `BlueprintCompiler`
  note below.
- Routing gained a third fixed route, `index`, alongside `create` and
  `edit`, resolving the same way, through the same generic controller.
- `ActionRunner`, connecting `Action`'s handler (already tested in
  isolation since `Action` itself shipped) to a real Resource: resolves
  an Action by id from the Blueprint, loads the record through the
  Repository, runs the handler, saves whatever attributes it returns.
  `ResourceTable` now renders row actions as buttons per record and bulk
  actions in a toolbar above the table, both wired straight through
  `ActionRunner`. `Delete` stays a separate, always-available table
  method, not an `Action`, every table needs it regardless of what a
  Resource declares.

**Next up:**

`Module` discovery, replacing the hand-maintained slug map once real
modules exist to discover, per the plan agreed while deferring
`BlueprintCompiler`.

**Just shipped: `Action`, made runnable**

- `ActionRunner`: the piece that was missing between `Action` knowing how
  to run its own handler and a Resource's records actually changing. A
  handler returning an array of attributes gets them merged into the
  loaded record and saved; a handler returning anything else, `null`, a
  side effect, its own persistence elsewhere, is left alone, nothing gets
  auto-saved. `Action`'s own contract didn't change, `ActionRunner` only
  adds the record lookup and the save step on top of it.
- `ResourceTable` renders every non-bulk Action visible on
  `Field::CONTEXT_INDEX` as a button per row, and every bulk Action in a
  toolbar above the table, enabled once at least one row is selected.
  Both call through to `ActionRunner`.
- Tested with a real handler (`shout`, uppercases a title), a real bulk
  handler (`clear-body`, clears a field across every selected record), a
  handler that returns nothing (confirms nothing gets auto-saved), and
  both failure paths: an unregistered action id, a record id that doesn't
  exist.

**Just shipped: `ResourceTable`**

- Lists every Field a Resource's Blueprint declares, minus whichever ones
  it hides on `Field::CONTEXT_INDEX` via `hiddenOnIndex()`, in declaration
  order. Clicking a column header sorts by it, a second click reverses
  direction.
- Pagination via the new `RepositoryQuery::paginate()`, wired directly, no
  `Livewire\WithPagination` trait: the trait expects a real
  `LengthAwarePaginator` bound to it, `RepositoryQuery` returns a plain
  array by design, the same shape every other query method already
  returns, so a couple of public properties (`page`, `perPage`) and two
  methods (`previousPage()`, `nextPage()`) do the job without pulling in
  Livewire machinery the Repository abstraction doesn't actually need.
- Delete is inline, on the row, calling straight through to
  `Repository::delete()`.
- A third route, `elo.index`, and `ResourceController::index()`,
  registered the same way `create`/`edit` already were.
- Tested through Livewire's testing helpers: every field rendered as a
  column, every record as a row, the empty state, sorting and its
  direction flip, moving between pages, and delete actually removing the
  row and the underlying record.
- The architectural note on `BlueprintCompiler` (see "Deferred" below)
  said watch what happens when a second renderer exists. It exists now,
  and the answer, at least for this one, is: no duplication. `ResourceTable`
  reads `Blueprint::getFields()` the same flat way `SchemaDiff` already
  does; it never touches `getLayout()`, `uses()` resolution, or reference
  validation the way `ResourceForm` does. The trigger condition still
  hasn't fired.

**Just shipped: the `elo:sync` command**

- The `SyncCommand` itself: loops every Resource in
  `config('elo.resources')`, resolves its table via
  `Repository::table()`, its Fields via `Blueprint::getFields()`, and
  hands both to `SchemaDiff`. Every Operation found across every Resource
  goes into one migration file via `MigrationWriter`, not one file per
  Resource.
- Nothing registered, or nothing out of sync, and it writes no file at
  all, it just says so. Inherits the additive guarantee straight from
  `SchemaDiff`, there's no extra check in the command itself, there's
  nothing to check, the command can't produce a drop or an alter because
  the thing it calls can't either.
- `Repository::table()`, a contract amendment: the sync command needed to
  know which table a Resource's Fields belong to, and `Repository` is the
  only thing that knows how a Resource is actually persisted. Same shape
  as `Resource::repository()` earlier, completing a dependency the
  contract already implied, not introducing a new concept.
- Tested end to end: the generated migration is required and actually run
  against SQLite, both for a brand-new table and for a column added to an
  existing one, not just asserted as generated PHP text.

**Just shipped: the `elo:sync` engine**

- `ColumnDefinition` renders a single Blueprint column call from what a
  Field already knows: type, nullability, and default, deliberately not
  carrying anything a Field doesn't already declare.
- `CreateTable` and `AddColumn`, the two v1 `Operation` implementations,
  each producing an exact inverse for `down()`.
- `MigrationWriter` generates a real anonymous-class migration file from
  an ordered list of Operations, `up()` in the given order, `down()` in
  exact reverse. Tested against migrations actually run on SQLite, not
  just the generated PHP as a string.
- `SchemaSnapshot`, a read-only view of what already exists in the
  database. It only answers table/column existence, never type, which
  follows directly from ADR-001's additive-only policy: since an existing
  column is never altered automatically, its exact type never needs to be
  known.
- `SchemaDiff` compares a Resource's Fields against a `SchemaSnapshot` and
  produces the Operations needed to catch the database up: a missing
  table becomes one `CreateTable`, a missing column on an existing table
  becomes one `AddColumn` per field. A column that already exists, or one
  a Field no longer declares, is left untouched, no drops, no alters, no
  exceptions.

**Just shipped: `EloquentRepository`, and a contract amendment**

- `EloquentRepository` and `EloquentRepositoryQuery` implemented, the only
  `Repository` in v1, tested against a real Eloquent model on an in-memory
  SQLite database. `RepositoryQuery` stays deliberately minimal:
  `where()`, `orderBy()`, `get()`, `first()`, nothing speculative.
- **Contract amendment:** `Resource` gained `repository(): Repository`.
  ADR-001 always committed Resource to depending on Repository, this just
  completes that dependency now that a real implementation exists to
  return. No convention-based inference of the model class, every Resource
  declares its repository explicitly.

**Just shipped: the first real Field (`Text`)**

- Renders as a plain Blade view, not its own nested Livewire component,
  resolved by convention from the class name (`Text` → `elo::fields.text`),
  so a simple Field never has to declare it.
- Established the view contract every Field's Blade view will follow:
  `$field`, `$value`, `$context`, `$error`.
- The field-level CSS classes (`elo-field`, `elo-field__label`,
  `elo-field__input`, `elo-field__error`) now exist in `elo.css`, built
  directly on the tokens from the previous step.

**Deferred, tracked, not implemented:**

- `BlueprintCompiler` / `CompiledBlueprint`: a single compilation pipeline
  resolving `uses()`, validating duplicate and pending references, and
  applying implicit defaults, so Form, Table, `elo:sync`, API, and Export
  would all read from one compiled structure instead of each walking
  `Blueprint` on its own. Not yet: `ResourceTable` shipped as the second
  real runtime consumer and, checked directly, does not resolve
  composition the way `ResourceForm` does, it reads `getFields()` flat,
  the same as `SchemaDiff`, no `getLayout()`, no `uses()` resolution, so
  there's still no real duplication to eliminate, only a prediction about
  permissions, workflows, computed fields, or AI-generated behavior six
  months out. Do not implement before a real runtime consumer exists
  that's independently resolving `uses()`, validating IDs, and applying
  defaults the way `ResourceForm` already does. The test: if removing this
  concept today still leaves the project clean, it isn't structural yet.
- `elo()->resource($slug)` returning a `ResourceHandle`-style facade
  (query, table, form, repository, metadata all from one call) instead of
  the `RepositoryQuery` it returns today. No real caller needs more than
  the query yet.
- `elo()->resource()` accepting a `class-string` or a Model class directly,
  not just a config slug.

- `Field` context flags (`hiddenOn`/`readonlyOn`/`requiredOn`) as a unified
  structure instead of one array per behavior, revisit if a fourth behavior
  (e.g. `disabledOn`) actually shows up.
- Context strings as a PHP enum, revisit only if a real typo causes a real bug.
- Hiding `Closure` behind a `Lifecycle` concept.
- A `Record`/`DataRecord` type instead of raw arrays from `Repository`.
- Stronger PHPDoc types on `Module::commands()`/`widgets()`/`listeners()`
  (e.g. `list<class-string>`), once a first real implementation reveals
  which methods actually settle into that shape (`listeners()` in
  particular likely needs an event-to-listeners map, not a flat list).
- `ResourceMetadata::defaultSort()` direction as a validated constant
  (`ASC`/`DESC`) instead of any string.
- Watching the contract-to-implementation ratio: roughly 20-30% contracts
  and infrastructure vs. 70-80% real implementations is a healthy range
  while the core is still young. If interfaces, abstract classes, and
  registries keep growing without real implementations using them, that's
  a sign the architecture has started growing by anticipation.

## Why this page exists

Elo is being built in the open, and its architecture was shaped through an
unusually thorough process before any implementation code was written.
This log exists so anyone following along doesn't need to read every commit
to understand where things stand, it's a shortcut, not a replacement for
the real documentation.

## How to follow more closely

- Star or watch this repository for updates.
- The [ADRs](docs/adr) explain every architectural decision and the reasoning behind it.
