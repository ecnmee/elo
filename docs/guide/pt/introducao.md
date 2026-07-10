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
- Um Field real: [`Text`](fields.md).
- Uma implementação real de Repository: `EloquentRepository`.
- Os tokens de design (`tokens.css`) e a folha de estilo base do painel
  (`elo.css`).

## O que ainda não existe

- Um painel de administração a funcionar que possas visitar num browser.
  O `ResourceForm` (o componente Livewire que vai de facto renderizar um
  formulário) ainda não foi construído.
- Rotas, por isso não há nenhum URL tipo `/admin` para visitar.
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
- As [ADRs](../adr), para o raciocínio por trás de cada decisão de
  desenho, não só o que faz.
