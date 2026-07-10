🇬🇧 English | 🇵🇹 [Português](../pt/introduction.md)

# Introduction

This guide documents Elo as it actually exists today, not as it will exist
once finished. It grows with every real step of implementation, the same
way [PROGRESS.md](https://github.com/ecnmee/elo/blob/main/PROGRESS.md)
tracks development at a glance. If something isn't in this guide, it isn't
built yet, check PROGRESS.md for what's next.

## What exists right now

- The seven public contracts: `Module`, `Resource`, `Blueprint`, `Field`,
  `Action`, `Layout`, `Repository`.
- One real Field type: [`Text`](fields.md).
- One real Repository implementation: `EloquentRepository`.
- The design tokens (`tokens.css`) and base panel stylesheet (`elo.css`).

## What doesn't exist yet

- A working admin panel you can visit in a browser. `ResourceForm` (the
  Livewire component that will actually render a form) hasn't been built.
- Routing, so there's no `/admin` (or similar) URL to point at.
- `elo:sync`, so migrations still need to be written by hand.
- Any Field beyond `Text` (`RichText`, `Image`, and the rest are next).

If you're evaluating Elo for a real project today, it's not ready for
that yet, this guide exists for people following the build, testing pieces
in isolation, or contributing.

## Where to go next

- [Concepts](concepts.md), the seven ideas Elo is built from, explained
  plainly.
- [Fields](fields.md), how to define and use the one Field that exists.
- The [architecture decision records](../adr), for the reasoning behind
  every design choice, not just what it does.
