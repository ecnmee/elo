🇬🇧 English | 🇵🇹 [Português](../pt/ADR-005-elo-authorization.md)

# ADR-005 - Authorization (draft)

**Status:** Proposed, not yet accepted, written for review before any code
**Date:** 2026-09-09
**Depends on:** ADR-001 (Architecture), ADR-002 (Philosophy), ADR-004 (Relation Field, precedent for how this ADR is structured)

---

## 1. Context

Elo has no authorization concept anywhere today. Every registered
Resource is visible in `Navigation`, every route resolves for every
visitor, every row action renders for every record. This was fine
while the demo had no login screen to speak of, it stops being fine
the moment a second real user role exists.

Raised as two distinct concerns, correctly kept apart by whoever raised
them:

> "gerir" (managing, day to day) vs "autorizar" (deciding who is
> allowed to)

Concretely, that splits into two different surfaces:

- **Navigation-level.** Should this person see `Customers` in the
  sidebar at all, `viewAny`, in Laravel Policy vocabulary.
- **CRUD-level.** Given this person can see `Customers`, can they
  create one, edit this specific one, delete that one, `create`/
  `update`/`delete` per record.

Both are missing. Neither is a small addition, this ADR treats it with
the same weight ADR-004 gave `BelongsTo`, open questions listed and
closed one at a time, no code before the shape is settled.

## 2. Decision drivers

- **Don't invent what Laravel already solved.** Every Laravel developer
  already knows Gates and Policies, `viewAny`/`view`/`create`/`update`/
  `delete` is not Elo's vocabulary to reinvent, it is the framework's.
  ADR-002's own rule, don't build a worse version of a solved problem
  to avoid looking like a copy.
- **`CoreBoundaryTest` still applies.** The core only depends on
  itself, Illuminate, and Livewire. Laravel's authorization layer
  (`Illuminate\Auth\Access`) is already inside that boundary, a
  third-party permissions package would not be, this alone settles
  "build on Laravel's Gate/Policy system, don't add a dependency" as
  the only option actually available without a `CoreBoundaryTest`
  violation.
- **Don't conflate with `Action::visibleWhen()`.** That method already
  exists, and already hides a row action per record, `reactivate`
  doesn't show for an already-active `Product`. That is a business-rule
  question, driven by the record's own data. "Can this actor delete
  this record" is an actor-driven question, a different axis entirely.
  The two will often need to combine (AND together) to decide whether
  a button finally renders, but naming them the same thing would hide
  that they answer different questions, worth keeping visibly separate
  in the API even where the render logic composes them.

## 3. Shape

Two surfaces, two different hooks, both consulting Laravel's own
`Gate` under the hood:

### 3.1 Navigation-level: `viewAny`

`Navigation::groups()` already builds its list from
`ResourceLocator::all()`, one loop, one place. It gains a single
check per Resource:

```php
if (Gate::denies('viewAny', $resource->repository()->modelClass())) {
    continue;
}
```

Requires `Repository` to expose the model class it's backed by,
`EloquentRepository` already knows it (constructor argument), a small,
real, non-speculative addition, the same kind `Repository::table()`
already was.

### 3.2 CRUD-level: `view`/`create`/`update`/`delete`

`ResourceController`'s three actions (`index`, `create`, `edit`) each
gain one `Gate::authorize()` call before rendering, `create` checks
`create`, `edit` checks `update` against the loaded record, `index`
checks `viewAny` (the same check `Navigation` uses, a person shouldn't
reach a route by URL that the sidebar already hid).

`ResourceTable`'s Edit/Delete links, and any row `Action`, gain a
per-record authorization check composed with (not replacing)
`Action::visibleWhen()`, resolved in §4 below: **`Action` stays actor-
unaware, `ActionRunner` becomes the actor-aware party.**

```php
Action::make('reactivate')
    ->visibleWhen(fn ($record) => $record['status'] !== 'active');
    // still exactly this, no second argument, no authorization here,
    // ActionRunner checks the Gate separately, before calling the handler
```

### 3.3 What the Gate actually authorizes against

