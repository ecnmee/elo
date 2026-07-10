🇬🇧 English | 🇵🇹 [Português](../pt/campos.md)

# Fields

The only Field type that exists today is `Text`, a single-line text input.
Everything below works right now, backed by real tests.

```php
use Ecnmee\Elo\Fields\Text;

Text::make('title');
```

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
(`elo::fields.text`). There's no working panel to see it in yet, but you
can render it directly:

```php
view('elo::fields.text', [
    'field' => Text::make('title')->required(),
    'value' => 'Hello world',
    'context' => 'create',
    'error' => null,
])->render();
```
