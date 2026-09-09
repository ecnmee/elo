🇵🇹 Português | 🇬🇧 [English](../en/ADR-005-elo-authorization.md)

# ADR-005 - Autorização (rascunho)

**Estado:** Proposta, ainda não aceite, escrita para revisão antes de
qualquer código
**Data:** 2026-09-09
**Depende de:** ADR-001 (Arquitetura), ADR-002 (Filosofia), ADR-004
(Field de Relação, precedente de como esta ADR está estruturada)

---

## 1. Contexto

O Elo não tem nenhum conceito de autorização hoje. Toda Resource
registada é visível no `Navigation`, toda rota resolve para qualquer
visitante, toda Action de linha renderiza para qualquer registo. Isto
era aceitável enquanto a demo não tinha ecrã de login nenhum, deixa de
ser aceitável assim que existir um segundo papel de utilizador real.

Levantado como duas preocupações distintas, correctamente mantidas
separadas por quem as levantou:

> "gerir" (gerir, no dia a dia) vs "autorizar" (decidir quem pode)

Concretamente, isso divide-se em duas superfícies diferentes:

- **Ao nível da navegação.** Esta pessoa deve ver `Customers` na barra
  lateral, de todo, `viewAny`, no vocabulário das Policies do Laravel.
- **Ao nível do CRUD.** Dado que esta pessoa consegue ver `Customers`,
  pode criar um, editar este específico, eliminar aquele, `create`/
  `update`/`delete` por registo.

Ambas em falta. Nenhuma é uma adição pequena, esta ADR trata-a com o
mesmo peso que a ADR-004 deu ao `BelongsTo`, questões em aberto
listadas e fechadas uma de cada vez, nenhum código antes da forma estar
assente.

## 2. Motores da decisão

- **Não inventar o que o Laravel já resolveu.** Todo developer Laravel
  já conhece Gates e Policies, `viewAny`/`view`/`create`/`update`/
  `delete` não é vocabulário do Elo para reinventar, é da framework.
  A própria regra da ADR-002, não construir uma versão pior de um
  problema já resolvido só para não parecer uma cópia.
- **O `CoreBoundaryTest` continua a aplicar-se.** O core só depende de
  si mesmo, Illuminate, e Livewire. A camada de autorização do Laravel
  (`Illuminate\Auth\Access`) já está dentro dessa fronteira, um package
  de permissões de terceiros não estaria, isto sozinho assenta
  "construir em cima do Gate/Policy do Laravel, não acrescentar
  dependência" como a única opção realmente disponível sem violar o
  `CoreBoundaryTest`.
- **Não confundir com `Action::visibleWhen()`.** Esse método já existe,
  e já esconde uma Action de linha por registo, o `reactivate` não
  aparece para um `Product` já activo. Essa é uma questão de regra de
  negócio, guiada pelos próprios dados do registo. "Este actor pode
  eliminar este registo" é uma questão guiada pelo actor, um eixo
  completamente diferente. As duas vão muitas vezes precisar de se
  combinar (com E lógico) para decidir se um botão finalmente
  renderiza, mas dar-lhes o mesmo nome esconderia que respondem a
  perguntas diferentes, vale a pena mantê-las visivelmente separadas na
  API mesmo onde a lógica de render as compõe.

## 3. Forma

Duas superfícies, dois hooks diferentes, ambos a consultar o próprio
`Gate` do Laravel por baixo:

### 3.1 Ao nível da navegação: `viewAny`

O `Navigation::groups()` já constrói a sua lista a partir do
`ResourceLocator::all()`, um loop, um único sítio. Ganha uma
verificação por Resource:

```php
if (Gate::denies('viewAny', $resource->repository()->modelClass())) {
    continue;
}
```

Exige que o `Repository` exponha a classe do modelo com que trabalha, o
`EloquentRepository` já a conhece (argumento do construtor), uma
adição pequena, real, não especulativa, do mesmo tipo que o
`Repository::table()` já foi.

### 3.2 Ao nível do CRUD: `view`/`create`/`update`/`delete`

As três acções do `ResourceController` (`index`, `create`, `edit`)
ganham cada uma uma chamada `Gate::authorize()` antes de renderizar, o
`create` verifica `create`, o `edit` verifica `update` contra o registo
carregado, o `index` verifica `viewAny` (a mesma verificação que o
`Navigation` usa, uma pessoa não devia conseguir chegar por URL a uma
rota que a barra lateral já escondeu).

Os links Edit/Delete do `ResourceTable`, e qualquer `Action` de linha,
ganham uma verificação de autorização por registo, composta com (não a
substituir) o `Action::visibleWhen()`:

