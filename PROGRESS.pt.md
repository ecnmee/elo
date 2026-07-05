🇵🇹 Português | 🇬🇧 [English](PROGRESS.md)

# Progresso do Desenvolvimento

Um registo público e contínuo do ponto em que o Elo está. O detalhe técnico
completo vive na [documentação de arquitectura](https://github.com/ecnmee/elo-monorepo/tree/main/docs/adr);
esta página é a versão em linguagem simples, actualizada à medida que o
projecto avança.

---

## Estado actual: sete contratos implementados

**O que já está feito:**

- Arquitectura congelada em três ADRs: hierarquia e contratos, filosofia e
  limites, vocabulário oficial.
- Os dois repositórios já existem: este (pacote público) e o
  [`elo-monorepo`](https://github.com/ecnmee/elo-monorepo) (desenvolvimento,
  onde o core é de facto construído).
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

**A seguir:**

- O design system (tokens de CSS base do painel).
- O primeiro Field real (`Text`), de ponta a ponta.
- O `PostResource` construído de ponta a ponta sobre os sete contratos.

## Porque existe esta página

O Elo está a ser construído em aberto, e a sua arquitectura foi moldada
através de um processo invulgarmente cuidadoso antes de qualquer linha de
código de implementação. Este registo existe para que quem está a
acompanhar não precise de ler cada commit para perceber o ponto em que as
coisas estão, é um atalho, não um substituto da documentação real.

## Como acompanhar mais de perto

- Star ou watch em [`ecnmee/elo`](https://github.com/ecnmee/elo) para actualizações ao nível de releases.
- Watch em [`ecnmee/elo-monorepo`](https://github.com/ecnmee/elo-monorepo) para o desenvolvimento dia a dia.
- As [ADRs](https://github.com/ecnmee/elo-monorepo/tree/main/docs/adr) explicam cada decisão de arquitectura e o raciocínio por trás dela.
