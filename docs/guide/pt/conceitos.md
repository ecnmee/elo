🇵🇹 Português | 🇬🇧 [English](../en/concepts.md)

# Conceitos

O Elo é construído a partir de sete conceitos públicos. Esta página
explica-os em linguagem simples; a [ADR-003](../adr/pt/ADR-003-elo-linguagem.md)
é a definição precisa e congelada, se precisares dela.

## A hierarquia

```
Module → Resource → Blueprint → Field / Layout / Action
```

Um **Module** agrupa Resources relacionados (um módulo Blog pode ter Post,
Category, Tag). Um **Resource** descreve uma entidade gerível (um Post). O
**Blueprint** de um Resource é a sua estrutura: que Fields tem, como estão
dispostos, que Actions suporta.

## Field: um pedaço de dado

Um **Field** é um único pedaço de dado num Resource, um título, um corpo,
um preço. Tem identidade própria (`id`), separada de onde é de facto
guardado (`attribute`), são iguais por omissão:

```php
use Ecnmee\Elo\Fields\Text;

Text::make('title');                              // id e attribute sao ambos "title"
Text::make('seo_title')->attribute('title');       // id diferente, mesma coluna
```

Ver [Fields](campos.md) para tudo o que o `Text` (o único Field que existe
hoje) sabe fazer.

## Layout: onde um Field aparece, não o que é

Um **Layout** decide onde um Field é mostrado, nunca contém o Field em si,
só o referencia por id. Isto significa que o mesmo Field pode aparecer em
mais do que um sítio sem ser definido duas vezes. (O Layout ainda não tem
um Field concreto para apontar, já que o `ResourceForm` não está
construído, isto torna-se concreto assim que estiver.)

## Action: algo que o utilizador pode accionar

Uma **Action** é uma operação iniciada pelo utilizador, publicar,
duplicar, exportar. Ainda não está integrada em nenhum fluxo real, mas o
contrato existe e está testado.

## Repository: onde os dados de facto vivem

Um **Resource** não fala directamente com o Eloquent, fala com um
**Repository**. Hoje, isso é o `EloquentRepository`, que envolve um único
Model Eloquent:

```php
use Ecnmee\Elo\Repositories\EloquentRepository;

public function repository(): Repository
{
    return new EloquentRepository(Post::class);
}
```

Esta indirecção é o que eventualmente vai permitir que um Resource seja
suportado por outra coisa que não o Eloquent, sem mudar mais nada em como
é definido.
