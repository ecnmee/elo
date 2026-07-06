🇵🇹 Português | 🇬🇧 [English](../en/ADR-001-elo-architecture.md)

# ADR-001 - Arquitectura do Elo (final)

**Status:** Aceite - contratos fundamentais congelados
**Data:** 2026-07-04
**Pacote Composer:** `ecnmee/elo`
**Namespace:** `Ecnmee\Elo`

**Nota sobre o nome:** existe conflito de marca com a bandeira de cartões
Elo (Bradesco/BB/Caixa, ~140M de cartões no Brasil). Risco avaliado e aceite
conscientemente - o pacote opera num domínio (tooling PHP/Laravel) totalmente
distinto, e o nome mantém-se pela clareza conceptual ("ligação" entre domínio,
painel e front-end).

---

## 1. Contexto

Cada site construído do zero repete o mesmo trabalho: login, dashboard, upload,
páginas, formulários, SEO, menus, utilizadores, configurações. O Elo existe para
eliminar essa repetição.

O Elo **não é um gerador de CRUD**. É uma plataforma de gestão de websites: o
programador descreve o domínio do site e o Elo constrói a interface de gestão,
a API, as rotas e, de forma controlada, as migrations correspondentes.

> **Filosofia:** o programador descreve o domínio do website. O Elo constrói tudo o resto.

Requisitos que guiaram as decisões:
- CSS próprio do painel, sem dependência de um UI kit de terceiros (Livewire + Blade puro).
- Instalação **site a site** (um pacote por projecto, não multi-tenant central).
- Módulos de terceiros como **pacotes Composer independentes**, prioridade desde a v1.
- `elo:sync` (geração de migrations a partir dos Fields) incluído desde a v1.

---

## 2. Princípio arquitectural central

> **Todos os elementos do Blueprint (Field, Action, Layout) têm identidade
> própria (`id`) e podem ser referenciados por ela. A persistência dos dados
> (`attribute`) é um conceito separado da identidade do elemento.**

Este princípio é o que torna o Blueprint um **grafo**, não uma árvore:
o mesmo Field existe uma única vez e pode ser referenciado a partir de
vários Layouts, Toolbars ou contextos, sem duplicação.

```
Blueprint
│
├── Fields    { page_title, body, cover }
├── Actions   { publish, duplicate }
├── Layouts   { content, seo }
│
└── Referências
    content ─┬─ page_title
             └─ body
    seo    ───── page_title      (mesmo Field, contexto diferente)
```

**Regra de omissão:** por defeito, `attribute = id`. `Text::make('title')`
continua trivial - só se declara `->attribute()` quando o `id` diverge da
coluna de persistência (título SEO, alias, campo calculado).

```php
Text::make('title');                                    // id=title, attribute=title
Text::make('seo_title')->attribute('title');              // id distinto, mesmo attribute
Text::make('display_name')->computed(fn ($r) => ...);      // sem attribute - campo calculado
```

---

## 3. Hierarquia central

```
Module
  └── Resource
        └── Blueprint (grafo, imutável, compilável)
              ├── Layout   { id, referencia Fields/Actions por id }
              ├── Field    { id, attribute, lifecycle, contexto }
              └── Action   { id, contexto, bulk }
```

### 3.1 Module

Unidade de domínio de mais alto nível. Responsável por tudo o que pertence ao
seu domínio. Métodos além de `resources()` e `menu()` são **opcionais**, com
implementação vazia por defeito na classe base.

```php
class BlogModule extends Module
{
    public function resources(): array
    {
        return [PostResource::class, CategoryResource::class, TagResource::class];
    }

    public function menu(): array
    {
        return [MenuItem::make('Blog')->icon('post')->resource(PostResource::class)];
    }

    public function policies(): array { return []; }
    public function widgets(): array { return []; }
    public function commands(): array { return []; }
    public function listeners(): array { return []; }
    public function routes(): array { return []; }
}
```

