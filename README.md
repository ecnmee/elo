🇬🇧 English | 🇵🇹 [Português](README.pt.md)

<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset=".github/assets/logo-dark.png">
    <source media="(prefers-color-scheme: light)" srcset=".github/assets/logo-light.png">
    <img alt="Elo" src=".github/assets/logo-light.png" width="280">
  </picture>
</p>

# Elo

**A declarative framework for website management, built on Laravel.**

> Every website repeats the same work: login, dashboard, uploads, pages,
> forms, SEO, menus, users, settings. Elo exists to eliminate that
> repetition.
>
> We don't want to replace Laravel. We don't want to build another CMS.
> We want the developer to describe the website's domain and keep writing
> Laravel, while Elo automatically builds the repetitive infrastructure
> around it.

## Installation

```bash
composer require ecnmee/elo
```

*(Package under active development, not yet published on Packagist.)*

## Core concepts

```
Module → Resource → Blueprint → Field / Layout / Action
```

You describe a domain through seven public concepts, `Module`, `Resource`,
`Blueprint`, `Field`, `Layout`, `Action`, `Repository`, and Elo builds the
management interface, the API, and the corresponding migrations.

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

## What Elo is, and isn't

Elo is a framework: declarative, PHP-first, Laravel-native, extensible by
composition. It is not a page builder, not a website builder, not a
monolithic CMS, not a Laravel replacement.

## Documentation

This repository is the public, single-package distribution of Elo, split
from the [`ecnmee/elo-monorepo`](https://github.com/ecnmee/elo-monorepo)
development monorepo, where core development, architecture documentation
(ADR-001/002/003), and issue discussion happen.

## Development progress

Following along? See [PROGRESS.md](PROGRESS.md) for a plain-language,
regularly updated log of where the project stands.

## Contributing

Core development happens in the [monorepo](https://github.com/ecnmee/elo-monorepo).
See [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Code of Conduct

See [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## License

MIT, see [LICENSE](LICENSE).
