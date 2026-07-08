🇵🇹 Português | 🇬🇧 [English](PROGRESS.md)

# Progresso do Desenvolvimento

Um registo público e contínuo do ponto em que o Elo está. O detalhe técnico
completo vive em [`docs/adr/`](docs/adr); esta página é a versão em
linguagem simples, actualizada à medida que o projecto avança.

---

## Estado actual: sete contratos implementados

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

**A seguir:**

- O design system (tokens de CSS base do painel).
- O primeiro Field real (`Text`), de ponta a ponta.
- O `PostResource` construído de ponta a ponta sobre os sete contratos.

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