*Nota registada, não implementada na v1:* um nível `Domain` entre `Module` e
`Resource` (agrupar Resources por agregado de negócio dentro de um módulo
grande) não tem justificação suficiente ainda. Organização por subpastas
(`app/Elo/Modules/Shop/Catalog/ProductResource.php`) já resolve o caso de
organização de ficheiros sem precisar de uma classe formal. `Domain` só se
introduz se aparecer um módulo real que precise de comportamento próprio por
agregado (menu agrupado, policies por agregado), não apenas agrupamento visual.

### 3.2 Resource

Descreve uma entidade gerível. Metadata em `definition()`; estrutura
(Fields + Layout + Actions) em `blueprint()`.

```php
class PostResource extends Resource
{
    public static function definition(): ResourceMetadata
    {
        return ResourceMetadata::make()
            ->label('Artigo')
            ->pluralLabel('Artigos')
            ->icon('post')
            ->navigationGroup('Blog')
            ->searchColumns(['title'])
            ->defaultSort('created_at', 'desc');
    }

    public function blueprint(): Blueprint
    {
        return Blueprint::make()
            ->fields([
                Text::make('title')->required(),
                Slug::make('slug')->from('title'),
                RichText::make('body'),
                Image::make('cover'),
                Boolean::make('published')->default(false),
            ])
            ->layout([
                Section::make('content')->fields(['title', 'body']),
            ])
            ->actions([
                PublishAction::make('publish'),
                DuplicateAction::make('duplicate'),
            ])
            ->uses(SeoBlueprint::class, MediaBlueprint::class); // composição
    }
}
```

**Fonte de dados desacoplada de Eloquent.** O Resource depende de um contrato
`Repository`, não obrigatoriamente de um Eloquent Model. Na v1 só existe
`EloquentRepository`; o contrato fica aberto para `RedisRepository`,
`ApiRepository`, etc. quando houver caso real.

```php
interface Repository
{
    public function find(int|string $id): ?array;
    public function query(): RepositoryQuery;
    public function save(array $attributes): array;
    public function delete(int|string $id): bool;
}
```

### 3.3 Blueprint

Estrutura completa de um Resource - campos, layout e acções - como um grafo
imutável e componível.

- **Imutável:** cada método (`fields()`, `layout()`, `actions()`, `uses()`)
  devolve uma **nova instância**. Necessário para composição segura (o mesmo
  `SeoBlueprint` usado em `PostResource` e `ProductResource` nunca pode
  sofrer mutação partilhada) e para permitir cache seguro entre pedidos.
- **Componível:** `->uses(SeoBlueprint::class, MediaBlueprint::class)` mescla
  Fields/Actions/Layouts de outros Blueprints, evitando duplicar campos
  recorrentes (SEO, media, endereço) em cada Resource.
- **Compilável:** o `BlueprintCompiler` resolve a composição (`uses()`),
  aplica a regra de omissão `attribute = id`, e **valida o grafo**:
  - **ID duplicado** dentro do mesmo Blueprint → erro em tempo de compilação.
  - **Referência pendente** (Layout/Toolbar aponta para um `id` inexistente) → erro em tempo de compilação.

  A compilação não é um sistema de cache paralelo - é invocada pelo mesmo
  `elo:cache` já previsto para Modules/Resources. Uma única superfície de
  cache no framework.

> Nome escolhido deliberadamente diferente de `Schema` para não colidir com
> `SchemaSnapshot`/`SchemaDiff` do motor de sync - conceitos diferentes
> (estrutura de UI/composição vs. estrutura de tabelas SQL).

### 3.4 Layout

Classe base própria, ao lado de `Field` e `Action` - terceiro cidadão de
primeira classe. Tem identidade (`id`) e **referencia Fields/Actions por id,
nunca contém as instâncias directamente**. Isto garante que um Field existe
uma única vez no Blueprint; o Layout só decide onde e como é apresentado.

```
Layout
  ├── Section
  ├── Tabs
  ├── Grid
  ├── Card
  └── Column
```

```php
Section::make('content')->fields(['title', 'body']);   // referência por id
Tabs::make('seo')->fields(['seo_title']);
```

**v1:** `Section`, `Tabs`, `Grid`, `Column`, `Card`.
**v1.1:** `Accordion`, `Wizard`.
**Fora da v1:** `Repeater` (dados aninhados - categoria de problema diferente,
merece a sua própria ADR).

