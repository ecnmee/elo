🇵🇹 Português | 🇬🇧 [English](../en/resources.md)

# Resources e formulários

Este é o primeiro fluxo real de ponta a ponta: definir um Resource, obter
um formulário de criação/edição a funcionar a partir dele, suportado por
uma tabela de base de dados real.

## Definir um Resource

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
            ->label('Artigo')
            ->pluralLabel('Artigos');
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

Três métodos, três responsabilidades: `definition()` é metadata (label,
ícone, navegação, ainda não integrada na UI), `blueprint()` é estrutura
(fields, layout, actions), `repository()` é onde os dados vivem.

## Renderizar o formulário

```blade
<livewire:elo-resource-form :resource-class="PostResource::class" />
```

Para editar um registo existente:

```blade
<livewire:elo-resource-form :resource-class="PostResource::class" :record-id="$post->id" />
```

O `ResourceForm` trata do resto: lê o Blueprint, renderiza cada Field
através da sua própria view na ordem que o Layout declara (ou na ordem em
que foram definidos, se não houver Layout), valida usando o
`isRequiredOn()` de cada Field, e grava através do Repository do Resource
ao submeter.

## Tornar acessível por URL

Regista o Resource em `config/elo.php` (publica-o primeiro com
`php artisan vendor:publish --tag=elo-config`):

```php
'resources' => [
    'posts' => \App\Elo\Resources\PostResource::class,
],
```

Só isto gera duas rotas: `/elo/posts/create` e `/elo/posts/{record}/edit`,
ambas a renderizar a mesma página que obterias colocando o
`<livewire:elo-resource-form>` numa view tu próprio. Publica também a
folha de estilo, ou a página fica sem estilo:

```bash
php artisan vendor:publish --tag=elo-assets
```

Ainda não há nenhum ecrã de listagem a ligar para estas rotas, visita os
URLs directamente por agora.

## Consumir um Resource a partir do front-end

Depois de um Resource estar registado em `config/elo.php`,
`elo()->resource($slug)` dá-te os dados directamente, sem precisares de
nenhum controller teu:

```php
elo()->resource('posts')->where('status', 'published')->orderBy('title')->get();
```

Devolve o mesmo `RepositoryQuery` que obterias de
`$resource->repository()->query()`, por isso `where()`, `orderBy()`,
`get()`, e `first()` funcionam exactamente como descrito acima.

## Um exemplo completo

[`examples/PostResource.php`](https://github.com/ecnmee/elo/blob/main/examples/PostResource.php)
neste repositório tem um Resource mais completo e realista, mais campos,
um Layout com várias secções, pronto a copiar para a tua própria aplicação.

## O que isto ainda não faz

- Sem ecrã de listagem, o `ResourceTable` ainda não foi construído.
- Sem geração de migration, a tabela (`posts`, no exemplo acima) ainda
  precisa de uma migration escrita à mão.
- Sem autenticação ou autorização nas rotas, adiciona o teu próprio
  middleware via `config('elo.middleware')` se o painel precisar de
  protecção.
- Só existem Fields `Text`, por isso hoje só funciona bem para dados de
  texto simples.

Ver [PROGRESS.pt.md](https://github.com/ecnmee/elo/blob/main/PROGRESS.pt.md)
para o que vem a seguir.
