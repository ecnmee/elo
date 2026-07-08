# Development Updates

Public log of major milestones on the road to v1. Written for people following the project, not just contributors — less internal jargon, still honest about what's done and what isn't.

Development happens in a private monorepo. This file is updated when a real milestone closes, not on every commit.

---

## 2026-07-05 — Core layers 1–4 closed

Corven's Core (the static part of the framework — no build tool required to consume it) now has four layers, each closed against an internal quality bar: compiles clean with zero warnings, has automated tests, and has a live example in a local Playground.

- **Layer 1 — Design Tokens.** Colors (22 palettes), spacing, radius, shadow, typography, and a breakpoint scale — one source of truth, compiled to CSS Custom Properties (breakpoints are the one deliberate exception: they stay compile-time only, since `@media` can't read a CSS variable in its condition).
- **Layer 2 — Utilities.** 2,700+ atomic classes — one class, one CSS responsibility each. Documented automatically from the compiled CSS output, not hand-written docs that can quietly go stale.
- **Layer 3 — Components.** The first two, `c-btn` and `c-card`, built through a `compose()` mechanism that can only ever reference existing tokens — never arbitrary, hardcoded CSS.
- **Layer 4 — Layouts.** `l-container` (responsive across 5 breakpoints) and `l-section`, same token-only guarantee as Components.

Every layer is covered by automated tests: compilation checks, structural checks (for example, a component can never silently end up in the wrong CSS cascade layer), and visual regression tests for anything with its own visual identity — including the responsive behavior of the container, checked at both desktop and mobile widths.

One real bug was found and fixed by this test suite mid-development: a duplicate-class detector didn't understand `@media` nesting and flagged a responsive layout rule as a false duplicate of an unrelated utility. Caught before it reached anyone, exactly because the tests exist.

**Not done yet:** Layer 5 (Pages), the JIT compiler, the CLI, and the runtime are all still ahead — see [ROADMAP.md](./ROADMAP.md). There is still no installable package. Everything above lives in the private monorepo; nothing has shipped publicly yet.
