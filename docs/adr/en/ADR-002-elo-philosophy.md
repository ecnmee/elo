🇬🇧 English | 🇵🇹 [Português](../pt/ADR-002-elo-filosofia.md)

# ADR-002 - Elo Philosophy

**Status:** Accepted
**Date:** 2026-07-04
**Depends on:** ADR-001 (Elo Architecture)

ADR-001 answers "how". This ADR answers **"what Elo is, and what it will
never be"**. The goal is to protect the framework's identity over the
coming years of evolution, this document is the reference to consult
whenever a future decision seems reasonable in isolation but risks
steering the project away from its original purpose.

---

## 1. Elo is

- A **framework for website management**, not a generic tool.
- **Declarative**, the developer describes the domain, Elo builds the operation.
- **PHP-first**, no essential feature depends on writing JS on the consumer's side.
- **Laravel-native**, uses Laravel's own conventions and mechanisms (service container, package discovery, migrations) instead of reinventing them.
- **Extensible by composition**, Blueprints compose, modules install as packages.
- **Convention-driven, but configurable**, default behavior covers most cases, without ever locking the consumer into it.

## 2. Elo is not

- A **page builder** (visual page-layout editing).
- A **website builder** (it doesn't generate the site, it manages the data of an already-built site).
- A **WordPress-style visual builder**.
- A **generic low-code** tool (the target is content/data management for sites, not any application).
- An **ERP**.
- A **monolithic CMS** (it's a package installed site-by-site, not a central platform).
- A **Laravel replacement**, it is built on top of Laravel and assumes it as a dependency, never hides it.

Any future proposal that pushes Elo into one of these categories should be
rejected by definition, regardless of the isolated technical merit.

---

## 3. Architectural principles

### 3.1 The developer describes the domain. Elo builds the rest.

Founding principle (ADR-001, section 1).

### 3.2 Convention over configuration, never over customization.

```php
Text::make('title');   // works immediately, default behavior
```

```php
Text::make('title')
    ->renderer(CustomRenderer::class)
    ->validator(CustomValidator::class);   // full customization, always available
```

The same applies at the Resource level: replacing a specific Resource's
`TableComponent`, `FormComponent`, or `Controller` is always possible. The
default behavior covers most cases; the consumer is never locked into it.

### 3.3 Two layers: frozen contracts, replaceable implementations.

This is the formalization of "everything is replaceable", with the
boundary that makes it consistent with ADR-001, section 5.

**Layer 1, Contracts (frozen, define what Elo is):**
`Module`, `Resource`, `Blueprint`, `Field`, `Action`, `Layout`, `Repository`.
Changing the public signature of any of these requires a major version
(ADR-001, D8). Replacing one of these contracts with something else isn't
"extending Elo", it's building a different framework.

**Layer 2, Implementations behind the contracts (replaceable via the
service container, the same way Laravel does, Cache driver, Queue driver):**

```
Repository          -> EloquentRepository (replaceable: Redis, external API, ...)
Renderer             -> BladeRenderer (replaceable)
MigrationWriter      -> default implementation (replaceable)
BlueprintCompiler    -> default implementation (replaceable)
RichText Driver      -> tiptap (replaceable: CKEditor, Monaco, ...)
```

The practical rule: if it's on the list of frozen contracts, it doesn't get
swapped, what gets swapped is what's *behind* it.

### 3.4 The core never knows about third-party modules. It's the module that knows the core.

Direct consequence of D2/D3 (ADR-001), made an explicit principle to
prevent the future temptation of adding exceptions for a specific module.

**Mechanical implication, no exceptions:** the core never contains code
like `if (class_exists('EloGallery\GalleryModule'))`, nor any form of named
special case. Any capability a third-party module needs from the core has
to be a generic extension point (discovery, Events), never a one-off
integration.

### 3.5 The golden rule of the core.

> If a feature only benefits one specific module, it does not enter the
> core. It only enters when at least two independent modules need it, or
> when it represents a cross-cutting capability of the framework.

Keeps the core small and coherent. Applies to every proposal to add
something to `elo/src/`, including contributions from the founding team
itself.

---

## 4. How to use this document

Whenever a future technical decision is sound but raises doubt about
whether "this is still Elo", the question to ask is:

1. Is it on the list in section 2 ("what it is not")? Reject, no exception.
2. Does it touch one of the seven frozen contracts? Requires a major
   version, evaluate with the same rigor as ADR-001.
3. Is it a new implementation behind an existing contract? Accept freely,
   it's the expected behavior of the system (section 3.3).
4. Does it only benefit one module? Apply the golden rule (section 3.5):
   it stays out of the core until there's a second real case.
