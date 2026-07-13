🇵🇹 Português | 🇬🇧 [English](README.md)

# Exemplos

Código de referência, feito para ser lido e copiado para a tua própria
aplicação. Nada nesta pasta é autoloaded pelo pacote, e nada disto chega a
quem correr `composer require ecnmee/elo`.

## `PostResource.php`

Um `Resource` completo e realista para um artigo de blog, usando tudo o
que existe hoje no Elo: Fields `Text`, obrigatório/omissão, um `Layout`, e
um `EloquentRepository` ligado a um Model Eloquent real.

Para usares na tua própria aplicação:

1. Copia `PostResource.php` para `app/Elo/Resources/PostResource.php`
   (ajusta o namespace).
2. Confirma que existe um Model `Post` e uma migration `posts`, o Elo
   ainda não gera nenhum dos dois (`elo:sync` continua no roteiro).
3. Regista-o em `config/elo.php`:

   ```php
   'resources' => [
       'posts' => App\Elo\Resources\PostResource::class,
   ],
   ```

4. Visita `/elo/posts/create`.

Ver o [guia do utilizador](../docs/guide/pt/resources.md) para o percurso
completo.