### 3.5 Field

Tem identidade (`id`) separada de persistência (`attribute`, por omissão
igual ao `id`). Cada Field:

1. Sabe o **tipo de coluna** que precisa (usado pelo `elo:sync`).
2. Sabe as suas **regras de validação**.
3. Sabe **renderizar-se** (componente Livewire/Blade próprio).
4. Participa no **ciclo de vida** do Resource.
5. É **consciente do contexto** onde está a ser usado.

```php
Text::make('title')
    ->required()
    ->beforeSave(fn ($value) => trim($value))
    ->afterSave(fn ($model, $value) => Cache::forget("post:{$model->id}"))
    ->hydrate(fn ($model) => $model->title)
    ->dehydrate(fn ($value) => Str::limit($value, 255))
    ->hiddenOnIndex()
    ->readonlyOnEdit()
    ->requiredOnCreate();
```

Contextos suportados: `index`, `detail`, `create`, `edit`, `api`, `export`.

**Editores ricos como drivers de Field, não Plugins separados:**

```php
RichText::make('body')->driver('tiptap');
Text::make('location')->driver('google-maps');
```

Não existe `PluginRegistry` na v1.

**Padrão de implementação interno (não é contrato público).** A classe base
`Field` delega internamente para quatro colaboradores com omissões simples:
`FieldRenderer`, `FieldValidator`, `FieldType`, `FieldHydrator`. Fields
simples usam as omissões; Fields complexos sobrepõem só o que precisam. Por
não afectar a API pública, pode evoluir livremente sem quebrar compatibilidade.

### 3.6 Action

Identidade própria (`id`), consciente de contexto tal como o Field, referenciável
a partir de Layouts/Toolbars por id.

```php
Action::make('publish')
    ->handle(fn ($record) => $record->update(['published' => true]))
    ->visibleOn(['index', 'detail'])
    ->bulk();
```

---

## 4. Decisões de infra-estrutura

### D1 - Discovery de Modules locais (do projecto)

Scan de `app/Elo/Modules/*` no boot da aplicação, com cache via
`php artisan elo:cache`. Config pode excluir/desactivar módulos por projecto.

### D2 - Discovery de Modules de terceiros (pacotes Composer)

Laravel Package Discovery nativo (`extra.laravel.providers`). No `boot()` do
provider do módulo:

```php
public function boot(): void
{
    Elo::registerModule(GalleryModule::class);
}
```

`composer require vendor/elo-gallery` instala; `composer remove` desinstala.
Versionamento do módulo independente do core.

### D3 - Comunicação entre Modules

Modules não se conhecem directamente. Relações de dados via Field
(`BelongsTo::make('author', TeamResource::class)`); reacção entre módulos via
Events nativos (`ResourceCreated`, `ResourceUpdated`, `ResourceDeleted`).

### D4 - Consumo no front-end

API pública única: tudo passa por `elo()`.

```php
elo()->resource('posts')->published()->get();
elo()->settings();
elo()->menu('main');
elo()->form('contact');
```

Atalhos globais (`setting()`, `menu()`, `form()`) chamam internamente o mesmo
serviço - não são API paralela. API REST é opcional por módulo (`Module::api()`).

### D5 - Eliminação de Controllers e Rotas

Controller genérico (Livewire component) resolve o Resource pelo slug da
rota. Rotas geradas em loop no boot do `EloServiceProvider`.

### D6 - Design System (pré-requisito, antes do primeiro Field)

CSS custom properties do painel definidas antes de qualquer componente
Livewire: spacing, radius, elevation, typography, animations, states, icons,
dark mode.

### D7 - `elo:sync` gera migrations, não altera a base de dados directamente

```
Fields (via Blueprint compilado)  →  SchemaSnapshot  →  SchemaDiff  →  Operations  →  MigrationWriter
```

`MigrationWriter` traduz as operações num ficheiro de migration Laravel real
(`up()`/`down()`) em `database/migrations/` - **não toca na base de dados**.

