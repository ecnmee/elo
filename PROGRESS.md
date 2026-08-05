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

**Next up:**

- The `elo:sync` artisan command itself, wiring `SchemaSnapshot`,
  `SchemaDiff`, and `MigrationWriter` together against every registered
  Resource's Repository. Once this lands, `elo:sync` stops being tested
  parts and becomes the command someone would actually run.

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
