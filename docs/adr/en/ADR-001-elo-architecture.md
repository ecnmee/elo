🇬🇧 English | 🇵🇹 [Português](../pt/ADR-001-elo-arquitetura.md)

# ADR-001 - Elo Architecture (final)

**Status:** Accepted, foundational contracts frozen
**Date:** 2026-07-04
**Composer package:** `ecnmee/elo`
**Namespace:** `Ecnmee\Elo`

**Note on the name:** there is a brand conflict with the Elo payment card
(Bradesco/BB/Caixa, ~140M cards issued in Brazil). Risk assessed and
knowingly accepted, the package operates in a completely different domain
(PHP/Laravel tooling), and the name is kept for its conceptual clarity
("link" between domain, panel, and front-end).

---

## 1. Context

Every website built from scratch repeats the same work: login, dashboard,
uploads, pages, forms, SEO, menus, users, settings. Elo exists to eliminate
that repetition.

Elo **is not a CRUD generator**. It is a website management platform: the
developer describes the site's domain and Elo builds the management
interface, the API, and, in a controlled way, the corresponding migrations.

> **Philosophy:** the developer describes the website's domain. Elo builds everything else.

Requirements that guided the decisions:
- The panel's own CSS, with no dependency on a third-party UI kit (Livewire + plain Blade).
- **Site-by-site** installation (one package per project, not a central multi-tenant).
- Third-party modules as **independent Composer packages**, a priority since v1.
- `elo:sync` (migration generation from Fields) included since v1.

---

## 2. Central architectural principle

> **Every Blueprint element (Field, Action, Layout) has its own identity
> (`id`) and can be referenced by it. Data persistence (`attribute`) is a
> concept separate from the element's identity.**

This principle is what makes the Blueprint a **graph**, not a tree: the
same Field exists exactly once and can be referenced from multiple
Layouts, Toolbars, or contexts, without duplication.

```
Blueprint
│
├── Fields    { page_title, body, cover }
├── Actions   { publish, duplicate }
├── Layouts   { content, seo }
│
└── References
    content, page_title, body
    seo, page_title      (same Field, different context)
```

**Omission rule:** by default, `attribute = id`. `Text::make('title')`
remains trivial, you only declare `->attribute()` when the `id` diverges
from the persistence column (SEO title, alias, computed field).

```php
Text::make('title');                                    // id=title, attribute=title
Text::make('seo_title')->attribute('title');              // distinct id, same attribute
Text::make('display_name')->computed(fn ($r) => ...);       // no attribute, computed field
```

---

## 3. Central hierarchy

```
Module
  └── Resource
        └── Blueprint (graph, immutable, compilable)
              ├── Layout   { id, references Fields/Actions by id }
              ├── Field    { id, attribute, lifecycle, context }
              └── Action   { id, context, bulk }
```

### 3.1 Module

The highest-level domain unit. Responsible for everything that belongs to
its domain. Methods beyond `resources()` and `menu()` are **optional**,
with an empty default implementation in the base class.

```php
class BlogModule extends Module
{
    public function resources(): array
    {
        return [PostResource::class, CategoryResource::class, TagResource::class];
    }

    public function menu(): array
    {
        return [MenuItem::make('Blog')->icon('post')->resource(PostResource::class)];
    }

    public function policies(): array { return []; }
    public function widgets(): array { return []; }
    public function commands(): array { return []; }
    public function listeners(): array { return []; }
    public function routes(): array { return []; }
}
```

*Note registered, not implemented in v1:* a `Domain` level between `Module`
and `Resource` (grouping Resources by business aggregate within a large
module) does not yet have sufficient justification. Organizing by
subfolders (`app/Elo/Modules/Shop/Catalog/ProductResource.php`) already
solves the file-organization case without needing a formal class. `Domain`
is only introduced if a real module appears that needs its own behavior per
aggregate (grouped menu, per-aggregate policies), not just visual grouping.

### 3.2 Resource

Describes a manageable entity. Metadata in `definition()`; structure
(Fields + Layout + Actions) in `blueprint()`.

```php
class PostResource extends Resource
{
    public static function definition(): ResourceMetadata
    {
        return ResourceMetadata::make()
            ->label('Post')
            ->pluralLabel('Posts')
            ->icon('post')
            ->navigationGroup('Blog')
            ->searchColumns(['title'])
            ->defaultSort('created_at', 'desc');
    }

    public function blueprint(): Blueprint
    {
        return Blueprint::make()
            ->fields([
                Text::make('title')->required(),
                Slug::make('slug')->from('title'),
                RichText::make('body'),
                Image::make('cover'),
                Boolean::make('published')->default(false),
            ])
            ->layout([
                Section::make('content')->fields(['title', 'body']),
            ])
            ->actions([
                PublishAction::make('publish'),
                DuplicateAction::make('duplicate'),
            ])
            ->uses(SeoBlueprint::class, MediaBlueprint::class); // composition
    }
}
```

