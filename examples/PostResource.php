<?php

declare(strict_types=1);

namespace App\Elo\Resources;

// Adjust this to wherever your own Post model lives.
use App\Models\Post;
use Ecnmee\Elo\Blueprint;
use Ecnmee\Elo\Fields\Text;
use Ecnmee\Elo\Layouts\Section;
use Ecnmee\Elo\Repositories\EloquentRepository;
use Ecnmee\Elo\Repository;
use Ecnmee\Elo\Resource;
use Ecnmee\Elo\ResourceMetadata;

/**
 * Reference example, not part of the installed package. Copy this into
 * your own app (see examples/README.md), adjust the model/fields to your
 * real schema.
 */
final class PostResource extends Resource
{
    public static function definition(): ResourceMetadata
    {
        return ResourceMetadata::make()
            ->label('Post')
            ->pluralLabel('Posts')
            ->searchColumns('title');
    }

    public function blueprint(): Blueprint
    {
        return Blueprint::make()
            ->fields(
                Text::make('title')
                    ->required()
                    ->hiddenOnIndex(),
                Text::make('slug')
                    ->required()
                    ->readonlyOnEdit(),
                Text::make('excerpt'),
                Text::make('status')
                    ->default('draft'),
            )
            ->layout(
                Section::make('content')->fields('title', 'slug', 'excerpt'),
                Section::make('publishing')->fields('status'),
            );
    }

    public function repository(): Repository
    {
        return new EloquentRepository(Post::class);
    }
}
