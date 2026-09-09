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
- `ModuleRegistry` and `ResourceLocator`, closing D1/D2. Third-party
  Modules register through `elo()->registerModule()` (D2); `Elo::resource()`,
  `ResourceController`, and `SyncCommand` no longer each do their own
  `Config::get("elo.resources.{$slug}")`, all three now read through
  `ResourceLocator`, one place instead of three. `Resource::slug()`
  joined too, a Module declares resources as a plain list, something has
  to turn a class-string into a routable slug, it defaults to the class
  name convention (`PostResource` -> `posts`), overridable, the same
  idiom `Field::attributeName()` already uses against `attribute()`.

**Next up:**

Still building the real demo application (`Product`, `Service`,
`Customer`, `Order` on a `BusinessModule`). Two real, unplanned findings
so far, both fixed directly, not deferred: Laravel 13 wasn't supported,
`illuminate/support` was pinned to `^11.0|^12.0`, widened to
`^11.0|^12.0|^13.0`, plus `orchestra/testbench` to `^9.0|^10.0|^11.0` for
the matching test toolchain. Then `elo:sync` itself, generating a single
migration file for every table changed in one run, readable at four
Resources, not at real scale (unreviewable diff, no per-table git blame,
no per-table rollback). Fixed: `SyncCommand` now writes one migration per
table, `SchemaSnapshot`, `SchemaDiff`, `Operation`, and `ColumnDefinition`
untouched, only `SyncCommand` and `MigrationWriter` (a new optional
`$timestamp` parameter on `write()`) changed. Then the most serious
finding yet: `CreateTable::up()` never generated an `id()` column,
`Schema::create()` doesn't add one on its own, so every table `elo:sync`
had ever generated had no primary key at all. Every read still "worked",
`SELECT *` doesn't need a primary key, but every record came back missing
`id`, which broke `ResourceTable`'s `wire:key` outright the first time a
real record existed to render. No test caught it because every existing
test built its own table by hand with `$table->id()` already there, never
through a real `CreateTable::up()` call. Fixed: `id()` is now
unconditional, first line, every time. Two more findings from actually
using the demo, not just building it: `ResourceForm::save()` completed
silently, no redirect, no visible feedback, a Livewire request finishing
with nothing to show for it reads as "nothing happened". `ResourceTable`
had Delete and whatever custom Actions a Resource declared, but no way
back into a record once it existed, no Edit link at all. Both fixed:
`save()` redirects to the Resource's index route now, every row links to
its edit page. No relationship Field type yet (`Order` can't declare
"belongs to Customer"), still open, tracked below. Also tracked, not built: a
branded confirm dialog instead of the browser's native one, icon buttons
(SVG, not an icon font, per explicit preference) instead of text, and
navigation, `Module::menu()` has existed as a hook since the beginning
with nothing rendering it yet, the demo made that concrete for the first
time without yet changing its priority. One more real gap surfaced by
actually clicking through the demo: `discontinue`/`reactivate` and
`archive`/`unarchive` both showed on every Product regardless of its
current status, `reactivate` on an already-active Product makes no
sense. `Action` had no way to answer "is this Action shown for this
specific record", only `isVisibleOn()`, a static per-context question
(index/create/edit). Fixed with a new, orthogonal method,
`Action::visibleWhen(callable $condition)`, evaluated per row in
`ResourceTable`. Last, from the same click-through: text-only buttons
read as noisy once several sat side by side, and the table itself had no
answer for a narrow screen, it just stayed a wide table. Fixed: `Action`
gained an optional `icon(string $svg)`, raw SVG, falls back to the text
label when unset; `ResourceTable`'s Edit and Delete are icon-only now,
unconditionally. Below 768px the table becomes a stack of cards, one per
record, via CSS alone (`data-label` on each cell, the same markup serves
both layouts, nothing duplicated). No relationship Field type yet
(`Order` can't declare "belongs to Customer"), and a branded confirm
dialog instead of the browser's native one, both still open, tracked
below.

**In progress: ADR-005, Authorization, opened for review, not accepted**

- `docs/adr/en/ADR-005-elo-authorization.md` (and the PT mirror)
  opened as a proposal, treating "gerir" (managing) and "autorizar"
  (deciding who is allowed to) as the two distinct surfaces raised
  earlier: navigation-level (`viewAny`, hides a Resource from the
  sidebar entirely) and CRUD-level (`view`/`create`/`update`/`delete`
  per record).
- Recommends building directly on Laravel's own `Gate`/Policy layer,
  not a new permissions system, `CoreBoundaryTest` (core only depends
  on itself, Illuminate, Livewire) makes this close to the only option
  available without adding a dependency the core boundary test would
  reject.
- Explicitly kept separate from `Action::visibleWhen()`, that hook is
  data-driven (does this record's state make the action make sense),
  authorization is actor-driven (is this user allowed to), the two
  will often compose but naming them the same thing would hide that
  they answer different questions.
- Five open questions recorded, the first (how a custom `Action`
  becomes actor-aware without quietly duplicating `visibleWhen()`)
  blocks starting anywhere else, everything downstream depends on that
  shape. Policy discovery, the no-Policy-registered default, bulk
  action authorization, and API/Export compatibility are the other
  four, none answered yet.
- The "master vs dev" configuration-lock idea, raised alongside this
  same request, explicitly kept out of this ADR, flagged as likely the
  same underlying mechanism once authorization lands, not designed
  together with it.
- No code written. `src/Fields`, `ResourceController`, `ResourceTable`,
  `Navigation` all wait for §4 to close, same discipline ADR-004 was
  held to before `BelongsTo.php` existed.

**Just shipped: real logo assets, replacing the AI-placeholder ones**

- Logo sizing closed out, final values: image height `1.2rem`, wordmark
  `calc(var(--elo-text-sm) * 1.8)`, `.elo-nav__logo` padding matching
  `.elo-topbar`'s (`--elo-space-3`/`--elo-space-6`). Settled on explicit
  literal values instead of compounding percentages partway through,
  each further adjustment was harder to reason about than just naming
  the size wanted.
- Deliverable zips now carry a version suffix (`elo-monorepo-v1.zip`,
  incrementing from here), several rounds of logo tweaks used
  descriptive-but-unordered names, no way to tell which was newest at
  a glance.

- `logo-dark.png`/`logo-light.png` replaced entirely, source is the
  actual brand mark now, not the AI-generated placeholder used since
  the branding pass. Processed programmatically: background made
  transparent, autocropped to the mark's own bounding box (the
  leftover whitespace baked into the source PNG, not CSS `gap`, was
  the real cause of the logo/wordmark spacing looking off no matter
  how far the CSS gap was reduced), `logo-dark.png` (white lettering,
  for the navy sidebar) recolored from the ink color to white per
  pixel while leaving the blue accent stroke (`#3B36FC`-ish) untouched,
  `logo-light.png` keeps the original navy ink, both otherwise
  identical.
- `.elo-nav__logo`: vertical padding reduced from matching
  `.elo-topbar`'s (`--elo-space-3`) down to `--elo-space-1`, per
  request. `border-bottom` removed entirely, no border-right existed
  on this element to remove (that border belongs to `.elo-nav` itself,
  the sidebar/content divider, left alone, flagged rather than guessed
  at).
- `.elo-table`'s outer `border` removed, `border-radius` +
  `overflow: hidden` stay, so the rounded-corner clipping still works,
  just without a visible stroke around it.

**Just shipped: footer, and a process fix on my side**

- Logo scaled back down 25% from the previous `+15%/+25%/+25%` step,
  it read as too large once actually seen rendered. The gap between
  the logo and the "Studio" wordmark also tightened, `--elo-space-2`
  down to `--elo-space-1`, per request, to sit closer together.
- A second process gap, same shape as the `view:clear` one above: the
  commands given for the footer round left out `vendor:publish
  --tag=elo-assets`, so the app kept serving the previously-published
  `elo.css` no matter how many times `view:clear` ran, `view:clear`
  only touches compiled Blade templates, not published package
  assets, they're two separate steps and both are needed after any
  CSS change. The standard sequence from here on:
  `composer update ecnmee/elo` → `vendor:publish --tag=elo-assets
  --force` → `view:clear`, every time, not trimmed down when a change
  looks CSS-only.

- New `.elo-footer`, copyright line + Terms/Privacy (placeholder links,
  same honesty as the topbar's search/language/user chrome, no real
  pages exist for them) + a real Documentation link, straight to
  `github.com/ecnmee/elo`. `.elo-shell__main`'s existing `flex: 1 1
  auto` pushes it to the bottom of the page on its own, no sticky-footer
  trick needed.
- Process note, not a code change: a `php artisan view:clear` instruction
  got buried inside a parenthetical in prose instead of given as its
  own command block, the person ran `cache:clear` instead (a different
  cache entirely, doesn't touch compiled Blade views) and kept seeing
  stale HTML. Every command from here on gets its own block, nothing
  load-bearing left to prose.

**Just shipped: branding pass, a real dark-mode bug, and demo icons**

- Real bug found and fixed: `.elo-panel[data-theme='dark']` only matched
  a panel carrying the attribute directly, `ResourceTable`/`ResourceForm`
  each wrap their own content in their own `.elo-panel` (so they stay
  embeddable standalone), so the manual toggle on the outer shell never
  reached those nested panels, the unconditional base `.elo-panel` rule
  kept re-asserting light values on them regardless of the toggle. This
  is what made the table/pagination/Create button ignore dark mode.
  Fixed with one added selector, `.elo-panel[data-theme='dark'] .elo-panel`,
  covering any nesting depth. The automatic (`prefers-color-scheme`)
  path never had this bug, only the manual override did.
- `elo.css`'s `<link>` now carries a `?v={mtime}` query string, computed
  from the published file's own last-modified time. Browsers were
  caching the stylesheet aggressively enough that several rounds of
  visual changes in this session needed a manual hard-refresh to even
  see, this removes that step permanently, every `vendor:publish` that
  actually changes the file busts the cache on its own.
- Logo enlarged, a wordmark ("Studio") added beside it, always the
  white-lettering `logo-dark.png` variant now, not swapped by
  `prefers-color-scheme` (a leftover from before the sidebar background
  became a fixed brand navy in both themes, the OS-preference swap no
  longer meant anything once that background stopped changing with the
  theme). Sized up further, compounding to `+15%/+25%/+25%` over the
  original (an earlier `+50%` step was tried and reverted, it made the
  logo taller than `.elo-topbar`, judged too large once the header row
  padding was made to match the topbar's own, `.elo-nav__logo` no
  longer forces a fixed height to stay level with the topbar,
  `.elo-nav__logo`'s padding now equals `.elo-topbar`'s exactly, per
  request, this does mean the two rows no longer line up in height,
  the logo row is now taller by design, an intentional trade-off, not
  an oversight). `.elo-nav__body` is new, holding the padding the
  groups still need, separate from the header strip now.
- Light-mode chrome: `.elo-nav`/`.elo-topbar` now use the brand navy
  (`#07132F`, the same ink the logo itself uses) instead of white,
  hardcoded in `elo.css` as a deliberate brand choice, not a themed
  token, both dark-mode blocks in `tokens.css` reset it back to the
  ordinary dark surface tokens, so dark mode is unaffected.
- `partials/topbar-user.blade.php` extracted from the layout, on
  purpose kept generic ("Guest", empty avatar) in the package. A
  specific person's name and photo are per-project content, not
  framework content, baking one developer's identity into the shipped
  default would mean every other project installing Elo sees a
  stranger in their own admin panel. `demo.elo` overrides it via
  Laravel's own package view-override convention
  (`resources/views/vendor/elo/partials/topbar-user.blade.php`), not a
  new mechanism built for this.
- Demo's four Resources gained `->icon()`. Confirmed previously that
  `Navigation` already reads `getIcon()`, no framework code needed,
  only content the demo had never declared.

**Still evaluated, not built:** confirmed dark/light mode already
follows the browser/OS preference automatically (`prefers-color-scheme`),
overridden by the manual toggle once used, nothing new needed there.
Search and filters on `ResourceTable` remain the same tracked, not
designed, item from the previous entry, no table in the demo is close
to needing them yet.

**Just shipped: navigation polish, logo, real dark mode toggle, two real bugs fixed**

- Two real bugs, found by actually looking at the rendered demo, not by
  test coverage: white margin around `.elo-shell` (the browser's default
  8px `body` margin, never reset, elo.css deliberately never touches
  `body`/`html` since a Composer package doesn't own the host document,
  fixed instead inside `layouts/app.blade.php` itself, which does fully
  own its own page). `.elo-nav__link:hover` used
  `var(--elo-color-neutral-100)`, a raw scale value never remapped for
  dark mode (only semantic tokens are), so hover stayed light-colored
  regardless of theme, clashing with dark-mode text. Fixed to
  `var(--elo-color-surface-raised)`, already theme-aware.
- Elo's own logo now renders in the sidebar, dark/light swapped via
  `<picture>`, same technique the READMEs already use. Published
  through the existing `elo-assets` `vendor:publish` tag,
  `EloServiceProvider::publishes()` gained a second entry,
  `resources/images` alongside `resources/css`, same destination, same
  tag, one command still publishes everything.
- A real, working dark mode toggle button in a new topbar. Not a new
  feature invented today, tokens.css was explicitly built for this
  already ("a future theme toggle needs no new tokens, only a way to
  set the [data-theme] attribute"), this is that toggle finally
  arriving. Persists via `localStorage`, applies before first paint to
  avoid a light-then-dark flash.
- Search input, language button, and a user avatar/name also sit in the
  topbar, explicitly static, `disabled`, and commented as such in the
  Blade file. Requested as a visual mock, built as exactly that, nothing
  wired to a search engine, an i18n system, or a User model that
  doesn't exist, Elo doesn't own auth and shouldn't assume a shape for
  one.

**Evaluated, not built, tracked for a future ADR each:**

- **Full i18n (multiple UI languages).** No framework UI strings are
  even extracted for translation yet ("Previous", "Next", "Page X of
  Y", the empty-state message). Building support for a specific
  language count with no real translations behind it would be exactly
  the anticipation ADR-003 rules against. Worth doing once the
  framework's own handful of strings are inventoried, starting with the
  two languages this project already uses throughout (EN/PT), not an
  arbitrary count decided up front.
- **Nested sub-menus for large Resource counts.** No registered demo or
  package consumer has anywhere near enough Resources for
  `navigationGroup()`'s single level of grouping to fall short yet.
  Tracked, revisited once a real case hits that ceiling.
- **Menu-item icons.** Needs no new code at all, `Navigation` already
  reads `ResourceMetadata::getIcon()`, the demo's four Resources simply
  never call `->icon()`. A content gap, not a framework gap.
- **Authorization: who can see a menu item, who can perform a CRUD
  operation.** The single biggest item raised, correctly separated from
  plain navigation ("gerir" vs "autorizar" are different concerns).
  Deliberately not improvised into `Navigation`, this is ADR-005
  territory on its own, deserving the same five-open-questions
  treatment ADR-004 got before any code: policy shape, where a check
  runs (Field, Action, route middleware, all three), how it composes
  with `Action::visibleWhen()`, which already exists for a different
  reason and might be the wrong place to bolt permissions onto.
- **A "master" vs "dev" access tier that locks which customization
  options a non-developer can change.** Raised alongside authorization,
  and likely the same underlying feature rather than two, a permission
  system already answers "who can change this setting" once it exists.
  Building a second, parallel mechanism first would mean redoing it
  once the real authorization ADR lands.
- **Exportable, reusable Elo configuration across projects.** No second
  project exists yet to prove what "reusable" should even mean here.
  Tracked, not designed.
- **A backup system built into Elo itself.** Flagged as likely out of
  scope for what Elo is, a declarative admin panel builder, not a
  backup tool. `spatie/laravel-backup` already solves this for any
  Eloquent-based Laravel app, Elo or not, building an equivalent inside
  `Ecnmee\Elo` would also fail `CoreBoundaryTest` (the core only depends
  on itself, Illuminate, and Livewire) unless it reached for a
  third-party package the core has no business depending on.
- **Table filters and search fields on `ResourceTable`.** Real, likely
  future work, explicitly opt-in per the request ("a critério do dev").
  No table in the demo is anywhere near large enough yet to need it.
  Tracked.

**Just shipped: sidebar navigation, `Navigation`, built from Resources already declared**

- `Ecnmee\Elo\Navigation::groups()` builds the whole sidebar straight
  from every registered Resource's own `ResourceMetadata`,
  `getLabel()`/`getPluralLabel()`/`getIcon()`/`getNavigationGroup()`,
  four getters that existed since `ResourceMetadata` was written but had
  no consumer until now, the same kind of dead-until-used gap `Number`
  closed for price and `BelongsTo` closed for relations. Nothing new to
  declare: the four demo Resources already call `->navigationGroup('Business')`,
  the nav groups them correctly with zero changes to `demo.elo`.
- `Module::menu()` stays exactly as it was, still unused, still
  available for a future link that genuinely isn't tied to a Resource,
  no such case exists yet (ADR-003, no ADR by anticipation), so
  `Navigation` doesn't consult it.
- Real trigger, not anticipation: PROGRESS.md had explicitly deferred
  this ("the demo may reveal we need navigation because we have four
  Resources"), and separately, this same session hit a person typing
  `/elo/product/create` by hand and getting "resource not registered",
  the exact friction a sidebar exists to remove.
- New shared layout, `layouts/app.blade.php`, `pages/table.blade.php`
  and `pages/form.blade.php` now `@extends` it instead of each
  duplicating the full `<html>` document. `partials/navigation.blade.php`
  renders the groups, highlighting the current Resource's link.
  `.elo-nav`/`.elo-shell` CSS added to `elo.css`, same token variables
  and BEM naming every other component already uses, including the
  768px card-stack precedent's same responsive breakpoint.
- Groups render in registration order (config first, then Modules),
  the same "insertion order, not sorted" precedent `ModuleRegistry`
  already set, not alphabetically. A Resource without `navigationGroup()`
  renders under no heading at all, not an invented literal label like
  "General" nobody declared.

**Just shipped: `elo-demo-app` gets a real README, logo, and a fixed em dash**

- `elo-demo-app` had the stock, never-touched `laravel/laravel` README
  until now. New `README.md` (English, default) and `README.pt.md`
  (Portuguese), same logo and language-toggle convention `elo`/
  `elo-monorepo` already use, `.github/assets/logo-{dark,light}.png`
  copied over from those two repos rather than redrawn, one brand,
  three repositories.
- The README documents current state, not history: the four Resources
  (`Product`, `Service`, `Customer`, `Order`), every Field type in use
  today (`Text`, `Number`, `BelongsTo`), and calls out `Order` as the
  first real `BelongsTo` usage outside the package's own tests. `BUILD.md`
  (Portuguese, the original step by step build log) stays as history,
  explicitly not the file kept current.
- Flagged, not fixed: `composer.json`'s `path` repository entry for
  `ecnmee/elo` is a hardcoded local Windows path, committed as-is to a
  now-public repo, anyone else cloning it can't `composer install`
  without editing that line first. Documented in the README's Getting
  Started section as a known step, real fix is publishing `ecnmee/elo`
  to Packagist and replacing the path repository with a version
  constraint, tracked, not solved here.
- One real em dash found in a shipped file, `resources/views/fields/belongs-to.blade.php`'s
  empty-option placeholder, `—` in the `<option>`, replaced with a plain
  `-`. Checked every file this session touched, `PROGRESS.md`/
  `PROGRESS.pt.md`/ADR-004 were already clean, this was the only one.
- Going forward: documentation is treated as living, updated in the
  same change as the code it describes, not written once. This applies
  across all three repositories, `elo-demo-app`'s README (what the demo
  currently shows), `elo`'s guide/ADR docs (the public architecture
  record), and `elo-monorepo`'s `PROGRESS.md`/`CONTRIBUTING.md` (the
  private, developer-facing build log), each already following this
  pattern to varying degrees, now named explicitly as the standard, not
  left implicit.

**Just shipped: `SyncCommand` describes `AddForeignKey` correctly**

- Console output was `[orders] change orders` for the new foreign key,
  `[orders] add column to orders` for the column, surfaced running
  `elo:sync` against `OrderResource` for real (see the entry above).
  `SyncCommand::describeOperation()` has a `match` naming each Operation
  kind, `AddForeignKey` was added to the codebase alongside `BelongsTo`
  but never given a case here, silently falling through to the generic
  `default => 'change'`. Fixed: `AddForeignKey => 'add foreign key to'`,
  matching `CreateTable`/`AddColumn`'s existing pattern exactly. The
  migration file itself was always correct, this only fixed the label
  printed while writing it.
- New test asserts the exact line, `[test-articles] add foreign key to
  test_articles`, so a future Operation added without a `describeOperation()`
  case falls through to `'change'` loudly, in a failing test, not
  silently in someone's terminal months later.

**Just shipped: `OrderResource` declares real relations, closing the loop**

- The demo's `OrderResource` now declares `BelongsTo::make('customer')
  ->resource(CustomerResource::class)` and `BelongsTo::make('product')
  ->resource(ProductResource::class)`, replacing nothing (`reference`,
  `status`, `total` all stay), adding the two relations the very first
  roadmap decision (`Product -> Customer -> Order`, at the top of this
  file) named as the real test of the architecture.
  `Order::$fillable` gained `customer_id`/`product_id`, `EloquentRepository::save()`
  goes through `fill()`, a Field a Resource declares but the underlying
  Eloquent model doesn't allow mass-assignment for is a silent no-op,
  not an error, worth remembering for the next Resource that adds a
  BelongsTo to an existing model.
- Next `php artisan elo:sync` on `demo.elo` writes a real `AddColumn` +
  `AddForeignKey` migration pair for each relation, the first time
  `AddForeignKey` runs outside a test.
- This was the last piece "Deliberately not done yet" from the previous
  entry. ADR-004 is now exercised by a real application Resource, not
  only package tests, `OrderResource` can finally declare "belongs to
  Customer" the way ADR-001 D3 always meant it to.

**Just shipped: `BelongsTo` wired into `ResourceForm`/`ResourceTable`**

- `ResourceForm::optionsById()`: every `BelongsTo` field's `<select>`
  gets its options from the related Resource's own `Repository::query()
  ->orderBy(displayAttribute)->get()`, ADR-004 §4.3, v1, no search or
  pagination yet. Fails loud, ADR-004 §3: throws `LogicException` if a
  related record doesn't have the declared `displayAttribute`, instead
  of rendering an empty label.
- `ResourceForm::rules()`: a `BelongsTo` field's validation rule gains
  `|exists:{table},id` on top of required/nullable, closing the
  "validation is the easy part" note from ADR-004 §4 (the original
  open-questions draft).
- `ResourceTable::displayValues()`: every `BelongsTo` column resolves to
  the related record's display value, not the raw foreign key, via one
  `whereIn()` per column against the related Resource's own Repository,
  batching every row on the current page in a single query, ADR-004
  §4.2. Same fail-loud rule as `optionsById()`. A dedicated test asserts
  exactly one query against the related table per page, not one per
  row, proving the N+1 the ADR was written to avoid doesn't happen.
  Neither `resource-form.blade.php` nor `resource-table.blade.php`
  needed to know `BelongsTo` exists, both just consume `$options`/
  `$displayValues` the Livewire components already computed, the field
  view contract (`$field`, `$value`, `$context`, `$error`, `$wireModel`,
  now `$options`) stays the same shape every other Field already uses.
- New fixtures, `TestArticle`/`TestArticleResource`, a `BelongsTo` to
  `TestAuthorResource`, exercising the wiring end to end (form render,
  save, validation, table display, batching) without touching
  `TestPost`/`TestPostResource`, which several unrelated tests already
  assert exact output against.
- `OrderResource` in the demo still uses a free-text `reference` field,
  not yet switched to `BelongsTo::make('customer')`, that's an
  application-level change in `demo.elo`, not the package, tracked as
  the next step.

**Just shipped: `BelongsTo`, `AddForeignKey`, `RepositoryQuery::whereIn()`**

- `Ecnmee\Elo\Fields\BelongsTo`, the third real Field and the first
  whose value is another Resource's record. `BelongsTo::make('customer')
  ->resource(CustomerResource::class)->attribute('customer_id')
  ->displayUsing('name')`, exactly the syntax ADR-004 accepted.
  `attributeName()` overrides the base Field's default to derive
  `{id}_id` (`customer` -> `customer_id`), not the raw id, the only Field
  so far where that override earns its keep. `displayUsing()` optional,
  defaults to `'name'`. `->resource()` is required, not optional,
  `resourceClass()`/`foreignKeyDefinition()` throw `LogicException`
  immediately if read before it's declared, a missing relation target is
  a declaration bug, not a runtime default to paper over.
- `Field::foreignKeyDefinition(): ?ForeignKeyDefinition`, null by
  default, overridden only by `BelongsTo`. Lets `SchemaDiff` stay
  generic against `Field`, never needing to know `BelongsTo` exists as a
  concrete class, same contract-amendment pattern `Repository::table()`
  and `Field::attribute()` already used.
- `Sync\ForeignKeyDefinition`, a plain value object (column, referenced
  column, referenced table), and `Sync\Operations\AddForeignKey`, its
  own Operation, deliberately not folded into `AddColumn`, ADR-004 §4.1.
  Uses `foreign()->references()->on()`, not `constrained()`, since this
  operation only ever adds the constraint, never the column.
- `SchemaDiff` appends an `AddForeignKey` right after the column-creating
  operation (`CreateTable` or `AddColumn`) for any Field whose
  `foreignKeyDefinition()` isn't null. Additive-only policy unchanged: a
  `customer_id` column that already existed before becoming a
  `BelongsTo` never gets a constraint added after the fact, the exact
  type-change gap `Number` already surfaced, same known limitation, not
  solved differently here, confirmed by a new test.
  `MigrationWriter` needed no changes at all: its existing "reverse
  order" rule for `down()` already drops the constraint before the
  column when both are being removed, `AddForeignKey`'s `down()` simply
  lands after `AddColumn`'s in the reversed list, for free.
- `RepositoryQuery::whereIn()`, ADR-004 §4.2, added the same way
  `paginate()` joined the interface, once a real consumer needed it, not
  by anticipation, `RepositoryQuery` isn't in ADR-001 §5's frozen list.
  `EloquentRepositoryQuery` implements it via Eloquent's own `whereIn()`.
- `resources/views/fields/belongs-to.blade.php`, a `<select>`, following
  the same view contract `Text`/`Number` established, plus an `$options`
  list the view itself does not fetch, ADR-004 §4.3 left that to the
  caller for v1.
- New test fixtures, `TestAuthor`/`TestAuthorResource`, giving `BelongsTo`
  a real related Resource to point at in tests, mirroring
  `TestPost`/`TestPostResource`.
- **Deliberately not done yet:** wiring `BelongsTo` into
  `ResourceForm`/`ResourceTable`, populating `$options` from the related
  Resource's own Repository, batching `whereIn()` per page in
  `ResourceTable` instead of one query per row, resolving the display
  value. `OrderResource` still can't declare "belongs to Customer" end
  to end until that lands, tracked as the next step, kept separate on
  purpose, this change was already large enough to review as one unit.

**Just shipped: ADR-004 accepted, the Relation Field is fully specified**

- All five open questions closed. `docs/adr/en/ADR-004-elo-relation-field.md`
  (and the PT mirror) moved from Proposed to Accepted.
- Syntax confirmed: `BelongsTo::make('customer')
  ->resource(CustomerResource::class)->attribute('customer_id')
  ->displayUsing('name')`, `displayUsing()` optional, defaults to the
  literal `'name'` column, fails loud at compile/render time if absent
  and undeclared, never a silent blank cell.
- `elo:sync`: a `BelongsTo` produces `AddColumn` + a new `AddForeignKey`
  operation, using Laravel's `foreignId()->constrained()`, additive-only
  policy (D7) unchanged, `ColumnDefinition` stays constraint-unaware.
- N+1: `RepositoryQuery` gains `whereIn()`, same non-frozen-contract
  precedent `paginate()` already set, ships alongside `BelongsTo`, not
  deferred, `ResourceTable` batches one query per page instead of one
  per row.
- Select options: v1 loads every related record via the existing
  `Repository::query()`, no new mechanism, explicitly not the final
  answer at scale, tracked for later.
- `onDelete`: no API in v1, the FK ships with no `ON DELETE` clause,
  which is `RESTRICT`/`NO ACTION` by default in MySQL/Postgres/SQLite,
  safe by construction, no code needed. `cascade`/`setNull` tracked,
  not built.
- Cross-Module relations: resolved by the existing architecture, not
  new work, `Module` has no runtime boundary to cross, `BelongsTo`
  references a Resource by class-string.
- Deferred, on purpose: `ResourceMetadata::displayField()` (a Resource
  declaring its own default display attribute once), no real caller
  exists yet to confirm the concept is needed.
- Filament's `Select::relationship()` used as market reference for the
  UX shape, not copied, Elo's declaration lives on the Field, informing
  Form/Table/Sync from one source, instead of configuring on top of an
  Eloquent relationship method written first.
- Still no code. `src/Fields/BelongsTo.php`, `AddForeignKey`, and
  `RepositoryQuery::whereIn()` are next.

**Just shipped: `Customer` and `Service` filled out, validating the demo**

- Not new Resources, `CustomerResource` and `ServiceResource` already
  existed. This was the "validate, don't just add" step: fill both out
  to a realistic shape and see what friction the framework has left, per
  the plan of finishing `Number` before deciding on the relationship
  Field.
- `CustomerResource` gained `phone` (`Text`, optional) and `status`
  (`Text`, defaults to `'active'`), the same pattern `Product`/`Order`
  already established for status.
- `ServiceResource` gained `active` (`Text`, defaults to `'true'`).
  Surfaced a real gap doing it: there is no Boolean Field type yet, so
  `active` stores and syncs as a plain string column, `'true'`/`'false'`,
  not a real boolean. Same honest, not-worked-around pattern `price` used
  on `Text` before `Number` existed. Tracked below, not solved here on
  purpose, this alone isn't reason enough to build a whole Field type.
- No other friction surfaced. Two `Text` fields, a default, and a layout
  Section were enough for both, nothing about the demo's shape pushed
  back on the framework this time.

**Just shipped: the second real Field (`Number`)**

- `Ecnmee\Elo\Fields\Number`, a plain numeric input backed by a `decimal`
  column (Laravel's default precision/scale, 8 and 2), following the
  exact same contract `Text` already established: `type()`, view by
  convention (`elo::fields.number`), lifecycle, context, default. No new
  concept on `Field` was needed.
- Deliberately generic: not `Money`, `Currency`, `Integer`, or `Decimal`.
  Those are either business semantics (a currency, a rounding rule) or a
  precision/scale choice no caller has asked for, `Number` represents the
  data, nothing more, per ADR-003. `PROGRESS.md` had been watching
  whether the demo needed more than one numeric shape before building
  this, it never did, `price`/`total` all just need a number.
- The demo's `ProductResource`, `ServiceResource`, and `OrderResource`
  now use `Number` for `price`/`total` instead of `Text`. Running `php
  artisan elo:sync` afterward reported "already in sync", surfacing a
  real gap: `SchemaDiff` only ever compares column *presence*, never
  type, by design (ADR-001 D7/D8, additive-only), so a Field changing
  type on an already-synced column is silently invisible to it. The
  demo's `price`/`total` columns stay `string` in the database until
  someone writes a manual migration, tracked below.
- Guide docs (`docs/guide/en/fields.md`, `docs/guide/pt/campos.md`)
  updated to document both Field types side by side.

**Just shipped: icons and a responsive card layout for `ResourceTable`**

- `Action::icon(string $svg)`/`getIcon()`: optional, a raw SVG string
  rendered unescaped, the text label stays the fallback when no icon is
  set, so every existing Action keeps working exactly as it did.
- `ResourceTable`'s Edit and Delete links are icon-only now, a pencil and
  a trash glyph, `aria-label`/`title` carry the accessible name neither
  one shows visibly any more. Row and bulk Actions render their icon when
  one is set, their text label otherwise, both fully optional.
- Below 768px, `ResourceTable` renders as a stack of cards instead of a
  horizontally cramped table, pure CSS, no JS, no second template:
  `data-label` on each cell supplies the column name via `::before`, the
  exact same Blade markup renders both layouts.
- The demo's `ProductResource` and `OrderResource` now use `icon()` on
  every Action, via a small `app/Elo/Icons.php` in the demo app itself,
  not the framework, kept there deliberately so no Resource class needs
  a wall of inline SVG strings.

**Just shipped: `Action::visibleWhen()`, conditional per record**

- `Action` gained `visibleWhen(callable $condition)` and
  `isVisibleFor(mixed $record)`, deliberately separate from
  `isVisibleOn()`/context: a context check needs no record, a record
  check needs no context, conflating them would have made either one
  harder to reason about.
- `ResourceTable` evaluates it per row now, a Discontinue button only
  renders for records whose status makes discontinuing make sense.
- In the demo, this turned `archive`/`unarchive` from bulk Actions into
  row Actions, toggling one record's state is exactly what
  `visibleWhen()` is for, a genuine `cancel` bulk Action stayed on
  `OrderResource` as the real example of that shape.

**Just shipped: `ResourceForm` redirects, `ResourceTable` links to edit**

- `ResourceForm::save()` now redirects to `elo.index` for the Resource
  after a successful save. Before, saving completed with no page
  navigation and no visible network request (it's Livewire, there isn't
  one to watch), so a person using the form for the first time had no
  way to tell whether anything had happened.
- `ResourceTable` gained an "Edit" link per row, pointing at `elo.edit`.
  Delete already existed as a permanent, non-Action link; Edit was the
  other half of that and was simply missing, every row's Actions column
  led everywhere except back into the record itself.
- Both found by using the demo, not by building it, the difference
  between a Resource compiling and a Resource being usable.

**Just shipped: `CreateTable` now gives every table a primary key**

- `id()` was missing from every generated `CREATE TABLE`, an
  auto-incrementing primary key that Laravel's `Schema::create()` never
  adds unless something explicitly calls `$table->id()`, and nothing did.
  Every column a Resource declared, plus `timestamps()`, but never the
  one column nothing declares because it's assumed: the primary key.
- Found in the Business demo, not by a test: `/elo/products` threw
  `Undefined array key "id"` the moment a real `Product` record existed
  to render in `ResourceTable`, `wire:key="elo-row-{{ $row['id'] }}"`
  needs the key every record is supposed to have.
- Every existing SchemaDiff/MigrationWriter/CreateTable test had built
  its fixture table by hand, `Schema::create(..., function ($table) {
  $table->id(); ... })`, with the id column added outside the code under
  test, so nothing ever exercised a real `CreateTable::up()` call end to
  end against a fresh table and then read a record back out of it. Two
  new tests close that gap directly: one asserts `id()` is the first line
  generated, one actually inserts two rows through a migration
  `CreateTable` produced and confirms real, incrementing primary keys
  come back.

**Just shipped: one migration per table, not one per run**

- `SyncCommand` now groups the `Operations` it collects by table before
  handing them to `MigrationWriter`, one `write()` call per table instead
  of one for the entire run. Four Resources changing produces four
  files, `create_products_table`, `create_services_table`, and so on,
  not one `sync_elo_resources` file listing all four.
- `MigrationWriter::write()` gained an optional `$timestamp` parameter.
  `SyncCommand` passes a base timestamp plus one second per file, so
  filenames sort in the same deterministic order they were generated in,
  even when every file is written within the same wall-clock second, no
  `sleep()` involved.
- `SchemaSnapshot`, `SchemaDiff`, `Operation`, and `ColumnDefinition` are
  untouched, the grouping already existed inside `SchemaDiff` (one
  `CreateTable`, or a list of `AddColumn`, never mixed, per table), this
  only changed how `SyncCommand` hands that grouping to the writer.
- Found by building the Business demo, not anticipated: a single file
  for four tables was still readable, the reviewer's point was that it
  stops being reviewable, git-blameable, or individually rollback-able
  well before real scale, an organizational problem, not a performance
  one.

**Just shipped: `Module` discovery**

- `ModuleRegistry`: a singleton list of registered Module classes,
  `elo()->registerModule(GalleryModule::class)` is D2's entry point, a
  third-party package calls it from its own `ServiceProvider::boot()`,
  Laravel's native Package Discovery is what gets that `boot()` to run at
  all.
- `ResourceLocator`: the single place `config('elo.resources')` and every
  registered Module's `resources()` get combined into one slug map. A
  config entry wins on a collision, explicit beats convention, same rule
  `Field::attribute()` already follows against `attributeName()`.
  `Elo::resource()`, `ResourceController::resolve()`, and
  `SyncCommand::handle()` all read through it now, replacing three
  separate copies of the same `Config::get()` call, exactly the
  duplication flagged as a watch-item during the `ResourceTable` review,
  it became real the moment a second source of resources existed.
- `Resource::slug()`: defaults to the class name, minus a trailing
  "Resource", kebab-cased and pluralized. Every existing hand-registered
  Resource keeps working unchanged, config always wins regardless of what
  its class's default slug would be.
- D1 (scanning `app/Elo/Modules/*` on boot, cached via `elo:cache`) is
  not built. A local Module can already call `registerModule()` from the
  host app's own `AppServiceProvider::boot()`, that works today;
  filesystem scanning is a convenience on top of this mechanism, not a
  prerequisite for it, and doesn't have a real case pushing for it yet.

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

- **A relationship Field type, partially shipped.** `BelongsTo`,
  `AddForeignKey`, and `RepositoryQuery::whereIn()` exist now, see "Just
  shipped: `BelongsTo`, `AddForeignKey`, `RepositoryQuery::whereIn()`"
  above. What's still deferred: wiring it into `ResourceForm`/
  `ResourceTable`, `OrderResource` still uses a free-text `reference`
  field, cannot declare "belongs to Customer" end to end yet.
- ~~A numeric Field type.~~ Shipped, see "Just shipped: the second real
  Field (`Number`)" above.
- `SchemaDiff` type-change detection. Confirmed by shipping `Number`:
  changing a Field's `type()` on a column that already exists produces
  no operation, `elo:sync` reports "already in sync" and the column's
  real type is unchanged. Correct for the additive-only design as
  written (ADR-001 D7/D8: never alter, never drop), but it means a
  Field's declared type and the database's actual column type can now
  silently diverge, worth a real look once a second case surfaces, an
  `AlterColumn` Operation isn't a small addition on its own.
- A Boolean Field type. Surfaced filling out `ServiceResource.active`,
  see "Just shipped: `Customer` and `Service` filled out" above: `active`
  stores and syncs as a plain string (`'true'`/`'false'`), not a real
  boolean, no checkbox, no true/false semantics anywhere in the stack.
  One occurrence isn't enough to commit to a shape yet, `Product`/`Order`/
  `Customer` all model status as a multi-value `Text` column instead of a
  boolean, so this needs a second real case before building it.
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
