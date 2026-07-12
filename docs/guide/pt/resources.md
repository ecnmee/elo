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

## O que isto ainda não faz

- Sem ecrã de listagem, o `ResourceTable` ainda não foi construído.
- Sem rotas, o `<livewire:elo-resource-form>` tem de ser colocado numa
  view que já tenhas.
- Sem geração de migration, a tabela (`posts`, no exemplo acima) ainda
  precisa de uma migration escrita à mão.
- Só existem Fields `Text`, por isso hoje só funciona bem para dados de
  texto simples.

Ver [PROGRESS.pt.md](https://github.com/ecnmee/elo/blob/main/PROGRESS.pt.md)
para o que vem a seguir.