**Data source decoupled from Eloquent.** The Resource depends on a
`Repository` contract, not necessarily on an Eloquent Model. In v1 only
`EloquentRepository` exists; the contract stays open for `RedisRepository`,
`ApiRepository`, etc. when a real case arises.

```php
interface Repository
{
    public function find(int|string $id): ?array;
    public function query(): RepositoryQuery;
    public function save(array $attributes): array;
    public function delete(int|string $id): bool;
}
```

### 3.3 Blueprint

The full structure of a Resource, fields, layout, and actions, as an
immutable, composable graph.

- **Immutable:** every method (`fields()`, `layout()`, `actions()`,
  `uses()`) returns a **new instance**. Necessary for safe composition (the
  same `SeoBlueprint` used in `PostResource` and `ProductResource` can
  never suffer shared mutation) and to allow safe caching across requests.
- **Composable:** `->uses(SeoBlueprint::class, MediaBlueprint::class)`
  merges Fields/Actions/Layouts from other Blueprints, avoiding duplicating
  recurring fields (SEO, media, address) in every Resource.
- **Compilable:** the `BlueprintCompiler` resolves composition (`uses()`),
  applies the `attribute = id` omission rule, and **validates the graph**:
  - **Duplicate ID** within the same Blueprint, compile-time error.
  - **Dangling reference** (a Layout/Toolbar points to a non-existent `id`), compile-time error.

  Compilation is not a parallel caching system, it is invoked by the same
  `elo:cache` already planned for Modules/Resources. A single cache
  surface in the framework.

> Name deliberately chosen to differ from `Schema` to avoid colliding with
> `SchemaSnapshot`/`SchemaDiff` from the sync engine, different concepts
> (UI/composition structure vs. SQL table structure).

### 3.4 Layout

Its own base class, alongside `Field` and `Action`, the third first-class
citizen. Has identity (`id`) and **references Fields/Actions by id, never
containing the instances directly**. This guarantees a Field exists exactly
once in the Blueprint; the Layout only decides where and how it's presented.

```
Layout
  ├── Section
  ├── Tabs
  ├── Grid
  ├── Card
  └── Column
```

```php
Section::make('content')->fields(['title', 'body']);   // reference by id
Tabs::make('seo')->fields(['seo_title']);
```

**v1:** `Section`, `Tabs`, `Grid`, `Column`, `Card`.
**v1.1:** `Accordion`, `Wizard`.
**Out of v1:** `Repeater` (nested data, a different category of problem,
deserving its own ADR).

### 3.5 Field

Has identity (`id`) separate from persistence (`attribute`, defaulting to
`id`). Every Field:

1. Knows the **column type** it needs (used by `elo:sync`).
2. Knows its own **validation rules**.
3. Knows how to **render itself** (its own Livewire/Blade component).
4. Participates in the Resource's **lifecycle**.
5. Is **context-aware** of where it's being used.

```php
Text::make('title')
    ->required()
    ->beforeSave(fn ($value) => trim($value))
    ->afterSave(fn ($model, $value) => Cache::forget("post:{$model->id}"))
    ->hydrate(fn ($model) => $model->title)
    ->dehydrate(fn ($value) => Str::limit($value, 255))
    ->hiddenOnIndex()
    ->readonlyOnEdit()
    ->requiredOnCreate();
```

Supported contexts: `index`, `detail`, `create`, `edit`, `api`, `export`.

**Rich editors as Field drivers, not separate Plugins:**

```php
RichText::make('body')->driver('tiptap');
Text::make('location')->driver('google-maps');
```

There is no `PluginRegistry` in v1.

**Internal implementation pattern (not part of the public contract).** The
base `Field` class internally delegates to four collaborators with simple
defaults: `FieldRenderer`, `FieldValidator`, `FieldType`, `FieldHydrator`.
Simple Fields use the defaults; complex Fields override only what they
need. Since it doesn't affect the public API, it can evolve freely without
breaking compatibility.

### 3.6 Action

Own identity (`id`), context-aware just like Field, referenceable from
Layouts/Toolbars by id.

```php
Action::make('publish')
    ->handle(fn ($record) => $record->update(['published' => true]))
    ->visibleOn(['index', 'detail'])
    ->bulk();
```

---

## 4. Infrastructure decisions

### D1, Discovery of local Modules (from the project)

Scan of `app/Elo/Modules/*` on application boot, cached via
`php artisan elo:cache`. Config can exclude/disable modules per project.

### D2, Discovery of third-party Modules (Composer packages)

Native Laravel Package Discovery (`extra.laravel.providers`). In the
module's provider `boot()`:

```php
public function boot(): void
{
    Elo::registerModule(GalleryModule::class);
}
```

`composer require vendor/elo-gallery` installs it; `composer remove`
uninstalls it. Module versioning is independent from the core.

### D3, Communication between Modules

