🇬🇧 English | 🇵🇹 [Português](../pt/ADR-004-elo-relacionamento.md)

# ADR-004 - Relation Field

**Status:** Accepted, ready for implementation
**Date:** 2026-08-22
**Depends on:** ADR-001 (Architecture), ADR-003 (Language)

---

## 1. Context

`OrderResource` cannot declare that an Order belongs to a Customer or a
Product. Confirmed twice now, once when the demo was first built
(`PROGRESS.md`), again validating `Customer`/`Service`: every Resource
built so far only needed scalar Fields (`Text`, `Number`). This is the
first Field whose value is another Resource's record, not a scalar, and
it touches every layer at once: column type, form rendering (a select,
not an input), validation (`exists` against another table), and read
(the table needs to show `Customer Name`, not `customer_id`).

ADR-001, D3 already commits to the shape at the architecture level:

> Data relations via Field (`BelongsTo::make('author', TeamResource::class)`)

That sentence was illustrative, not a specification. This ADR is that
specification.

## 2. Decision drivers

- **Consistency with `Field`.** `BelongsTo` is a `Field` subclass, the
  same contract `Text` and `Number` already implement (`type()`, `view()`,
  lifecycle, context). Per ADR-003, that means it is not a new public
  concept and needs no vocabulary entry, exactly like `Text`/`Number`.
- **Scope discipline.** ADR-001 §3.4 already deferred `Repeater` to its
  own ADR as "a different category of problem". A full relationship
  system (`BelongsTo`, `HasMany`, `HasOne`, `BelongsToMany`, polymorphic
  variants) is the same kind of category, this ADR proposes deciding
  `BelongsTo` only, the one the demo actually needs, and deferring the
  rest until a real Resource needs them.
- **No new infrastructure concept.** `Repository::query()` already
  exists (`where`, `orderBy`, pagination); a `BelongsTo` Field populating
  a select needs nothing more than calling the related Resource's own
  Repository, no new contract.

## 3. Naming and syntax

Two shapes were on the table:

```php
// A: attribute-first, explicit column
Relation::make('customer_id')
    ->resource(CustomerResource::class)
    ->displayUsing('name');

// B: relationship-first, id/attribute split (ADR-001 §2)
BelongsTo::make('customer')
    ->resource(CustomerResource::class)
    ->attribute('customer_id')
    ->displayUsing('name');
```

**Recommendation: B.** It is the syntax ADR-001 §D3 already sketched, and
it is the one that actually uses ADR-001 §2's central principle
(identity separate from persistence): the Field's `id` is `customer`, the
relationship as the Resource talks about it, `attribute` is `customer_id`,
the column, diverging exactly the way `Text::make('seo_title')
->attribute('title')` already diverges in ADR-001 §2's own example.
`Relation::make('customer_id')` collapses that distinction back into one
name, the thing ADR-001 §2 was written to avoid.

`->attribute()` keeps the existing omission rule: unset, it derives
`{id}_id` (`customer` -> `customer_id`), the common case stays as
trivial as `Text::make('title')` is today. Only declared when the column
genuinely diverges, same as every other Field.

`->displayUsing()` is optional, defaulting to the literal `'name'`
column on the related Resource, overridable
(`->displayUsing('company_name')`) when it diverges. This is a
convention, not a `ResourceMetadata` concept (a `displayField()` method
on `ResourceMetadata` was considered and explicitly deferred, see §7),
kept as a plain string default so the common case needs no extra
declaration, the same shape `Field::attribute()` already uses. Deferred
by design: nothing here is a copy of Filament's
`Select::relationship('customer', 'name')`, the declaration lives on the
Field, on the Resource/Blueprint/Field graph ADR-001 already committed
to, not on an Eloquent relationship method the person would otherwise
have to write first. **Fails loud, not silent:** if the related
Resource's records don't have a `'name'` key and no `->displayUsing()`
was given, this must raise a clear exception at Blueprint-compile time
or first render, never render a blank cell, a wrong silent default is
worse than requiring the explicit call.

## 4. Resolved decisions

Each open question from the original draft, closed below. Recorded with
its reasoning, not just its answer, so a future revision knows why.

### 4.1 `elo:sync` and foreign keys

A `BelongsTo` Field produces two operations, not one:

