🇬🇧 English | 🇵🇹 [Português](README.pt.md)

<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset=".github/assets/logo-dark.png">
    <source media="(prefers-color-scheme: light)" srcset=".github/assets/logo-light.png">
    <img alt="Elo" src=".github/assets/logo-light.png" width="280">
  </picture>
</p>

<p align="center">
  <strong>Stop building the same admin panel for every website.</strong>
</p>

<p align="center">
  <a href="PROGRESS.md">Follow the build</a> ·
  <a href="docs/adr">Read the architecture</a> ·
  <a href="#installation">Installation</a>
</p>

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-%3E%3D8.2-777bb4">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-11%20%7C%2012-ff2d20">
  <img alt="License" src="https://img.shields.io/badge/license-MIT-blue">
  <img alt="Status" src="https://img.shields.io/badge/status-in%20development-yellow">
</p>

---

## The problem

Every Laravel website you ship needs the same things: a login, a dashboard,
CRUD for pages and content, forms, uploads, SEO fields, menus. You've built
this before. You'll build it again on the next project, from scratch,
because every admin panel generator either locks you into someone else's
UI, or gives you generic CRUD that doesn't fit how your client's site
actually thinks about its content.

**Elo is not another CRUD generator.** You describe your site's domain in
plain PHP, using seven concepts, `Module`, `Resource`, `Blueprint`, `Field`,
`Layout`, `Action`, `Repository`, and Elo builds the management interface,
the API, and the database migrations around it. Your CSS. Your Laravel
app. No black box.

```php
class PostResource extends Resource
{
    public function blueprint(): Blueprint
    {
        return Blueprint::make()
            ->fields([
                Text::make('title')->required(),
                RichText::make('body'),
                Image::make('cover'),
            ])
            ->layout([
                Section::make('content')->fields(['title', 'body']),
            ]);
    }
}
```

That's a full CRUD screen, validation, and (soon) an auto-generated
migration, from about ten lines you actually own.

## Why it's different

| | Generic admin panel | Page builder / WordPress-style | **Elo** |
|---|---|---|---|
| UI framework | Locks you into theirs | Locks you into theirs | Your own CSS, plain Livewire |
| What it manages | Any model, generically | Visual page layout | Your site's actual domain, declared in PHP |
| Where it lives | Separate product | Separate platform | A Composer package, one per project |
| Extending it | Plugins, if supported | Themes/plugins ecosystem | Compose Blueprints, ship modules as Composer packages |
| Escape hatch | Often none | Rarely | Every layer is a swappable Laravel binding |

Elo isn't trying to replace Laravel, or become a CMS. It's the missing
piece between "I described my domain" and "there's a working admin panel
for it", nothing more, nothing less. See [what Elo is, and isn't](docs/adr/en/ADR-002-elo-philosophy.md)
for the full boundary.

## Installation

```bash
composer require ecnmee/elo
```

*(Package under active development, not yet published on Packagist, see
[PROGRESS.md](PROGRESS.md) for exactly how close it is.)*

## Built in the open, architecture-first

Before writing implementation code, the full architecture was worked
through and frozen across three public ADRs, hierarchy and contracts,
philosophy and boundaries, official vocabulary, all in [`docs/adr/`](docs/adr).
Every core class ships with tests that verify the public contract, and CI
fails automatically if the core ever ends up depending on a specific
third-party module. If you want to see the reasoning behind every decision,
not just the result, it's all there to read.

## User guide

Documentation of what actually exists today, growing with every real step
of implementation, no tutorials for features that don't exist yet: see
[`docs/guide/`](docs/guide/en/introduction.md).

## Development progress

Following along? See [PROGRESS.md](PROGRESS.md) for a plain-language,
regularly updated log of where the project stands, no need to read every
commit.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for how to report issues and request
features.

## Code of Conduct

See [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## License

MIT, see [LICENSE](LICENSE).

---

<p align="center">
  If this approach to admin panels resonates with you, a star helps more people find it while it's still being built.
</p>
