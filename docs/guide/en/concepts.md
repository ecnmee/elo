🇬🇧 English | 🇵🇹 [Português](../pt/conceitos.md)

# Concepts

Elo is built from seven public concepts. This page explains them plainly;
[ADR-003](../adr/en/ADR-003-elo-language.md) is the precise, frozen
definition if you need it.

## The hierarchy

```
Module → Resource → Blueprint → Field / Layout / Action
```

A **Module** groups related Resources (a Blog module might hold Post,
Category, Tag). A **Resource** describes one manageable entity (a Post). A
**Resource**'s **Blueprint** is its structure: which Fields it has, how
they're laid out, what Actions it supports.

## Field: a piece of data

A **Field** is a single piece of data on a Resource, a title, a body, a
price. It has its own identity (`id`), separate from where it's actually
stored (`attribute`), they're the same by default:

```php
use Ecnmee\Elo\Fields\Text;

Text::make('title');                              // id and attribute are both "title"
Text::make('seo_title')->attribute('title');       // different id, same column
```

See [Fields](fields.md) for everything `Text` (the only Field that exists
today) can do.

## Layout: where a Field appears, not what it is

A **Layout** decides where a Field is shown, it never contains the Field
itself, only references it by id. This means the same Field can appear in
more than one place without being defined twice. (Layout doesn't have a
concrete Field to point at yet, since `ResourceForm` isn't built, this
becomes concrete once it is.)

## Action: something the user can trigger

An **Action** is a user-initiated operation, publish, duplicate, export.
Not built into any real workflow yet, but the contract exists and is
tested.

## Repository: where the data actually lives

A **Resource** doesn't talk to Eloquent directly, it talks to a
**Repository**. Today, that's `EloquentRepository`, wrapping a single
Eloquent model:

```php
use Ecnmee\Elo\Repositories\EloquentRepository;

public function repository(): Repository
{
    return new EloquentRepository(Post::class);
}
```

This indirection is what will eventually let a Resource be backed by
something other than Eloquent, without changing anything else about how
it's defined.
