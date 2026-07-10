🇵🇹 Português | 🇬🇧 [English](../en/fields.md)

# Fields

O único tipo de Field que existe hoje é o `Text`, um input de texto de uma
linha. Tudo o que vem a seguir funciona agora mesmo, com testes reais por
trás.

```php
use Ecnmee\Elo\Fields\Text;

Text::make('title');
```

## Identidade e persistência

```php
Text::make('seo_title')->attribute('title');
```

`id()` mantém-se `seo_title`, `attributeName()` passa a `title`. Ver
[Conceitos](conceitos.md) para perceber porque são separados.

## Obrigatório

```php
Text::make('title')->required();          // sempre obrigatorio
Text::make('title')->requiredOnCreate();   // obrigatorio so na criacao
```

## Só-leitura e escondido, por contexto

Os contextos são `index`, `detail`, `create`, `edit`, `api`, `export`.

```php
Text::make('title')
    ->hiddenOnIndex()
    ->readonlyOnEdit();

// ou, para qualquer outro contexto:
Text::make('internal_note')->hiddenOn('api', 'export');
```

## Valor por omissão

```php
Text::make('status')->default('draft');
```

## Ganchos de ciclo de vida

```php
Text::make('title')
    ->beforeSave(fn ($value) => trim($value))
    ->afterSave(fn ($model, $value) => Cache::forget("post:{$model->id}"))
    ->hydrate(fn ($model) => $model->title)
    ->dehydrate(fn ($value) => Str::limit($value, 255));
```

## Driver

Para trocar a implementação subjacente mais tarde (ainda não existe
nenhum driver além do input por omissão):

```php
Text::make('location')->driver('google-maps');
```

## Imutabilidade

Cada método acima devolve uma nova instância de `Text`, a original nunca
muda:

```php
$title = Text::make('title');
$obrigatorio = $title->required();

$title === $obrigatorio; // false
```

Isto importa se construíres um Field uma vez e o reutilizares em mais do
que um sítio, personalizá-lo num sítio nunca vaza para outro.

## Renderização

O `Text` renderiza-se através de uma view Blade, resolvida por convenção
(`elo::fields.text`). Ainda não há nenhum painel funcional para o ver, mas
podes renderizá-lo directamente:

```php
view('elo::fields.text', [
    'field' => Text::make('title')->required(),
    'value' => 'Ola mundo',
    'context' => 'create',
    'error' => null,
])->render();
```