```
AddColumn(orders.customer_id)
AddForeignKey(orders.customer_id -> customers.id)
```

`AddForeignKey` is its own `Sync\Operations` class, `ColumnDefinition`
gains no knowledge of constraints, the same separation `CreateTable` and
`AddColumn` already keep from each other. The generated migration uses
Laravel's `foreignId('customer_id')->constrained('customers')`, not a
manual `unsignedBigInteger()` + `foreign()->references()->on()`, less
generated code for the identical result. The additive-only policy (D7)
holds unchanged: `elo:sync` only ever adds an `AddForeignKey`, it never
alters or drops one, exactly like every other operation today.

### 4.2 N+1 on `ResourceTable`

`RepositoryQuery` gains `whereIn(string $column, array $values): static`.
Not a new frozen contract, per ADR-001 §5 only `Repository` is frozen,
`RepositoryQuery` is not, and `paginate()` already joined this same
interface after the fact "once ResourceTable was the real consumer that
needed it, not by anticipation" (see its own docblock). `whereIn()` is
the same kind of addition, for the same reason: `ResourceTable` batches
every row's foreign key into one `whereIn()` call against the related
Resource's Repository, instead of one query per row. Ships in the same
change as `BelongsTo`, not deferred, `ResourceTable` is a real consumer
today, this is not infrastructure built for a hypothetical future one.

### 4.3 Where select options come from

v1 loads every record via the related Resource's own
`Repository::query()->orderBy(...)->get()`, no new mechanism. Correct
for the demo's scale, explicitly not the final answer for a Customer
table with 100k rows. Search/pagination on the select is tracked as a
future item, revisited only once a real Resource needs it, not designed
speculatively now.

### 4.4 `onDelete`

No `->onDelete()` API ships in v1. The generated foreign key carries no
explicit `ON DELETE` clause, which is `RESTRICT`/`NO ACTION` by default
in MySQL, Postgres, and SQLite with foreign keys enabled, the safe
behavior by construction, no code needed to get it. `->onDelete('cascade')`,
`->onDelete('setNull')` (which would also require the column to be
nullable) tracked as a future item, built only once a real Resource
needs one.

### 4.5 Cross-Module relations

Already resolved by the existing architecture, no new rule, no code.
`Module` is `resources()` + `menu()` and nothing else, there is no
runtime boundary between Modules to cross. `BelongsTo::resource()` takes
a `class-string<Resource>`, PHP autoloading resolves it the same way
regardless of which Module, if any, declares it.

## 5. What this ADR is not deciding

- `HasMany`, `HasOne`, `BelongsToMany`, polymorphic relations, out of
  scope, each is its own future ADR once a real Resource needs one.
- Nested/inline editing of the related record (creating a Customer from
  inside the Order form) is out of scope, `BelongsTo` selects an
  existing record only.
- Searchable/paginated select options, tracked, not designed (§4.3).
- `onDelete` policies beyond the database's own default, tracked, not
  designed (§4.4).

## 6. On Filament as a reference, not a specification

`Select::make('customer_id')->relationship('customer', 'name')` solves
the same problem well, and there is no merit in inventing a different
shape solely to be different. The difference that matters is where the
declaration lives: Filament configures a form component on top of an
Eloquent relationship method the developer writes first
(`public function customer(): BelongsTo`), Elo's declaration is the
relationship, on the `Resource`/`Blueprint`/`Field` graph ADR-001
already committed to, informing `ResourceForm`, `ResourceTable`,
`elo:sync`, and eventually API/Export from one source, without asking
for an Eloquent relationship method first. Filament is market validation
that this shape of problem has a good solution, a reference for
UX/behavior, not something to copy or to deliberately diverge from.

## 7. Explicitly deferred, not designed here

- `ResourceMetadata::displayField()`, a Resource declaring its own
  default display attribute once, instead of every `BelongsTo` pointing
  at it repeating `->displayUsing('name')`. Real ergonomic improvement,
  not built now: `BelongsTo` is not implemented yet, so there is no
  actual caller to confirm `ResourceMetadata` needs this concept. First
  real usage decides whether this earns its place.

## 8. Status

Accepted. `src/Fields/BelongsTo.php`, the `AddForeignKey` operation, and
`RepositoryQuery::whereIn()` are next.