```
Alterei o Blueprint (fields) → php artisan elo:sync → migration gerada
→ revejo → git commit → php artisan migrate
```

Sem `--dry-run`/`--force`/confirmação interactiva - a segurança vem do
próprio fluxo Laravel (git review + migrate manual). `down()` gerado
automaticamente a partir da operação inversa.

Módulos de terceiros declaram a migration base da tabela da forma
tradicional Laravel; o `elo:sync` gera só a evolução incremental dos Fields.

### D8 - Versionamento e compatibilidade

Semver estrito. Major = mudança de contrato em `Field`, `Action`, `Layout`,
`Blueprint`, `Resource`, `Module` ou `Repository`. Módulos de terceiros
declaram compatibilidade via constraint normal do Composer.

---

## 5. Contratos congelados

A partir desta revisão, os seguintes contratos estão **congelados** - mudar
a sua assinatura pública passa a exigir um major version bump (D8):

- `Module`
- `Resource`
- `Blueprint`
- `Field`
- `Action`
- `Layout`
- `Repository`

mais o princípio arquitectural da secção 2 (identidade separada de persistência).

---

## 6. Estrutura do pacote core

```
elo/
├── src/
│   ├── EloServiceProvider.php
│   ├── Module.php
│   ├── Resource.php                  (definition() + blueprint())
│   ├── Blueprint.php                 (grafo imutável: fields/layout/actions)
│   ├── BlueprintCompiler.php         (resolve uses(), valida IDs/referências)
│   ├── Layout.php                    (classe base) + Section/Tabs/Grid/Column/Card
│   ├── Field.php                     (id, attribute, lifecycle, contexto)
│   ├── Action.php                    (id, contexto, bulk)
│   ├── Repository.php
│   ├── EloquentRepository.php
│   ├── Facades/Elo.php
│   ├── Console/Commands/
│   │   ├── SyncCommand.php           (elo:sync)
│   │   └── CacheCommand.php          (elo:cache - inclui compilação de Blueprints)
│   ├── Sync/
│   │   ├── SchemaSnapshot.php
│   │   ├── SchemaDiff.php
│   │   ├── Operations/
│   │   └── MigrationWriter.php
│   ├── Http/
│   │   ├── Controllers/ (ResourceController, Api/ResourceApiController)
│   │   └── Livewire/ (ResourceTable, ResourceForm, Fields/, FormBuilder)
│   ├── Registry/ (ModuleRegistry, ResourceRegistry)
│   └── Models/ (Media, FormSubmission, EloUser)
├── resources/
│   ├── views/livewire/...
│   └── css/ (tokens.css, elo.css)
├── database/migrations/              (tabelas internas)
├── config/elo.php
└── routes/elo.php
```

---

## 7. Próximos passos

> Ordem revista na ADR-002: os contratos públicos são a fundação do
> framework - uma assinatura mal desenhada exige major version bump; o CSS
> pode evoluir livremente sem quebrar compatibilidade. Por isso os contratos
> vêm antes do design system.

1. Criar o repositório (`ecnmee/elo`).
2. Configurar CI, análise estática, testes e estilo de código.
3. Implementar os contratos vazios: `Module`, `Resource`, `Blueprint`,
   `Field`, `Action`, `Layout`, `Repository` (assinatura pública fechada,
   corpo mínimo - sem lógica de negócio ainda).
4. Definir o **design system** (`tokens.css`).
5. Implementar o primeiro Field de referência (`Text`), validando
   id/attribute, lifecycle e contexto sobre os tokens definidos.
6. Construir `PostResource` de ponta a ponta: listagem, criação, edição,
   `elo()->resource('posts')` no front.
7. Implementar o motor de `elo:sync`, testado sobre `PostResource`,
   confirmando migration Laravel válida e reversível.
8. Extrair `SeoBlueprint`/`MediaBlueprint`, validando composição e as
   validações do `BlueprintCompiler` (ID duplicado, referência pendente).
9. Extrair `elo-gallery` como pacote Composer separado, validando D2.

Ver ADR-002 para os princípios que devem guiar cada uma destas
implementações (contratos vs. implementações substituíveis, regra de ouro
do núcleo).
