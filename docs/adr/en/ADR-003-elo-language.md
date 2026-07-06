🇬🇧 English | 🇵🇹 [Português](../pt/ADR-003-elo-linguagem.md)

# ADR-003 - Ubiquitous Language

**Status:** Accepted
**Date:** 2026-07-04
**Depends on:** ADR-001 (Architecture), ADR-002 (Philosophy)

ADR-001 answers "how". ADR-002 answers "what it is and isn't". This ADR
answers a different question: **what Elo calls each concept**, and
guarantees that answer never varies between code, documentation, and
conversation.

> Elo is a declarative framework for website management, built on Laravel.

This sentence sums up the three ideas that run through all three ADRs,
*framework* (not a CMS), *declarative* (not a CRUD generator), *built on
Laravel* (not a competitor to it), and should open the official
documentation.

---

## 1. The seven public concepts

```
Module - Resource - Blueprint - Field - Layout - Action - Repository
```

Nothing else exists as a first-class public concept. These should never
exist, and never will, as concept names: `Entity`, `Model` (in Elo's
sense, the Eloquent Model still exists as an implementation), `Widget`,
`Screen`, `Panel`, `FormDefinition`, `Schema`, `PageDefinition`. If one of
these terms appears in a future proposal, it's either a synonym for
something that already has a name (reject), or a genuinely new concept
that needs to justify its entry into this list (same treatment as a new
contract, ADR-001, D8).

## 2. One name, one meaning

| Term | Always is | Never is |
|---|---|---|
| **Resource** | the manageable entity | Controller, page, API endpoint |
| **Blueprint** | a Resource's structure (fields+layout+actions) | Schema, Definition, Configuration |
| **Field** | a data element | Input, Component, Widget |
| **Layout** | visual structure, no data logic | a place for validation or hydration |
| **Action** | user-initiated behavior | Event, Job, Command (Laravel's) |
| **Module** | the highest-level domain unit | Package (the Composer package is the distribution medium; Module is the Elo concept inside it) |
| **Repository** | data access contract | a generic third-party Eloquent Repository |

**Synonym rule:** if a concept already has an official name, that's the
only name used in code, tests, artisan commands, error messages, and
documentation. Two names for the same thing never coexist.

## 3. Supporting vocabulary (not public concepts, but recurring terms)

- **Driver**, a replaceable implementation behind a Field or behind
  ADR-002's Layer 2 (e.g. `RichText::make('body')->driver('tiptap')`). Same
  meaning Laravel already uses for Cache/Queue drivers, not a new Elo
  coinage, deliberately reused.
- **Compiled Blueprint**, the output of the `BlueprintCompiler` (internal,
  ADR-001 section 3.3). The Elo consumer never interacts with this directly.
- **Sync engine** (`SchemaSnapshot`, `SchemaDiff`, `Operations`,
  `MigrationWriter`), **internal** vocabulary of the `elo:sync` engine
  (ADR-001, D7). "Schema" here refers to database schema, a different
  domain from "Blueprint", and therefore not a reintroduction of the term
  banned in section 2. The developer never writes these classes; they only
  see the result (the generated migration file).

## 4. Retroactive correction to ADR-001

`ResourceDefinition` renamed to **`ResourceMetadata`**, the name
`Definition` collided with the explicit ban in section 2 of this ADR
("Blueprint is never Definition"), even though it describes a different
concept (label/icon/navigation vs. structure). The risk of confusion for
someone reading the code for the first time was real enough to justify the
correction before a single line of implementation existed.

## 5. Practical consequence

All documentation, examples, and tutorials use exactly these terms, always.
It never happens that one tutorial calls something `Resource` and another
calls it `Entity` or `Model`. This consistency is what makes frameworks
like Laravel, Symfony, or React recognizable year after year, and it's a
deliberate decision, not an accident of whoever wrote the first version of
the documentation.

---

## 6. From here on

The three ADRs (Architecture, Philosophy, Language) are closed. There are
no more architectural decisions to make by speculation, the next
architectural decision is only born from a real case found during
implementation.

Implementation sequence:

1. Repository (`ecnmee/elo`).
2. Project quality: PHPStan, Pint, Pest, CI.
3. Empty public contracts: `Module`, `Resource`, `Blueprint`, `Field`,
   `Action`, `Layout`, `Repository`.
4. First architecture test, e.g. a Pest Arch/Deptrac test that fails CI if
   any class in `Elo\` directly references a specific module's namespace,
   making the "the core never knows about third-party modules" principle
   (ADR-002, section 3.4) verifiable.
5. Design system (`tokens.css`).
6. First Field (`Text`).
7. `PostResource` end to end.
8. `elo:sync` engine.
