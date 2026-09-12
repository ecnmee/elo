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
substituir) o `Action::visibleWhen()`, resolvido no §4 abaixo: **a
`Action` mantém-se sem conhecimento do actor, o `ActionRunner` passa a
ser a parte consciente do actor.**

```php
Action::make('reactivate')
    ->visibleWhen(fn ($record) => $record['status'] !== 'active');
    // continua exactamente assim, sem segundo argumento, nenhuma
    // autorização aqui, o ActionRunner verifica o Gate em separado,
    // antes de chamar o handler
```

### 3.3 Contra o que é que o Gate autoriza mesmo

Os dados do próprio Elo circulam como arrays simples em todo o lado
(`Repository::find(): ?array`, todo registo que o `ResourceTable`/
`Action` alguma vez vê é um array), as Policies do Laravel são
escritas por convenção contra o Model Eloquent (`update(User $user,
Post $post)`), passar o array do Elo directamente para o
`Gate::authorize()` partiria em silêncio qualquer Policy escrita da
forma normal. Resolução: o `Repository` ganha `findModel($id): ?object`,
estreito, usado só pelo ponto de chamada da autorização (`ActionRunner`,
`ResourceController`), o resto do contrato do Elo (Fields, as linhas
do `ResourceTable`, os handlers de `Action`) mantém-se baseado em
arrays, sem alteração. Não é uma via de escape genérica de volta para
Models, um método, um chamador, mantido estreito de propósito.

## 4. Questões em aberto

### 4.1 Resolvida: como a autorização se liga a uma `Action` personalizada

**A `Action` não se torna consciente do actor. O `ActionRunner` torna-se.**

O `ActionRunner` já é o único sítio onde `action` e `record` já
convergem antes do handler correr, acrescentar `actor` ali é completar
uma forma já meio construída, não inventar uma nova:

```php
$runner->run(
    action: $action,
    record: $record,
    actor: $actor, // ?Authenticatable, null para um guest, o Gate::forUser(null) já lida bem com isso
);
```

Internamente, o `ActionRunner` resolve o model do registo via
`Repository::findModel()` (§3.3) e chama `Gate::forUser($actor)
->authorize($action->id(), $model)` antes de invocar o handler, nunca
depois. Isto protege uma chamada directa ao `ActionRunner`, não só o
botão na UI, o `visibleWhen()` só alguma vez decidiu se o botão
renderiza, nunca foi a fronteira real.

O `visibleWhen()` mantém exactamente a sua forma e significado actuais,
um argumento, o registo, uma questão de regra de negócio. A
autorização é uma segunda questão, separada, respondida pelo Gate,
perguntada pelo `ActionRunner`, nunca dobrada dentro do callback do
`visibleWhen()`. As duas vão muitas vezes precisar de se combinar para
decidir se um botão finalmente aparece (escondido se qualquer uma
disser não), essa composição acontece onde o botão renderiza, não ao
fundir as duas perguntas num único hook.

Deliberadamente não construído ainda: qualquer objecto
`ActionContext`/`ActorContext` a agrupar actor+registo+action juntos.
Os três parâmetros nomeados do `ActionRunner::run()` chegam para o que
se sabe hoje, um objecto de contexto é exactamente o tipo de
abstracção contra a qual a ADR-003 já avisa, construída antes de uma
segunda forma real provar que é precisa.

### 4.2 Ainda em aberto

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

Proposta, uma questão resolvida. O §4.1 está fechado, a `Action`
mantém-se sem conhecimento do actor, o `ActionRunner` passa a ser a
parte consciente do actor, o `findModel()` do §3.3 é o que torna essa
resolução realmente funcionar contra Policies normais do Laravel. Os
três itens restantes no §4.2, descoberta de Policy, a omissão sem
Policy, e bulk actions, não se bloqueiam entre si nem bloqueiam a
implementação da forma que o §4.1 bloqueava, podem ser resolvidos a
par do primeiro código a sério, o `API/Export` mantém-se uma restrição
assinalada, não um bloqueio.
