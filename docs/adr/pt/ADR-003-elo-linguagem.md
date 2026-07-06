🇵🇹 Português | 🇬🇧 [English](../en/ADR-003-elo-language.md)

# ADR-003 - Linguagem Ubíqua

**Status:** Aceite
**Data:** 2026-07-04
**Depende de:** ADR-001 (Arquitectura), ADR-002 (Filosofia)

A ADR-001 responde ao "como". A ADR-002 responde ao "o que é e o que não é".
Esta ADR responde a uma pergunta diferente: **como o Elo chama cada
conceito** - e garante que essa resposta nunca varia entre código,
documentação e conversa.

> O Elo é um framework declarativo para gestão de websites, construído
> sobre Laravel.

Esta frase resume as três ideias que atravessam as três ADRs - *framework*
(não CMS), *declarativo* (não gerador de CRUD), *sobre Laravel* (não
concorrente dele) - e deve abrir a documentação oficial.

---

## 1. Os sete conceitos públicos

```
Module · Resource · Blueprint · Field · Layout · Action · Repository
```

Nada mais existe como conceito público de primeira classe. Não existem, e
nunca deverão existir, como nomes de conceitos: `Entity`, `Model` (no
sentido do Elo - o Eloquent Model continua a existir como implementação),
`Widget`, `Screen`, `Panel`, `FormDefinition`, `Schema`, `PageDefinition`.
Se um desses termos aparecer numa proposta futura, ou é um sinónimo de algo
que já tem nome (rejeitar), ou é um conceito novo que precisa de justificar
a sua entrada nesta lista (tratamento igual ao de um novo contrato - ADR-001, D8).

## 2. Um nome, um significado

| Termo | É sempre | Nunca é |
|---|---|---|
| **Resource** | a entidade gerível | Controller, página, endpoint de API |
| **Blueprint** | a estrutura de um Resource (fields+layout+actions) | Schema, Definition, Configuration |
| **Field** | um elemento de dados | Input, Component, Widget |
| **Layout** | estrutura visual, sem lógica de dados | um lugar para validação ou hidratação |
| **Action** | comportamento iniciado pelo utilizador | Event, Job, Command (do Laravel) |
| **Module** | unidade de domínio de mais alto nível | Package (o pacote Composer é o meio de distribuição; Module é o conceito Elo dentro dele) |
| **Repository** | contrato de acesso a dados | um Eloquent Repository genérico de terceiros |

**Regra de sinónimos:** se um conceito já tem nome oficial, esse é o único
nome usado em código, testes, comandos artisan, mensagens de erro e
documentação. Nunca coexistem dois nomes para a mesma coisa.

## 3. Vocabulário de suporte (não são conceitos públicos, mas são termos recorrentes)

- **Driver** - implementação substituível por trás de um Field ou de uma
  Camada 2 da ADR-002 (ex: `RichText::make('body')->driver('tiptap')`).
  Mesmo sentido que Laravel já usa para Cache/Queue drivers - não é um
  termo novo do Elo, é reaproveitado deliberadamente.
- **Blueprint compilado** - o resultado do `BlueprintCompiler` (interno,
  ADR-001 secção 3.3). O consumidor do Elo nunca interage com isto
  directamente.
- **Sync engine** (`SchemaSnapshot`, `SchemaDiff`, `Operations`,
  `MigrationWriter`) - vocabulário **interno** do motor de `elo:sync`
  (ADR-001, D7). "Schema" aqui refere-se a schema de base de dados - um
  domínio diferente de "Blueprint", e por isso não é uma reintrodução do
  termo banido na secção 2. O programador nunca escreve estas classes; só
  vê o resultado (o ficheiro de migration gerado).

## 4. Correcção retroactiva à ADR-001

`ResourceDefinition` renomeado para **`ResourceMetadata`** - o nome
`Definition` colidia com a proibição explícita da secção 2 desta ADR
("Blueprint nunca é Definition"), mesmo descrevendo um conceito diferente
(label/ícone/navegação vs. estrutura). O risco de confusão para quem lê o
código pela primeira vez era real o suficiente para justificar a correcção
antes de existir uma única linha de implementação.

## 5. Consequência prática

Toda a documentação, exemplos e tutoriais usam exactamente estes termos,
sempre. Nunca acontece um tutorial chamar `Resource` a uma coisa e outro
chamar-lhe `Entity` ou `Model`. Esta consistência é o que torna frameworks
como Laravel, Symfony ou React reconhecíveis ano após ano - e é uma decisão
deliberada, não um acidente de quem escreveu a primeira versão da documentação.

---

## 6. A partir daqui

As três ADRs (Arquitectura, Filosofia, Linguagem) estão fechadas. Não há
mais decisões arquitecturais a tomar por especulação - a próxima decisão
architectural só nasce de um caso real encontrado durante a implementação.

Sequência de implementação:

1. Repositório (`ecnmee/elo`).
2. Qualidade do projecto: PHPStan, Pint, Pest, CI.
3. Contratos públicos vazios: `Module`, `Resource`, `Blueprint`, `Field`,
   `Action`, `Layout`, `Repository`.
4. Primeiro teste de arquitectura - ex: um teste Pest Arch/Deptrac que falha
   o CI se qualquer classe em `Elo\` referenciar directamente um namespace
   de módulo específico, tornando verificável o princípio "o core nunca
   conhece módulos de terceiros" (ADR-002, secção 3.4).
5. Design system (`tokens.css`).
6. Primeiro Field (`Text`).
7. `PostResource` de ponta a ponta.
8. Motor de `elo:sync`.
