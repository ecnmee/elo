🇵🇹 Português | 🇬🇧 [English](PROGRESS.md)

# Progresso do Desenvolvimento

Um registo público e contínuo do ponto em que o Elo está. O detalhe técnico
completo vive em [`docs/adr/`](docs/adr); esta página é a versão em
linguagem simples, actualizada à medida que o projecto avança.

---

## Estado actual: design system

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
  A paleta deriva do próprio logótipo do Elo (neutros tingidos de navy, o
  mesmo azul de destaque) em vez de um genérico de painel administrativo, e
  o par tipográfico (IBM Plex Sans/Mono) foi escolhido para uma ferramenta
  técnica e densa em dados, não para uma página de marketing. O dark mode é
  suportado automaticamente e por override manual, e a preferência de
  movimento reduzido é respeitada.

**A seguir:**

- O componente Livewire `ResourceForm` (o primeiro a sério), o próprio
  `PostResource`, rotas, e o helper `elo()` do front-end — o resto do
  "PostResource de ponta a ponta".

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
