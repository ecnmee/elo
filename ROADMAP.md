# Roadmap

High-level, public view of where Corven is headed. For the full architectural reasoning behind each stage, see the ADRs in the private development monorepo (referenced from internal docs — not all architecture decisions are public yet).

| Stage | What ships | Status |
|---|---|---|
| v1 | Design tokens + atomic utilities + components + layouts (5-layer static core, no build tooling required to consume) | **Layers 1–4 closed** (tokens, utilities, components, layouts). Layer 5 (pages) not started. |
| v2 | JIT compiler (scans your templates, generates only the CSS you use) + component composition syntax | Planned |
| v3 | Lazy loading of CSS by feature domain | Planned |
| v4 | Full CLI (pattern analysis, build explain mode, performance budgets) | Planned |
| v5+ | Project diagnostics command, dependency graph, browser DevTools inspector, official templates | Planned |

## Current focus

Layers 1 through 4 (Tokens, Utilities, Components, Layouts) are closed: each compiles cleanly, has zero duplicate rules, has automated compilation and visual regression tests, and has a live example. See [UPDATES.md](./UPDATES.md) for the development log.

Next: Layer 5 (Pages) — reconstructing real page examples using only the layers above. No timeline is published yet — quality gates come before dates.
