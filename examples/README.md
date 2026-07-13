🇬🇧 English | 🇵🇹 [Português](README.pt.md)

# Examples

Reference code, meant to be read and copied into your own application.
Nothing in this directory is autoloaded by the package, and none of it
ships to anyone who runs `composer require ecnmee/elo`.

## `PostResource.php`

A complete, realistic `Resource` for a blog post, using everything that
exists in Elo today: `Text` fields, required/default, a `Layout`, and an
`EloquentRepository` wired to a real Eloquent model.

To use it in your own app:

1. Copy `PostResource.php` into `app/Elo/Resources/PostResource.php`
   (adjust the namespace to match).
2. Make sure a `Post` model and a `posts` migration exist, Elo doesn't
   generate either yet (`elo:sync` is still on the roadmap).
3. Register it in `config/elo.php`:

   ```php
   'resources' => [
       'posts' => App\Elo\Resources\PostResource::class,
   ],
   ```

4. Visit `/elo/posts/create`.

See the [user guide](../docs/guide/en/resources.md) for the full
walkthrough.
