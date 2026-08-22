🇵🇹 Português | 🇬🇧 [English](../en/ADR-004-elo-relation-field.md)

# ADR-004 - Field de Relação

**Estado:** Aceite, pronta para implementação
**Data:** 2026-08-22
**Depende de:** ADR-001 (Arquitetura), ADR-003 (Linguagem)

---

## 1. Contexto

O `OrderResource` não consegue declarar que uma Order pertence a um
Customer ou a um Product. Confirmado duas vezes agora, uma quando a demo
foi construída pela primeira vez (`PROGRESS.md`), outra a validar
`Customer`/`Service`: todas as Resources construídas até agora só
precisaram de Fields escalares (`Text`, `Number`). Este é o primeiro
Field cujo valor é o registo de outra Resource, não um escalar, e toca
todas as camadas ao mesmo tempo: tipo de coluna, renderização do
formulário (um select, não um input), validação (`exists` contra outra
tabela), e leitura (a tabela precisa de mostrar `Nome do Customer`, não
`customer_id`).

A ADR-001, D3, já compromete a forma ao nível da arquitetura:

> Relações de dados via Field (`BelongsTo::make('author',
> TeamResource::class)`)

Essa frase era ilustrativa, não uma especificação. Esta ADR é essa
especificação.

## 2. Motores da decisão

- **Consistência com o `Field`.** `BelongsTo` é uma subclasse de
  `Field`, o mesmo contrato que `Text` e `Number` já implementam
  (`type()`, `view()`, ciclo de vida, contexto). Segundo a ADR-003, isso
  significa que não é um conceito público novo e não precisa de entrada
  no vocabulário, exatamente como `Text`/`Number`.
- **Disciplina de âmbito.** A ADR-001 §3.4 já adiou o `Repeater` para a
  sua própria ADR como "uma categoria de problema diferente". Um sistema
  de relações completo (`BelongsTo`, `HasMany`, `HasOne`,
  `BelongsToMany`, variantes polimórficas) é o mesmo tipo de categoria,
  esta ADR propõe decidir só o `BelongsTo`, o que a demo precisa mesmo,
  e adiar o resto até uma Resource real precisar deles.
- **Nenhum conceito de infraestrutura novo.** O `Repository::query()` já
  existe (`where`, `orderBy`, paginação); um Field `BelongsTo` a povoar
  um select não precisa de mais nada além de chamar o próprio
  Repository da Resource relacionada, nenhum contrato novo.

## 3. Nome e sintaxe

Havia duas formas em cima da mesa:

```php
// A: atributo primeiro, coluna explícita
Relation::make('customer_id')
    ->resource(CustomerResource::class)
    ->displayUsing('name');

// B: relação primeiro, id/attribute separados (ADR-001 §2)
BelongsTo::make('customer')
    ->resource(CustomerResource::class)
    ->attribute('customer_id')
    ->displayUsing('name');
```

**Recomendação: B.** É a sintaxe que a ADR-001 §D3 já esboçou, e é a que
realmente usa o princípio central da ADR-001 §2 (identidade separada da
persistência): o `id` do Field é `customer`, a relação tal como a
Resource fala dela, `attribute` é `customer_id`, a coluna, a divergir
exatamente da mesma forma que `Text::make('seo_title')
->attribute('title')` já diverge no próprio exemplo da ADR-001 §2. O
`Relation::make('customer_id')` colapsa essa distinção de volta a um
único nome, precisamente o que a ADR-001 §2 foi escrita para evitar.

O `->attribute()` mantém a regra de omissão já existente: sem
declaração, deriva `{id}_id` (`customer` -> `customer_id`), o caso comum
mantém-se tão trivial como o `Text::make('title')` é hoje. Só se declara
quando a coluna diverge mesmo, tal como qualquer outro Field.

O `->displayUsing()` é opcional, com omissão para a coluna literal
`'name'` na Resource relacionada, sobreponível
(`->displayUsing('company_name')`) quando diverge. Isto é uma
convenção, não um conceito no `ResourceMetadata` (um método
`displayField()` no `ResourceMetadata` foi considerado e explicitamente
adiado, ver §7), mantido como uma string simples por omissão para que o
caso comum não precise de declaração extra, a mesma forma que
`Field::attribute()` já usa. Adiado por desenho: nada aqui é uma cópia
do `Select::relationship('customer', 'name')` do Filament, a declaração
vive no Field, no grafo Resource/Blueprint/Field a que a ADR-001 já se
comprometeu, não num método de relação Eloquent que a pessoa teria de
escrever primeiro. **Falha alto, não em silêncio:** se os registos da
Resource relacionada não tiverem uma chave `'name'` e nenhum
`->displayUsing()` foi dado, isto tem de lançar uma exceção clara ao
compilar o Blueprint ou no primeiro render, nunca renderizar uma célula
em branco, uma omissão silenciosa errada é pior do que exigir a
chamada explícita.

## 4. Decisões resolvidas

Cada questão em aberto do rascunho original, fechada abaixo. Registada
com o seu raciocínio, não só a resposta, para uma revisão futura saber
porquê.

### 4.1 `elo:sync` e chaves estrangeiras

Um Field `BelongsTo` produz duas operações, não uma:

```
AddColumn(orders.customer_id)
AddForeignKey(orders.customer_id -> customers.id)
```

O `AddForeignKey` é a sua própria classe em `Sync\Operations`, o
`ColumnDefinition` não ganha nenhum conhecimento de constraints, a
mesma separação que `CreateTable` e `AddColumn` já mantêm entre si. A
migration gerada usa o `foreignId('customer_id')->constrained('customers')`
do Laravel, não um `unsignedBigInteger()` + `foreign()->references()->on()`
manual, menos código gerado para o mesmo resultado. A política aditiva
(D7) mantém-se sem alteração: o `elo:sync` só produz um
`AddForeignKey`, nunca altera nem elimina um, exatamente como qualquer
outra operação hoje.

### 4.2 N+1 no `ResourceTable`

O `RepositoryQuery` ganha `whereIn(string $column, array $values): static`.
Não é um contrato congelado novo, segundo a ADR-001 §5 só o `Repository`
está congelado, o `RepositoryQuery` não está, e o `paginate()` já se
juntou a esta mesma interface depois do facto "once ResourceTable was
the real consumer that needed it, not by anticipation" (ver o seu
próprio docblock). O `whereIn()` é o mesmo tipo de adição, pela mesma
razão: o `ResourceTable` agrupa a chave estrangeira de todas as linhas
numa única chamada `whereIn()` contra o Repository da Resource
relacionada, em vez de uma query por linha. Sai na mesma mudança que o
`BelongsTo`, não adiado, o `ResourceTable` é um consumidor real hoje,
isto não é infraestrutura construída para um consumidor futuro
hipotético.

### 4.3 De onde vêm as opções do select

A v1 carrega todos os registos via o próprio
`Repository::query()->orderBy(...)->get()` da Resource relacionada,
nenhum mecanismo novo. Correto para a escala da demo, explicitamente
não a resposta final para uma tabela de Customers com 100 mil linhas.
Pesquisa/paginação no select fica registada como item futuro, revisitada
só quando uma Resource real precisar, não desenhada especulativamente
agora.

### 4.4 `onDelete`

Nenhuma API `->onDelete()` sai na v1. A chave estrangeira gerada não
leva nenhuma cláusula `ON DELETE` explícita, que é `RESTRICT`/`NO ACTION`
por omissão em MySQL, Postgres, e SQLite com chaves estrangeiras
ativas, o comportamento seguro por construção, sem precisar de código
para o ter. `->onDelete('cascade')`, `->onDelete('setNull')` (que
também exigiria a coluna ser nullable) ficam registados como item
futuro, construídos só quando uma Resource real precisar de um.

### 4.5 Relações entre Modules

Já resolvido pela arquitetura existente, nenhuma regra nova, nenhum
código. O `Module` é `resources()` + `menu()` e mais nada, não há
nenhuma fronteira em tempo de execução entre Modules para atravessar.
O `BelongsTo::resource()` recebe uma `class-string<Resource>`, o
autoloading do PHP resolve-a da mesma forma independentemente de que
Module, se algum, a declara.

## 5. O que esta ADR não está a decidir

- `HasMany`, `HasOne`, `BelongsToMany`, relações polimórficas, fora de
  âmbito, cada uma é a sua própria ADR futura assim que uma Resource
  real precisar de uma.
- Edição aninhada/inline do registo relacionado (criar um Customer de
  dentro do formulário da Order) está fora de âmbito, o `BelongsTo`
  só seleciona um registo já existente.
- Opções de select pesquisáveis/paginadas, registado, não desenhado
  (§4.3).
- Políticas de `onDelete` além da omissão da base de dados, registado,
  não desenhado (§4.4).

## 6. Sobre o Filament como referência, não especificação

O `Select::make('customer_id')->relationship('customer', 'name')`
resolve bem o mesmo problema, e não há mérito em inventar uma forma
diferente só para ser diferente. A diferença que importa é onde a
declaração vive: o Filament configura um componente de formulário em
cima de um método de relação Eloquent que o developer escreve primeiro
(`public function customer(): BelongsTo`), a declaração do Elo é a
relação, no grafo `Resource`/`Blueprint`/`Field` a que a ADR-001 já se
comprometeu, a informar `ResourceForm`, `ResourceTable`, `elo:sync`, e
eventualmente API/Export a partir de uma única fonte, sem pedir um
método de relação Eloquent primeiro. O Filament é validação de mercado
de que esta forma de problema tem uma boa solução, uma referência de
UX/comportamento, não algo para copiar nem para divergir de propósito.

## 7. Explicitamente adiado, não desenhado aqui

- `ResourceMetadata::displayField()`, uma Resource a declarar o seu
  próprio atributo de apresentação por omissão uma vez, em vez de cada
  `BelongsTo` que aponta para ela repetir `->displayUsing('name')`.
  Melhoria de ergonomia real, não construída agora: o `BelongsTo` ainda
  não está implementado, por isso não há nenhum consumidor real para
  confirmar que o `ResourceMetadata` precisa deste conceito. O primeiro
  uso real decide se isto merece o seu lugar.

## 8. Estado

Aceite. `src/Fields/BelongsTo.php`, a operação `AddForeignKey`, e o
`RepositoryQuery::whereIn()` são os próximos.
