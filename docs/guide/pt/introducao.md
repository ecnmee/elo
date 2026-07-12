🇵🇹 Português | 🇬🇧 [English](../en/introduction.md)

# Introdução

Este guia documenta o Elo tal como existe hoje, não como vai existir
quando estiver terminado. Cresce a cada passo real de implementação, da
mesma forma que o [PROGRESS.md](https://github.com/ecnmee/elo/blob/main/PROGRESS.pt.md)
acompanha o desenvolvimento num relance. Se algo não está neste guia,
ainda não foi construído, consulta o PROGRESS.md para saber o que vem a
seguir.

## O que existe agora

- Os sete contratos públicos: `Module`, `Resource`, `Blueprint`, `Field`,
  `Action`, `Layout`, `Repository`.
- Um Field real: [`Text`](campos.md).
- Uma implementação real de Repository: `EloquentRepository`.
- Os tokens de design (`tokens.css`) e a folha de estilo base do painel
  (`elo.css`).
- `ResourceForm`, um formulário Livewire de criação/edição a funcionar
  para qualquer Resource que definires, ver [Resources e formulários](resources.md).

## O que ainda não existe

- Um ecrã de listagem, o `ResourceTable` ainda não foi construído.
- Rotas, por isso não há nenhum URL tipo `/admin` para visitar, colocas o
  `<livewire:elo-resource-form>` numa view que já tenhas.
- `elo:sync`, por isso as migrations ainda têm de ser escritas à mão.
- Qualquer Field além do `Text` (`RichText`, `Image`, e o resto vêm a
  seguir).

Se estás a avaliar o Elo para um projecto real hoje, ainda não está
pronto para isso, este guia existe para quem está a acompanhar o
desenvolvimento, a testar peças isoladamente, ou a contribuir.

## Para onde ir a seguir

- [Conceitos](conceitos.md), as sete ideias sobre as quais o Elo é
  construído, explicadas em linguagem simples.
- [Fields](campos.md), como definir e usar o único Field que existe.
- [Resources e formulários](resources.md), o primeiro fluxo real de ponta
  a ponta, define um Resource, obtém um formulário a funcionar a partir
  dele.
- As [ADRs](../adr), para o raciocínio por trás de cada decisão de
  desenho, não só o que faz.