Elo's own data flows as plain arrays everywhere (`Repository::find():
?array`, every record `ResourceTable`/`Action` ever sees is an array),
Laravel Policies are conventionally written against the Eloquent Model
(`update(User $user, Post $post)`), passing Elo's array straight to
`Gate::authorize()` would silently break any Policy written the normal
way. Resolution: `Repository` gains `findModel($id): ?object`, narrow,
used only by the authorization call site (`ActionRunner`,
`ResourceController`), the rest of Elo's contract (Fields,
`ResourceTable`'s rows, `Action` handlers) stays array-based,
unchanged. Not a general-purpose escape hatch back to Models, one
method, one caller, kept that narrow on purpose.

## 4. Open questions

### 4.1 Resolved: how authorization attaches to a custom `Action`

**`Action` does not become actor-aware. `ActionRunner` does.**

`ActionRunner` already sits at the one place `action` and `record`
already converge before the handler runs, adding `actor` there is
completing a shape already half-built, not inventing a new one:

```php
$runner->run(
    action: $action,
    record: $record,
    actor: $actor, // ?Authenticatable, null for a guest, Gate::forUser(null) already handles that
);
```

Internally, `ActionRunner` resolves the record's model via
`Repository::findModel()` (§3.3) and calls `Gate::forUser($actor)
->authorize($action->id(), $model)` before invoking the handler, never
after. This protects a direct call to `ActionRunner`, not only the UI
button, `visibleWhen()` only ever decided whether the button renders,
it was never the actual boundary.

`visibleWhen()` keeps its exact existing shape and meaning, one
argument, the record, a business-rule question. Authorization is a
second, separate question, answered by the Gate, asked by
`ActionRunner`, never folded into `visibleWhen()`'s callback. The two
will often need to combine to decide whether a button finally shows
(hidden if either says no), that composition happens where the button
renders, not by merging the two questions into one hook.

Deliberately not built yet: any `ActionContext`/`ActorContext` object
bundling actor+record+action together. `ActionRunner::run()`'s three
named parameters are enough for what's known today, a context object
is exactly the kind of abstraction ADR-003 already warns against
building before a second real shape proves it's needed.

### 4.2 Still open

- **Policy discovery.** Laravel auto-discovers a Policy from a Model
  class by naming convention. Does Elo rely on that entirely (a
  Resource with no discoverable Policy simply has no authorization,
  matching Laravel's own default-open behavior), or does `Resource`
  gain an explicit `->policy()` declaration on `ResourceMetadata` for
  the cases naming convention won't reach? Leaning toward relying on
  Laravel's own discovery first, adding an override only once a real
  Resource's Policy can't be found by convention, the same "ship the
  honest subset" precedent `Number` and `BelongsTo` both followed.
- **What happens with no Policy at all.** A Resource whose Model has no
  Policy registered, `Gate::denies()` returns `false` (allowed) by
  Laravel's own default, meaning an unauthenticated demo with no
  Policies configured keeps behaving exactly as it does today, no
  regression, but worth stating explicitly rather than leaving it to
  be discovered by surprise.
- **Bulk actions.** `ResourceTable`'s bulk actions run against every
  selected id. Authorization presumably needs to check every record,
  not just the first, what happens when some are authorized and others
  aren't, silently skip the unauthorized ones, or fail the whole batch?
  Not designed here.
- **API/Export.** ADR-004 §3 already named these as future consumers of
  a Field's declaration. The same is true here, whatever shape
  authorization takes needs to make sense for a future API layer too,
  not be reworked when one arrives. Not designed here, flagged so the
  eventual API ADR inherits the constraint.

## 5. What this ADR is not deciding

- Row-level/multi-tenant authorization (a user seeing only their own
  Orders, not all Orders) is a different, larger problem, `Gate`
  policies can express it but Elo isn't building anything Multi-tenant-
  specific here, out of scope.
- Roles, permission groups, anything resembling a UI for managing who
  has which permission. Laravel's Gate/Policy layer is the mechanism,
  a Resource's own Policy class decides the actual rules, Elo supplies
  neither roles nor a role-management screen.
- The "master vs dev" idea, a tier that locks which customization
  options a non-developer can change, raised alongside this same
  request. That is closer to a settings/configuration-authorization
  question than a data-authorization one, likely the same underlying
  mechanism once this lands, not designed together with it, tracked
  separately in `PROGRESS.md`.

## 6. Status

Proposed, one question resolved. §4.1 is closed, `Action` stays actor-
unaware, `ActionRunner` becomes the actor-aware party, §3.3's
`findModel()` is what makes that resolution actually work against
ordinary Laravel Policies. The three remaining items in §4.2, Policy
discovery, the no-Policy default, and bulk actions, don't block each
other and don't block implementation the way §4.1 did, they can be
settled alongside the first real code, `API/Export` stays a flagged
constraint, not a blocker.
