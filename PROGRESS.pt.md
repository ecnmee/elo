🇵🇹 Português | 🇬🇧 [English](PROGRESS.md)

# Progresso do Desenvolvimento

Um registo público e contínuo do ponto em que o Elo está. O detalhe técnico
completo vive em [`docs/adr/`](docs/adr); esta página é a versão em
linguagem simples, actualizada à medida que o projecto avança.

---

## Estado actual: o "PostResource de ponta a ponta" está fechado

**O que já está feito:**

- Arquitectura congelada em três ADRs: hierarquia e contratos, filosofia e
  limites, vocabulário oficial.
- Pacote Composer configurado, com toda a stack de qualidade: PHPStan,
  Pint, Pest, e integração contínua.
- Primeiro teste de arquitectura em vigor e a passar, falha automaticamente
  o CI se o core alguma vez passar a depender de um módulo de terceiros
  específico, transforma uma regra escrita numa regra aplicada.
- Os sete contratos públicos (`Module`, `Resource`, `Blueprint`, `Field`,
  `Action`, `Layout`, `Repository`) implementados como esqueletos tipados,
  cada um com testes unitários que cobrem o seu comportamento central: a
  imutabilidade do Blueprint, a regra de identidade separada de persistência
  do Field, os hooks opcionais do Module, e os cinco tipos de Layout da v1
  (Section, Tabs, Grid, Card, Column).
- Tokens de design (`tokens.css`) e uma folha de estilo base mínima
  (`elo.css`), CSS puro, sem Tailwind, sem UI kit de terceiros, delimitado
  sob `.elo-panel` para o pacote nunca vazar estilos para o site anfitrião.
- `EloquentRepository`, o único `Repository` na v1, e a conclusão do
  contrato `Resource::repository()`.
- `ResourceForm`, o primeiro componente Livewire real: renderiza o
  Blueprint de um Resource como formulário de criação/edição, resolve a
  ordem dos campos a partir do Layout (ou cai para a ordem de declaração
  quando não há nenhum), deriva a validação directamente do
  `isRequiredOn()` de cada Field, e grava através do Repository do
  Resource. Testado de ponta a ponta contra um Model Eloquent real numa
  base de dados em memória: criação, validação, edição, e o evento
  `elo-resource-saved`.
- Rotas: um `ResourceController` genérico (criar/editar, nunca um
  controller por Resource), com rotas geradas automaticamente a partir de
  um mapeamento slug-para-Resource num `config/elo.php` publicável. A
  descoberta completa de Modules ainda não está ligada, esta é
  deliberadamente a versão mínima real, um mapa mantido à mão, até
  aparecer um caso real que peça mais. `php artisan vendor:publish
  --tag=elo-assets` publica o `elo.css` para onde as rotas conseguem de
  facto carregá-lo.
- O helper `elo()` do front-end: `elo()->resource('posts')->where(...)->get()`,
  resolve um Resource registado pelo slug e devolve o seu `RepositoryQuery`
  directamente. Só `resource()` existe, `settings()`, `menu()`, e `form()`
  do esboço original da ADR ainda não têm um caso real por trás.
- Um `PostResource` completo e realista em `examples/`, código de
  referência para copiar para uma aplicação real, deliberadamente não
  distribuído como parte do pacote instalável (um Post de blog é
  específico de domínio, o core mantém-se pequeno de propósito).

**A seguir:**

- `elo:sync`, a gerar migrations a partir dos Fields de um Blueprint.

**Acabado de sair: `EloquentRepository`, e uma emenda ao contrato**

- `EloquentRepository` e `EloquentRepositoryQuery` implementados, o único
  `Repository` na v1, testado contra um Model Eloquent real numa base de
  dados SQLite em memória. `RepositoryQuery` fica deliberadamente mínimo:
  `where()`, `orderBy()`, `get()`, `first()`, nada especulativo.
- **Emenda ao contrato:** `Resource` ganhou `repository(): Repository`. A
  ADR-001 sempre comprometeu o Resource a depender de Repository, isto só
  completa essa dependência agora que existe uma implementação real para
  devolver. Sem inferência por convenção do Model, cada Resource declara o
  seu repositório explicitamente.

**Acabado de sair: o primeiro Field real (`Text`)**

- Renderiza-se como uma view Blade simples, não como o seu próprio
  componente Livewire aninhado, resolvida por convenção a partir do nome
  da classe (`Text` → `elo::fields.text`), para um Field simples nunca
  precisar de a declarar.
- Estabeleceu o contrato de variáveis que a view Blade de cada Field vai
  seguir: `$field`, `$value`, `$context`, `$error`.
- As classes CSS ao nível de campo (`elo-field`, `elo-field__label`,
  `elo-field__input`, `elo-field__error`) já existem no `elo.css`,
  construídas directamente sobre os tokens do passo anterior.

**Adiado, registado, não implementado:**

- `elo()->resource($slug)` devolver uma fachada estilo `ResourceHandle`
  (query, table, form, repository, metadata, tudo a partir de uma só
  chamada) em vez do `RepositoryQuery` que devolve hoje. Nenhum caso real
  ainda precisa de mais do que a query.
- `elo()->resource()` aceitar uma `class-string` ou um Model directamente,
  não só um slug de config.

- Os indicadores de contexto do `Field` (`hiddenOn`/`readonlyOn`/`requiredOn`)
  como uma estrutura única em vez de um array por comportamento, revisitar
  se aparecer mesmo um quarto comportamento (ex: `disabledOn`).
- Contextos como enum do PHP, revisitar só se um typo real causar um bug real.
- Esconder `Closure` atrás de um conceito `Lifecycle`.
- Um tipo `Record`/`DataRecord` em vez de arrays crus vindos do `Repository`.
- Tipos mais fortes no PHPDoc de `Module::commands()`/`widgets()`/`listeners()`
  (ex: `list<class-string>`), assim que uma primeira implementação real
  revelar qual forma cada método realmente assenta (`listeners()` em
  particular provavelmente precisa de um mapa evento-para-listeners, não
  uma lista simples).
- Direcção de `ResourceMetadata::defaultSort()` como constante validada
  (`ASC`/`DESC`) em vez de qualquer string.
- Vigiar o rácio contrato/implementação: cerca de 20-30% contratos e
  infra-estrutura vs. 70-80% implementações reais é uma faixa saudável
  enquanto o core ainda é jovem. Se interfaces, classes abstractas e
  registries continuarem a crescer sem implementações reais a usá-los, é
  sinal de que a arquitectura começou a crescer por antecipação.

## Porque existe esta página

O Elo está a ser construído em aberto, e a sua arquitectura foi moldada
através de um processo invulgarmente cuidadoso antes de qualquer linha de
código de implementação. Este registo existe para que quem está a
acompanhar não precise de ler cada commit para perceber o ponto em que as
coisas estão, é um atalho, não um substituto da documentação real.

## Como acompanhar mais de perto

- Star ou watch neste repositório para actualizações.
- As [ADRs](docs/adr) explicam cada decisão de arquitectura e o raciocínio por trás dela.
