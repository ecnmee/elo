🇬🇧 English | 🇵🇹 [Português](../pt/resources.md)

# Resources and forms

This is the first real end-to-end flow: define a Resource, get a working
create/edit form out of it, backed by a real database table.

## Defining a Resource

```php
use Ecnmee\Elo\Blueprint;
use Ecnmee\Elo\Fields\Text;
use Ecnmee\Elo\Layouts\Section;
use Ecnmee\Elo\Repositories\EloquentRepository;
use Ecnmee\Elo\Repository;
use Ecnmee\Elo\Resource;
use Ecnmee\Elo\ResourceMetadata;

class PostResource extends Resource
{
    public static function definition(): ResourceMetadata
    {
        return ResourceMetadata::make()
            ->label('Post')
            ->pluralLabel('Posts');
    }

    public function blueprint(): Blueprint
    {
        return Blueprint::make()
            ->fields(
                Text::make('title')->required(),
                Text::make('body'),
            )
            ->layout(
                Section::make('content')->fields('title', 'body'),
            );
    }

    public function repository(): Repository
    {
        return new EloquentRepository(Post::class);
    }
}
```

Three methods, three responsibilities: `definition()` is metadata (label,
icon, navigation, not built into the UI yet), `blueprint()` is structure
(fields, layout, actions), `repository()` is where the data lives.

## Rendering the form

```blade
<livewire:elo-resource-form :resource-class="PostResource::class" />
```

For editing an existing record:

```blade
<livewire:elo-resource-form :resource-class="PostResource::class" :record-id="$post->id" />
```

`ResourceForm` does the rest: it reads the Blueprint, renders each Field
through its own view in the order the Layout declares (or in the order
they were defined, if there's no Layout), validates using each Field's
`isRequiredOn()`, and saves through the Resource's Repository on submit.

## What this doesn't do yet

- No listing screen, `ResourceTable` isn't built.
- No routing, `<livewire:elo-resource-form>` has to be dropped into a view
  you already have.
- No migration generation, the table (`posts`, in the example above) still
  needs a hand-written migration.
- Only `Text` fields exist, so this only really works well for simple text
  data today.

See [PROGRESS.md](https://github.com/ecnmee/elo/blob/main/PROGRESS.md) for
what's coming next.
