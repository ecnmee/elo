🇵🇹 Português | 🇬🇧 [English](README.md)

# Elo

**Framework declarativo para gestão de websites, construído sobre Laravel.**

> Todos os websites repetem o mesmo trabalho: login, dashboard, upload,
> páginas, formulários, SEO, menus, utilizadores, configurações. O Elo
> nasceu para eliminar essa repetição.
>
> Não queremos substituir o Laravel. Não queremos criar outro CMS. Queremos
> que o programador descreva o domínio do website e continue a escrever
> Laravel, enquanto o Elo constrói automaticamente a infraestrutura
> repetitiva.

## Instalação

```bash
composer require ecnmee/elo
```

*(Pacote em desenvolvimento activo, ainda não publicado no Packagist.)*

## Conceitos centrais

```
Module → Resource → Blueprint → Field / Layout / Action
```

Descreves um domínio através de sete conceitos públicos, `Module`,
`Resource`, `Blueprint`, `Field`, `Layout`, `Action`, `Repository`, e o
Elo constrói a interface de gestão, a API, e as migrations correspondentes.

```php
class PostResource extends Resource
{
    public function blueprint(): Blueprint
    {
        return Blueprint::make()
            ->fields([
                Text::make('title')->required(),
                RichText::make('body'),
                Image::make('cover'),
            ])
            ->layout([
                Section::make('content')->fields(['title', 'body']),
            ]);
    }
}
```

## O que é e o que não é

O Elo é um framework: declarativo, PHP-first, Laravel-native, extensível
por composição. Não é um page builder, não é um website builder, não é um
CMS monolítico, não é um substituto do Laravel.

## Documentação

Este repositório é a distribuição pública, de pacote único, do Elo, gerada
a partir do monorepo de desenvolvimento
[`ecnmee/elo-monorepo`](https://github.com/ecnmee/elo-monorepo), onde
acontece o desenvolvimento do core, a documentação de arquitectura
(ADR-001/002/003), e a discussão de issues.

## Contribuir

O desenvolvimento do core acontece no [monorepo](https://github.com/ecnmee/elo-monorepo).
Ver [CONTRIBUTING.md](CONTRIBUTING.pt.md) para detalhes.

## Código de conduta

Ver [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.pt.md).

## Licença

MIT, ver [LICENSE](LICENSE).
