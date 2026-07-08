🇬🇧 English | 🇵🇹 [Português](PROGRESS.pt.md)

# Development Progress

A running, public log of where Elo stands. Full technical detail lives in
[`docs/adr/`](docs/adr); this page is the plain-language version, updated
as things move forward.

---

## Current stage: design system

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
  The palette is derived from the Elo logo itself (navy-tinted neutrals,
  the same accent blue) rather than a generic admin-panel default, and the
  type pairing (IBM Plex Sans/Mono) was chosen for a technical, data-dense
  tool rather than a marketing page. Dark mode is supported both
  automatically and via a manual override, and reduced-motion preferences
  are respected.

**Next up:**

- The first real Field (`Text`), end to end, the first component built on
  top of the tokens above.
- `PostResource` built end to end on top of the seven contracts.

**Deferred, tracked, not implemented:**

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
