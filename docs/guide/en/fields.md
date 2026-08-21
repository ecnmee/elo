🇬🇧 English | 🇵🇹 [Português](../pt/campos.md)

# Fields

Two Field types exist today: `Text`, a single-line text input, and
`Number`, a plain numeric input backed by a `decimal` column. Everything
below works right now, backed by real tests, and applies to both, the
examples below use `Text`.

```php
use Ecnmee\Elo\Fields\Text;
use Ecnmee\Elo\Fields\Number;

Text::make('title');
Number::make('price');
```

`Number` is deliberately generic, not `Money`, `Currency`, `Integer`, or
`Decimal`. Those are either business semantics (a currency, a rounding
rule) or a precision/scale choice, `Number` represents the data, nothing
more, at Laravel's default `decimal` precision and scale (8, 2).

## Identity and persistence

```php
Text::make('seo_title')->attribute('title');
```

`id()` stays `seo_title`, `attributeName()` becomes `title`. See
[Concepts](concepts.md) for why these are separate.

## Required

```php
Text::make('title')->required();          // always required
Text::make('title')->requiredOnCreate();   // required only when creating
```

## Read-only and hidden, by context

Contexts are `index`, `detail`, `create`, `edit`, `api`, `export`.

```php
Text::make('title')
    ->hiddenOnIndex()
    ->readonlyOnEdit();

// or, for any other context:
Text::make('internal_note')->hiddenOn('api', 'export');
```

## Default value

```php
Text::make('status')->default('draft');
```

## Lifecycle hooks

```php
Text::make('title')
    ->beforeSave(fn ($value) => trim($value))
    ->afterSave(fn ($model, $value) => Cache::forget("post:{$model->id}"))
    ->hydrate(fn ($model) => $model->title)
    ->dehydrate(fn ($value) => Str::limit($value, 255));
```

## Driver

For swapping the underlying implementation later (no drivers exist yet
beyond the default input):

```php
Text::make('location')->driver('google-maps');
```

## Immutability

Every method above returns a new `Text` instance, the original is never
changed:

```php
$title = Text::make('title');
$required = $title->required();

$title === $required; // false
```

This matters if you build a Field once and reuse it in more than one
place, customizing it in one spot never leaks into another.

## Rendering

`Text` renders through a Blade view, resolved by convention
(`elo::fields.text`). `Number` follows the same convention
(`elo::fields.number`), a plain `<input type="number">`. There's no
working panel to see either in yet, but you can render them directly:

```php
view('elo::fields.text', [
    'field' => Text::make('title')->required(),
    'value' => 'Hello world',
    'context' => 'create',
    'error' => null,
])->render();

view('elo::fields.number', [
    'field' => Number::make('price')->required(),
    'value' => 19.99,
    'context' => 'create',
    'error' => null,
])->render();
```