Modules don't know each other directly. Data relations via Field
(`BelongsTo::make('author', TeamResource::class)`); reaction between
modules via native Events (`ResourceCreated`, `ResourceUpdated`,
`ResourceDeleted`).

### D4, Front-end consumption

Single public API: everything goes through `elo()`.

```php
elo()->resource('posts')->published()->get();
elo()->settings();
elo()->menu('main');
elo()->form('contact');
```

Global shortcuts (`setting()`, `menu()`, `form()`) internally call the same
service, they are not a parallel API. REST API is opt-in per module
(`Module::api()`).

### D5, Elimination of Controllers and Routes

A generic controller (Livewire component) resolves the Resource by the
route slug. Routes are generated in a loop on `EloServiceProvider` boot.

### D6, Design System (prerequisite, before the first Field)

Panel CSS custom properties defined before any Livewire component:
spacing, radius, elevation, typography, animations, states, icons, dark mode.

### D7, `elo:sync` generates migrations, it does not alter the database directly

```
Fields (via compiled Blueprint) -> SchemaSnapshot -> SchemaDiff -> Operations -> MigrationWriter
```

`MigrationWriter` translates operations into a real Laravel migration file
(`up()`/`down()`) in `database/migrations/`, **it never touches the database**.

```
Changed the Blueprint (fields) -> php artisan elo:sync -> migration generated
-> I review it -> git commit -> php artisan migrate
```

No `--dry-run`/`--force`/interactive confirmation, safety comes from the
Laravel workflow itself (git review + manual migrate). `down()` is
generated automatically from the inverse operation.

Third-party modules declare the table's base migration the traditional
Laravel way; `elo:sync` only generates the incremental evolution of Fields.

### D8, Versioning and compatibility

Strict semver. Major = contract change in `Field`, `Action`, `Layout`,
`Blueprint`, `Resource`, `Module`, or `Repository`. Third-party modules
declare compatibility via normal Composer constraints.

---

## 5. Frozen contracts

As of this revision, the following contracts are **frozen**, changing
their public signature requires a major version bump (D8):

- `Module`
- `Resource`
- `Blueprint`
- `Field`
- `Action`
- `Layout`
- `Repository`

plus the architectural principle from section 2 (identity separate from persistence).

---

## 6. Core package structure

```
elo/
├── src/
│   ├── EloServiceProvider.php
│   ├── Module.php
│   ├── Resource.php                  (definition() + blueprint())
│   ├── Blueprint.php                 (immutable graph: fields/layout/actions)
│   ├── BlueprintCompiler.php         (resolves uses(), validates IDs/references)
│   ├── Layout.php                    (base class) + Section/Tabs/Grid/Column/Card
│   ├── Field.php                     (id, attribute, lifecycle, context)
│   ├── Action.php                    (id, context, bulk)
│   ├── Repository.php
│   ├── EloquentRepository.php
│   ├── Facades/Elo.php
│   ├── Console/Commands/
│   │   ├── SyncCommand.php           (elo:sync)
│   │   └── CacheCommand.php          (elo:cache, includes Blueprint compilation)
│   ├── Sync/
│   │   ├── SchemaSnapshot.php
│   │   ├── SchemaDiff.php
│   │   ├── Operations/
│   │   └── MigrationWriter.php
│   ├── Http/
│   │   ├── Controllers/ (ResourceController, Api/ResourceApiController)
│   │   └── Livewire/ (ResourceTable, ResourceForm, Fields/, FormBuilder)
│   ├── Registry/ (ModuleRegistry, ResourceRegistry)
│   └── Models/ (Media, FormSubmission, EloUser)
├── resources/
│   ├── views/livewire/...
│   └── css/ (tokens.css, elo.css)
├── database/migrations/              (internal tables)
├── config/elo.php
└── routes/elo.php
```

---

## 7. Next steps

> Order revised in ADR-002: public contracts are the framework's
> foundation, a poorly designed signature requires a major version bump;
> CSS can evolve freely without breaking compatibility. That's why
> contracts come before the design system.

1. Create the repository (`ecnmee/elo`).
2. Set up CI, static analysis, tests, and code style.
3. Implement the empty contracts: `Module`, `Resource`, `Blueprint`,
   `Field`, `Action`, `Layout`, `Repository` (public signature closed,
   minimal body, no business logic yet).
4. Define the **design system** (`tokens.css`).
5. Implement the first reference Field (`Text`), validating id/attribute,
   lifecycle, and context on top of the defined tokens.
6. Build `PostResource` end to end: listing, creation, editing,
   `elo()->resource('posts')` on the front-end.
7. Implement the `elo:sync` engine, tested on `PostResource`, confirming a
   valid and reversible Laravel migration.
8. Extract `SeoBlueprint`/`MediaBlueprint`, validating composition and the
   `BlueprintCompiler` validations (duplicate ID, dangling reference).
9. Extract `elo-gallery` as a separate Composer package, validating D2.
