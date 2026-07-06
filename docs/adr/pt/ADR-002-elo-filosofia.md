🇵🇹 Português | 🇬🇧 [English](../en/ADR-002-elo-philosophy.md)

# ADR-002 - Filosofia do Elo

**Status:** Aceite
**Data:** 2026-07-04
**Depende de:** ADR-001 (Arquitectura do Elo)

A ADR-001 responde ao "como". Esta ADR responde ao **"o que o Elo é, e o que
nunca será"**. O objectivo é proteger a identidade do framework ao longo dos
próximos anos de evolução - este documento é a referência a consultar sempre
que uma decisão futura parecer razoável isoladamente, mas ameaçar desviar o
projecto do seu propósito original.

---

## 1. O Elo é

- Um **framework para gestão de websites** - não uma ferramenta genérica.
- **Declarativo** - o programador descreve o domínio, o Elo constrói a operação.
- **PHP-first** - nenhuma funcionalidade essencial depende de escrever JS no lado do consumidor.
- **Laravel-native** - usa as convenções e mecanismos nativos do Laravel (service container, package discovery, migrations) em vez de reinventá-los.
- **Extensível por composição** - Blueprints compõem-se, módulos instalam-se como pacotes.
- **Convencional, mas configurável** - comportamento por omissão cobre a maioria dos casos, sem nunca prender o consumidor a ele.

## 2. O Elo não é

- Um **page builder** (edição visual de layout de página).
- Um **website builder** (não gera o site, gere os dados de um site já construído).
- Um **construtor visual tipo WordPress**.
- Um **low-code genérico** (o alvo é gestão de conteúdo/dados de sites, não qualquer aplicação).
- Um **ERP**.
- Um **CMS monolítico** (é um pacote instalado site a site, não uma plataforma central).
- Um **substituto do Laravel** - é construído sobre o Laravel e assume-o como dependência, nunca o esconde.

Qualquer proposta futura que empurre o Elo para uma destas categorias deve
ser recusada por definição, independentemente do mérito técnico isolado.

---

## 3. Princípios arquitecturais

### 3.1 O programador descreve o domínio. O Elo constrói o resto.

Princípio fundador (ADR-001, secção 1).

### 3.2 Convenção sobre configuração, nunca sobre personalização.

```php
Text::make('title');   // funciona imediatamente, comportamento por omissão
```

```php
Text::make('title')
    ->renderer(CustomRenderer::class)
    ->validator(CustomValidator::class);   // personalização total, sempre disponível
```

O mesmo vale ao nível do Resource: substituir `TableComponent`,
`FormComponent` ou o `Controller` de um Resource específico é sempre
possível. O comportamento por omissão cobre a maioria dos casos; o
consumidor nunca fica preso a ele.

### 3.3 Duas camadas: contratos congelados, implementações substituíveis.

Esta é a formalização de "everything is replaceable" - com o limite que a
torna consistente com a ADR-001, secção 5.

**Camada 1 - Contratos (congelados, definem o que é o Elo):**
`Module`, `Resource`, `Blueprint`, `Field`, `Action`, `Layout`, `Repository`.
Mudar a assinatura pública destes exige major version (ADR-001, D8).
Substituir um destes contratos por outra coisa não é "estender o Elo" - é
construir um framework diferente.

**Camada 2 - Implementações por trás dos contratos (substituíveis via
service container, à maneira do Laravel - Cache driver, Queue driver):**

```
Repository          → EloquentRepository (trocável: Redis, API externa, ...)
Renderer             → BladeRenderer (trocável)
MigrationWriter      → implementação por omissão (trocável)
BlueprintCompiler    → implementação por omissão (trocável)
RichText Driver      → tiptap (trocável: CKEditor, Monaco, ...)
```

A regra prática: se está na lista de contratos congelados, não se troca -
troca-se o que está *por trás* dele.

### 3.4 O core nunca conhece módulos de terceiros. É o módulo que conhece o core.

Consequência directa de D2/D3 (ADR-001), tornada princípio explícito para
evitar a tentação futura de adicionar excepções para um módulo específico.

**Implicação mecânica, sem excepções:** o core nunca contém código do tipo
`if (class_exists('EloGallery\GalleryModule'))`, nem qualquer forma de caso
especial nomeado. Qualquer capacidade que um módulo de terceiros precise do
core tem de ser um ponto de extensão genérico (discovery, Events), nunca uma
integração pontual.

### 3.5 Regra de ouro do núcleo.

> Se uma funcionalidade só beneficia um módulo específico, não entra no core.
> Só entra quando pelo menos dois módulos independentes precisarem dela, ou
> quando representar uma capacidade transversal do framework.

Mantém o núcleo pequeno e coerente. Aplica-se a toda proposta de adicionar
algo a `elo/src/` - inclui contribuições da própria equipa, não só de
terceiros.

---

## 4. Como usar este documento

Sempre que uma decisão técnica futura for tecnicamente sólida mas gerar
dúvida sobre se "ainda é o Elo", a pergunta a fazer é:

1. Está na lista da secção 2 ("o que não é")? → recusar, sem excepção.
2. Mexe num dos sete contratos congelados? → exige major version, avaliar com
   o mesmo rigor da ADR-001.
3. É uma implementação nova por trás de um contrato existente? → aceitar
   livremente, é o comportamento esperado do sistema (secção 3.3).
4. Beneficia só um módulo? → aplicar a regra de ouro (secção 3.5): fica fora
   do core até haver um segundo caso real.