```php
Action::make('reactivate')
    ->visibleWhen(fn ($record) => $record['status'] !== 'active')
    // autorização não é um segundo visibleWhen(), ver §4, ainda em aberto
```

## 4. Questões em aberto, ainda não respondidas

Registadas aqui para a ADR ser honesta sobre o que "Aceite" ainda
deixaria por decidir, não porque alguma delas esteja respondida.

- **Como é que a autorização se liga mesmo a uma `Action` personalizada?**
  O `visibleWhen()` recebe o registo, decide a visibilidade a partir
  dos seus dados. A autorização também precisa do utilizador actor,
  a `Action` não tem noção nenhuma de "quem está a correr isto" hoje,
  o `ActionRunner` chama o handler directamente. A `Action` ganha um
  segundo método, consciente do actor (risco: dois hooks de
  visibilidade que um autor de Resource tem de se lembrar de usar
  correctamente, e de lembrar qual é qual), ou o callback do
  `visibleWhen()` simplesmente ganha o utilizador actual como segundo
  argumento (risco: transforma silenciosamente um hook em duas
  responsabilidades, exactamente a confusão que o §2 argumentou
  contra)? Nenhuma resposta é obviamente certa, esta é provavelmente a
  única questão que vale a pena resolver antes de todo o resto.
- **Descoberta de Policy.** O Laravel descobre automaticamente uma
  Policy a partir de uma classe Model por convenção de nome. O Elo
  confia nisso por completo (uma Resource sem Policy descobrível
  simplesmente não tem autorização, a condizer com o comportamento
  por omissão do próprio Laravel, aberto por omissão), ou a `Resource`
  ganha uma declaração explícita `->policy()` no `ResourceMetadata`
  para os casos que a convenção de nome não alcança? A inclinar para
  confiar primeiro na descoberta do próprio Laravel, só acrescentando
  uma sobreposição quando a Policy de uma Resource real não conseguir
  ser encontrada por convenção, o mesmo precedente de "lançar o
  subconjunto honesto" que o `Number` e o `BelongsTo` seguiram os dois.
- **O que acontece sem nenhuma Policy.** Uma Resource cujo Model não
  tem nenhuma Policy registada, o `Gate::denies()` devolve `false`
  (permitido) pela omissão do próprio Laravel, o que significa que uma
  demo sem autenticação e sem nenhuma Policy configurada continua a
  comportar-se exactamente como hoje, sem regressão, mas vale a pena
  afirmar isto explicitamente em vez de deixar para descobrir por
  surpresa.
- **Bulk actions.** As bulk actions do `ResourceTable` correm contra
  todos os ids seleccionados. A autorização presumivelmente precisa de
  verificar cada registo, não só o primeiro, o que acontece quando
  alguns estão autorizados e outros não, ignorar em silêncio os não
  autorizados, ou falhar o lote inteiro? Não desenhado aqui.
- **API/Export.** A ADR-004 §3 já nomeou estes como consumidores
  futuros da declaração de um Field. O mesmo é verdade aqui, seja qual
  for a forma que a autorização tomar precisa de fazer sentido também
  para uma futura camada de API, não ser refeita quando uma chegar.
  Não desenhado aqui, assinalado para que a eventual ADR de API herde
  esta restrição.

## 5. O que esta ADR não está a decidir

- Autorização ao nível da linha/multi-tenant (um utilizador a ver só
  as suas próprias Orders, não todas as Orders) é um problema diferente
  e maior, as Policies do `Gate` conseguem exprimi-lo mas o Elo não
  está a construir nada específico de multi-tenant aqui, fora de
  âmbito.
- Roles, grupos de permissões, qualquer coisa que se pareça com uma UI
  para gerir quem tem que permissão. A camada Gate/Policy do Laravel é
  o mecanismo, a própria classe Policy de uma Resource decide as regras
  reais, o Elo não fornece nem roles nem um ecrã de gestão de roles.
- A ideia "master vs dev", um nível que tranca que opções de
  personalização um não-developer pode mudar, levantada a par deste
  mesmo pedido. Isso está mais próximo de uma questão de autorização de
  configurações/definições do que de autorização de dados,
  provavelmente o mesmo mecanismo subjacente assim que isto aterrar,
  não desenhado junto com isto, registado em separado no `PROGRESS.md`.

## 6. Estado

Proposta. Não implementada. A primeira questão do §4, como é que uma
`Action` personalizada se torna consciente do actor sem duplicar em
silêncio o `visibleWhen()`, bloqueia começar por qualquer outro lado,
tudo o resto a jusante (links Edit/Delete do `ResourceTable`, bulk
actions) depende dessa forma estar assente primeiro.
