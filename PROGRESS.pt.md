🇵🇹 Português | 🇬🇧 [English](PROGRESS.md)

# Progresso do Desenvolvimento

Um registo público e contínuo do ponto em que o Elo está. O detalhe técnico
completo vive em [`docs/adr/`](docs/adr); esta página é a versão em
linguagem simples, actualizada à medida que o projecto avança.

---

## Estado actual: o "PostResource de ponta a ponta" está fechado

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
- `EloquentRepository`, o único `Repository` na v1, e a conclusão do
  contrato `Resource::repository()`.
- `ResourceForm`, o primeiro componente Livewire real: renderiza o
  Blueprint de um Resource como formulário de criação/edição, resolve a
  ordem dos campos a partir do Layout (ou cai para a ordem de declaração
  quando não há nenhum), deriva a validação directamente do
  `isRequiredOn()` de cada Field, e grava através do Repository do
  Resource. Testado de ponta a ponta contra um Model Eloquent real numa
  base de dados em memória: criação, validação, edição, e o evento
  `elo-resource-saved`.
- Rotas: um `ResourceController` genérico (criar/editar, nunca um
  controller por Resource), com rotas geradas automaticamente a partir de
  um mapeamento slug-para-Resource num `config/elo.php` publicável. A
  descoberta completa de Modules ainda não está ligada, esta é
  deliberadamente a versão mínima real, um mapa mantido à mão, até
  aparecer um caso real que peça mais. `php artisan vendor:publish
  --tag=elo-assets` publica o `elo.css` para onde as rotas conseguem de
  facto carregá-lo.
- O helper `elo()` do front-end: `elo()->resource('posts')->where(...)->get()`,
  resolve um Resource registado pelo slug e devolve o seu `RepositoryQuery`
  directamente. Só `resource()` existe, `settings()`, `menu()`, e `form()`
  do esboço original da ADR ainda não têm um caso real por trás.
- Um `PostResource` completo e realista em `examples/`, código de
  referência para copiar para uma aplicação real, deliberadamente não
  distribuído como parte do pacote instalável (um Post de blog é
  específico de domínio, o core mantém-se pequeno de propósito).
- O motor do `elo:sync`: `ColumnDefinition`, `Operation` e as suas duas
  implementações v1 (`CreateTable`, `AddColumn`), `MigrationWriter`,
  `SchemaSnapshot`, e `SchemaDiff`, tudo testado, incluindo migrations
  geradas a correr de verdade contra SQLite. Aditivo apenas por
  construção, uma coluna existente nunca é alterada nem removida, por
  isso uma coluna acrescentada à mão fora do Elo sobrevive sempre a um
  sync.
- `elo:sync`, o comando artisan em si. Lê cada Resource em
  `config('elo.resources')`, compara os seus Fields contra a tabela do
  seu Repository, e escreve uma única migration com o que faltar em todos
  eles, nada de todo quando já está tudo em sincronia. Fecha uma emenda
  ao contrato pelo caminho, `Repository::table()`, o mesmo tipo de
  conclusão a posteriori que `Resource::repository()` foi, o contrato
  ficou em silêncio sobre isto até uma implementação real precisar de
  saber.
- `ResourceTable`, o segundo componente Livewire real: lista os registos
  de um Resource, ordenável por coluna, paginado, com apagar em linha.
  Completa o `RepositoryQuery` com `paginate()`, o mesmo tipo de conclusão
  a posteriori que `Repository::table()` foi, o `RepositoryQuery` ficou
  apenas com `where()/orderBy()/get()/first()` até um consumidor real
  precisar de uma página em vez do conjunto todo. Lê
  `Blueprint::getFields()` directamente, tal como o `SchemaDiff`, não
  `getLayout()` como o `ResourceForm` faz, as colunas de uma tabela não
  são agrupadas em secções da mesma forma que os campos de um formulário,
  por isso este consumidor continua a não resolver composição da forma
  que o `ResourceForm` resolve, ver a nota do `BlueprintCompiler` abaixo.
- O routing ganhou uma terceira rota fixa, `index`, ao lado de `create` e
  `edit`, resolvida da mesma forma, através do mesmo controller genérico.
- `ActionRunner`, a ligar o handler de uma `Action` (já testado isolado
  desde que a `Action` foi lançada) a um Resource real: resolve uma
  Action por id a partir do Blueprint, carrega o registo através do
  Repository, corre o handler, guarda o que ele devolver. O
  `ResourceTable` agora renderiza acções de linha como botões por
  registo e acções em massa numa toolbar acima da tabela, ambas ligadas
  directamente ao `ActionRunner`. `Delete` continua um método próprio da
  tabela, sempre disponível, não uma `Action`, toda a tabela precisa
  dele independentemente do que um Resource declare.
- `ModuleRegistry` e `ResourceLocator`, a fechar D1/D2. Módulos de
  terceiros registam-se através de `elo()->registerModule()` (D2);
  `Elo::resource()`, `ResourceController`, e `SyncCommand` já não fazem
  cada um o seu próprio `Config::get("elo.resources.{$slug}")`, os três
  lêem agora através do `ResourceLocator`, um sítio em vez de três.
  `Resource::slug()` juntou-se também, um Module declara resources como
  uma lista simples, algo tem de transformar um class-string num slug
  navegável, por omissão segue a convenção do nome da classe
  (`PostResource` -> `posts`), substituível, o mesmo princípio que
  `Field::attributeName()` já usa contra `attribute()`.

**A seguir:**

Ainda a construir a demo real (`Product`, `Service`, `Customer`, `Order`
num `BusinessModule`). Duas descobertas reais até agora, não planeadas,
ambas corrigidas directamente, não adiadas: Laravel 13 não era suportado,
`illuminate/support` estava preso a `^11.0|^12.0`, alargado para
`^11.0|^12.0|^13.0`, mais `orchestra/testbench` para `^9.0|^10.0|^11.0`
para a cadeia de testes correspondente. Depois o próprio `elo:sync`, a
gerar um único ficheiro de migration para todas as tabelas alteradas
numa corrida, legível com quatro Resources, não à escala real (diff
irrevisável, sem git blame por tabela, sem rollback por tabela).
Corrigido: `SyncCommand` passa a escrever uma migration por tabela,
`SchemaSnapshot`, `SchemaDiff`, `Operation`, e `ColumnDefinition`
intocados, só `SyncCommand` e `MigrationWriter` (um novo parâmetro
opcional `$timestamp` em `write()`) mudaram. Depois a descoberta mais
séria até agora: `CreateTable::up()` nunca gerava uma coluna `id()`, o
`Schema::create()` não acrescenta uma sozinho, por isso toda a tabela que
o `elo:sync` já tinha gerado ficava sem chave primária nenhuma. Toda a
leitura continuava "a funcionar", um `SELECT *` não precisa de chave
primária, mas todo o registo voltava sem `id`, o que partiu o
`wire:key` do `ResourceTable` de imediato, na primeira vez que existiu um
registo real para renderizar. Nenhum teste apanhou isto porque todo o
teste existente construía a sua própria tabela à mão, já com
`$table->id()` lá posto, nunca através de uma chamada real a
`CreateTable::up()`. Corrigido: `id()` passa a ser incondicional, sempre
a primeira linha. Mais duas descobertas ao usar mesmo a demo, não só ao
construí-la: `ResourceForm::save()` terminava em silêncio, sem
redireccionar, sem feedback visível, um pedido Livewire a terminar sem
nada para mostrar lê-se como "não aconteceu nada". O `ResourceTable`
tinha Delete e as Actions personalizadas que um Resource declarasse, mas
nenhuma forma de voltar a um registo depois de criado, sem link de
Editar nenhum. Ambas corrigidas: `save()` redirecciona agora para o
`index` do Resource, cada linha liga à sua página de edição. Ainda sem
Field de relação (`Order` não consegue declarar "pertence a Customer"),
em aberto, registado abaixo. Também registado, não construído: uma caixa de
confirmação com a marca do Elo em vez da nativa do browser, botões com
ícones (SVG, não fonte de ícones, por preferência explícita) em vez de
texto, e navegação, o `Module::menu()` existe como hook desde o início
sem nada a renderizá-lo ainda, a demo tornou isso concreto pela primeira
vez sem ainda mudar a prioridade. Mais uma lacuna real revelada ao clicar
mesmo na demo: `discontinue`/`reactivate` e `archive`/`unarchive`
apareciam ambos em todo o Product independentemente do seu status,
`reactivate` num Product já activo não faz sentido nenhum. O `Action`
não tinha forma de responder "este Action aparece para este registo
específico", só `isVisibleOn()`, uma pergunta estática por contexto
(index/create/edit). Corrigido com um método novo, ortogonal,
`Action::visibleWhen(callable $condition)`, avaliado por linha no
`ResourceTable`. Por último, do mesmo percurso na demo: botões só com
texto liam-se como ruído assim que vários ficavam lado a lado, e a
tabela em si não tinha resposta nenhuma para um ecrã estreito, ficava
simplesmente uma tabela larga. Corrigido: o `Action` ganhou um
`icon(string $svg)` opcional, SVG cru, cai para o texto quando não
definido; o Edit e o Delete do `ResourceTable` passam a ser só ícone,
incondicionalmente. Abaixo de 768px a tabela vira uma pilha de cartões,
um por registo, só com CSS (`data-label` em cada célula, o mesmo
markup serve os dois layouts, nada duplicado). Ainda sem Field de
relação (`Order` não consegue declarar "pertence a Customer"), e sem uma
caixa de confirmação com a marca do Elo em vez da nativa do browser,
ambos ainda em aberto, registados abaixo.

**Acabado de sair: `Customer` e `Service` preenchidos, a validar a demo**

- Não são Resources novas, `CustomerResource` e `ServiceResource` já
  existiam. Este foi o passo de "validar, não só acrescentar": preencher
  os dois com uma forma realista e ver que fricção sobrou na framework,
  seguindo o plano de acabar o `Number` antes de decidir sobre o Field
  de relação.
- O `CustomerResource` ganhou `phone` (`Text`, opcional) e `status`
  (`Text`, com omissão `'active'`), o mesmo padrão que `Product`/`Order`
  já tinham estabelecido para o status.
- O `ServiceResource` ganhou `active` (`Text`, com omissão `'true'`).
  Revelou uma lacuna real ao fazê-lo: ainda não existe nenhum Field
  Boolean, por isso `active` guarda-se e sincroniza como uma coluna de
  texto simples, `'true'`/`'false'`, não um booleano a sério. O mesmo
  padrão honesto, sem desvio, que o `price` usou em `Text` antes do
  `Number` existir. Registado abaixo, não resolvido aqui de propósito,
  isto sozinho não é razão suficiente para construir um Field inteiro.
- Nenhuma outra fricção surgiu. Dois campos `Text`, uma omissão, e uma
  Section de layout chegaram para os dois, nada na forma da demo desta
  vez fez pressão sobre a framework.

**Acabado de sair: o segundo Field real (`Number`)**

- `Ecnmee\Elo\Fields\Number`, um input numérico simples apoiado numa
  coluna `decimal` (precisão/escala por omissão do Laravel, 8 e 2),
  seguindo exactamente o mesmo contrato que o `Text` já estabeleceu:
  `type()`, view por convenção (`elo::fields.number`), ciclo de vida,
  contexto, valor por omissão. Nenhum conceito novo foi preciso no
  `Field`.
- Deliberadamente genérico: não `Money`, `Currency`, `Integer`, nem
  `Decimal`. Isso seria semântica de negócio (uma moeda, uma regra de
  arredondamento) ou uma escolha de precisão/escala que ninguém pediu
  ainda, o `Number` representa o dado, nada mais, segundo a ADR-003. O
  `PROGRESS.md` estava a observar se a demo precisava de mais do que uma
  forma numérica antes de construir isto, nunca precisou,
  `price`/`total` só precisam de um número.
- O `ProductResource`, `ServiceResource`, e `OrderResource` da demo usam
  agora `Number` para `price`/`total` em vez de `Text`. Correr o `php
  artisan elo:sync` a seguir reportou "already in sync", revelando uma
  lacuna real: o `SchemaDiff` só compara a *presença* de colunas, nunca
  o tipo, por desenho (ADR-001 D7/D8, aditivo apenas), por isso um Field
  a mudar de tipo numa coluna já sincronizada fica invisível para ele.
  As colunas `price`/`total` da demo mantêm-se `string` na base de dados
  até alguém escrever uma migration manual, registado abaixo.
- Documentação de guia (`docs/guide/en/fields.md`,
  `docs/guide/pt/campos.md`) actualizada para documentar os dois tipos
  de Field lado a lado.

**Acabado de sair: ícones e layout responsivo em cartões no `ResourceTable`**

- `Action::icon(string $svg)`/`getIcon()`: opcional, uma string SVG cru
  renderizada sem escapar, o texto continua a ser o fallback quando
  nenhum ícone está definido, por isso todo o Action já existente
  continua a funcionar exactamente como antes.
- O Edit e o Delete do `ResourceTable` passam a ser só ícone, um lápis e
  um caixote do lixo, `aria-label`/`title` carregam o nome acessível que
  nenhum dos dois mostra visivelmente agora. Actions de linha e bulk
  renderizam o seu ícone quando definido, o texto caso contrário, ambos
  totalmente opcionais.
- Abaixo de 768px, o `ResourceTable` renderiza como uma pilha de cartões
  em vez de uma tabela apertada na horizontal, CSS puro, sem JS, sem
  segundo template: `data-label` em cada célula fornece o nome da coluna
  via `::before`, o mesmo markup Blade exacto serve os dois layouts.
- O `ProductResource` e o `OrderResource` da demo usam agora `icon()` em
  todo o Action, através de um pequeno `app/Elo/Icons.php` só na app da
  demo, não no framework, mantido lá de propósito para nenhum Resource
  precisar de uma parede de strings SVG inline.

**Acabado de sair: `Action::visibleWhen()`, condicional por registo**

- O `Action` ganhou `visibleWhen(callable $condition)` e
  `isVisibleFor(mixed $record)`, deliberadamente separado de
  `isVisibleOn()`/contexto: uma verificação de contexto não precisa de
  registo, uma verificação de registo não precisa de contexto, misturar
  os dois teria tornado qualquer um dos dois mais difícil de raciocinar.
- O `ResourceTable` avalia isto por linha agora, um botão Discontinue só
  renderiza para registos cujo status faz sentido descontinuar.
- Na demo, isto transformou `archive`/`unarchive` de Actions bulk em
  Actions de linha, alternar o estado de UM registo é exactamente para
  que serve o `visibleWhen()`, um `cancel` bulk a sério ficou no
  `OrderResource` como exemplo real dessa forma.

**Acabado de sair: `ResourceForm` redirecciona, `ResourceTable` liga a editar**

- `ResourceForm::save()` redirecciona agora para o `elo.index` do
  Resource depois de gravar com sucesso. Antes, gravar terminava sem
  navegação de página e sem pedido de rede visível (é Livewire, não há
  nenhum para observar), por isso alguém a usar o formulário pela
  primeira vez não tinha forma de saber se algo tinha acontecido.
- O `ResourceTable` ganhou um link "Edit" por linha, a apontar para
  `elo.edit`. O Delete já existia como link permanente, não uma Action;
  o Edit era a outra metade disso e simplesmente faltava, a coluna de
  Actions de cada linha levava para todo o lado excepto de volta ao
  próprio registo.
- Ambas encontradas a usar a demo, não a construí-la, a diferença entre
  um Resource compilar e um Resource ser usável.

**Acabado de sair: o `CreateTable` já dá chave primária a toda a tabela**

- Faltava o `id()` em todo o `CREATE TABLE` gerado, uma chave primária
  auto-incremental que o `Schema::create()` do Laravel nunca acrescenta a
  menos que algo chame `$table->id()` explicitamente, e nada chamava.
  Cada coluna que um Resource declarava, mais `timestamps()`, mas nunca a
  única coluna que ninguém declara por ser assumida: a chave primária.
- Encontrado na demo de Business, não por um teste: `/elo/products`
  lançava `Undefined array key "id"` no momento em que existiu um
  `Product` real para renderizar no `ResourceTable`,
  `wire:key="elo-row-{{ $row['id'] }}"` precisa da chave que todo o
  registo supostamente tem.
- Todo o teste existente de SchemaDiff/MigrationWriter/CreateTable tinha
  construído a sua tabela fixture à mão, `Schema::create(..., function
  ($table) { $table->id(); ... })`, com a coluna id acrescentada fora do
  código a testar, por isso nada exercitava de facto uma chamada real a
  `CreateTable::up()` de ponta a ponta contra uma tabela nova e depois lia
  um registo de volta. Dois testes novos fecham essa lacuna directamente:
  um confirma que `id()` é a primeira linha gerada, outro insere mesmo
  duas linhas através de uma migration que o `CreateTable` produziu e
  confirma que voltam chaves primárias reais, a incrementar.

**Acabado de sair: uma migration por tabela, não uma por corrida**

- `SyncCommand` agora agrupa as `Operations` que recolhe por tabela antes
  de as entregar ao `MigrationWriter`, uma chamada a `write()` por tabela
  em vez de uma só para a corrida toda. Quatro Resources a mudar produz
  quatro ficheiros, `create_products_table`, `create_services_table`, e
  por aí fora, não um único ficheiro `sync_elo_resources` a listar os
  quatro.
- `MigrationWriter::write()` ganhou um parâmetro opcional `$timestamp`.
  O `SyncCommand` passa um timestamp base mais um segundo por ficheiro,
  para que os nomes ordenem na mesma sequência determinística em que
  foram gerados, mesmo quando todos os ficheiros são escritos dentro do
  mesmo segundo de relógio, sem `sleep()` nenhum.
- `SchemaSnapshot`, `SchemaDiff`, `Operation`, e `ColumnDefinition`
  ficaram intocados, o agrupamento já existia dentro do `SchemaDiff` (um
  `CreateTable`, ou uma lista de `AddColumn`, nunca misturados, por
  tabela), isto só mudou a forma como o `SyncCommand` entrega esse
  agrupamento ao writer.
- Encontrado a construir a demo de Business, não antecipado: um único
  ficheiro para quatro tabelas continuava legível, o ponto do revisor foi
  que deixa de ser revisável, rastreável por git blame, ou reversível por
  tabela bem antes da escala real, um problema de organização, não de
  performance.

**Acabado de sair: descoberta de `Module`**

- `ModuleRegistry`: uma lista singleton de classes Module registadas,
  `elo()->registerModule(GalleryModule::class)` é o ponto de entrada do
  D2, um pacote de terceiros chama-o a partir do seu próprio
  `ServiceProvider::boot()`, a Package Discovery nativa do Laravel é o
  que faz esse `boot()` correr.
- `ResourceLocator`: o único sítio onde `config('elo.resources')` e o
  `resources()` de cada Module registado se combinam num único mapa de
  slugs. Uma entrada na config ganha numa colisão, explícito vence
  convenção, a mesma regra que `Field::attribute()` já segue contra
  `attributeName()`. `Elo::resource()`, `ResourceController::resolve()`,
  e `SyncCommand::handle()` lêem todos através dele agora, a substituir
  três cópias separadas da mesma chamada `Config::get()`, exactamente a
  duplicação apontada como ponto a vigiar durante a revisão do
  `ResourceTable`, tornou-se real no momento em que passou a existir uma
  segunda fonte de resources.
- `Resource::slug()`: por omissão, o nome da classe, menos um "Resource"
  no fim, kebab-case e pluralizado. Todo o Resource já registado à mão
  continua a funcionar sem alterações, a config ganha sempre
  independentemente do que o slug por omissão da sua classe seria.
- D1 (percorrer `app/Elo/Modules/*` no boot, em cache via `elo:cache`)
  não está construído. Um Module local já pode chamar `registerModule()`
  a partir do próprio `AppServiceProvider::boot()` da app, isso já
  funciona hoje; percorrer o sistema de ficheiros é uma conveniência por
  cima deste mecanismo, não um pré-requisito dele, e ainda não tem um
  caso real a puxar por isso.

**Acabado de sair: `Action`, tornada executável**

- `ActionRunner`: a peça que faltava entre a `Action` saber correr o seu
  próprio handler e os registos de um Resource mudarem de facto. Um
  handler que devolve um array de atributos vê-os fundidos no registo
  carregado e guardados; um handler que devolve outra coisa, `null`, um
  efeito colateral, a sua própria persistência noutro sítio, fica
  intocado, nada é guardado automaticamente. O contrato da `Action` não
  mudou, o `ActionRunner` só acrescenta a busca do registo e o passo de
  guardar por cima dele.
- O `ResourceTable` renderiza cada Action não-bulk visível em
  `Field::CONTEXT_INDEX` como um botão por linha, e cada Action bulk
  numa toolbar acima da tabela, activa assim que pelo menos uma linha
  esteja seleccionada. Ambas chamam o `ActionRunner`.
- Testado com um handler real (`shout`, maiúsculas no título), um handler
  bulk real (`clear-body`, limpa um campo em cada registo seleccionado),
  um handler que não devolve nada (confirma que nada é guardado
  automaticamente), e os dois caminhos de falha: um id de acção não
  registado, um id de registo que não existe.

**Acabado de sair: `ResourceTable`**

- Lista cada Field que o Blueprint de um Resource declara, menos os que
  esconde em `Field::CONTEXT_INDEX` via `hiddenOnIndex()`, pela ordem de
  declaração. Clicar num cabeçalho de coluna ordena por ela, um segundo
  clique inverte a direcção.
- Paginação via o novo `RepositoryQuery::paginate()`, ligado directamente,
  sem a trait `Livewire\WithPagination`: a trait espera um
  `LengthAwarePaginator` real ligado a ela, o `RepositoryQuery` devolve um
  array simples por desenho, a mesma forma que todos os outros métodos de
  query já devolvem, por isso duas propriedades públicas (`page`,
  `perPage`) e dois métodos (`previousPage()`, `nextPage()`) resolvem sem
  puxar a maquinaria do Livewire que a abstracção do Repository não
  precisa de facto.
- Apagar é em linha, na própria linha, a chamar directamente
  `Repository::delete()`.
- Uma terceira rota, `elo.index`, e `ResourceController::index()`,
  registada da mesma forma que `create`/`edit` já estavam.
- Testado através dos helpers de teste do Livewire: cada field como
  coluna, cada registo como linha, o estado vazio, a ordenação e a sua
  inversão de direcção, mudar entre páginas, e apagar a remover mesmo a
  linha e o registo subjacente.
- A nota arquitectural sobre o `BlueprintCompiler` (ver "Adiado" abaixo)
  dizia para observar o que acontecia quando existisse um segundo
  renderer. Agora existe, e a resposta, pelo menos para este, é: nenhuma
  duplicação. O `ResourceTable` lê `Blueprint::getFields()` da mesma forma
  plana que o `SchemaDiff` já faz, nunca toca em `getLayout()`, na
  resolução de `uses()`, ou na validação de referências como o
  `ResourceForm` faz. O gatilho continua sem disparar.

**Acabado de sair: o comando `elo:sync`**

- O `SyncCommand` em si: percorre cada Resource em
  `config('elo.resources')`, resolve a sua tabela via
  `Repository::table()`, os seus Fields via `Blueprint::getFields()`, e
  entrega ambos ao `SchemaDiff`. Cada Operation encontrada em cada
  Resource vai para uma única migration via `MigrationWriter`, não um
  ficheiro por Resource.
- Nada registado, ou nada fora de sincronia, e não escreve ficheiro
  nenhum, só o diz. Herda a garantia aditiva directamente do
  `SchemaDiff`, não há verificação extra no comando em si, não há nada
  para verificar, o comando não consegue produzir um drop ou um alter
  porque aquilo que chama também não consegue.
- `Repository::table()`, uma emenda ao contrato: o comando de sync
  precisava de saber a que tabela pertencem os Fields de um Resource, e o
  `Repository` é a única coisa que sabe como um Resource é persistido de
  facto. A mesma forma que `Resource::repository()` foi antes, completa
  uma dependência que o contrato já implicava, não introduz um conceito
  novo.
- Testado de ponta a ponta: a migration gerada é de facto requerida e
  corrida contra SQLite, tanto para uma tabela nova como para uma coluna
  acrescentada a uma já existente, não só verificada como texto PHP
  gerado.

**Acabado de sair: o motor do `elo:sync`**

- `ColumnDefinition` gera uma chamada de coluna do Blueprint a partir do
  que um Field já sabe: tipo, nulidade e valor por omissão,
  deliberadamente sem transportar nada que um Field ainda não declare.
- `CreateTable` e `AddColumn`, as duas implementações v1 de `Operation`,
  cada uma a gerar o seu inverso exacto para `down()`.
- `MigrationWriter` gera um ficheiro de migration real, de classe anónima,
  a partir de uma lista ordenada de Operations, `up()` pela ordem dada,
  `down()` em ordem inversa exacta. Testado contra migrations a correr de
  verdade em SQLite, não apenas o PHP gerado como string.
- `SchemaSnapshot`, uma vista só de leitura do que já existe na base de
  dados. Só responde à existência de tabela/coluna, nunca ao tipo, o que
  decorre directamente da política aditiva da ADR-001: como uma coluna
  existente nunca é alterada automaticamente, o seu tipo exacto nunca
  precisa de ser conhecido.
- `SchemaDiff` compara os Fields de um Resource contra um `SchemaSnapshot`
  e produz as Operations necessárias para pôr a base de dados em dia: uma
  tabela em falta vira um único `CreateTable`, uma coluna em falta numa
  tabela existente vira um `AddColumn` por field. Uma coluna que já
  existe, ou uma que um Field deixou de declarar, fica intocada, sem
  drops, sem alters, sem excepções.

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

- Um Field de relação (`BelongsTo::make('customer', CustomerResource::class)`,
  ADR-001, D3). Revelado pela demo de Business: `OrderResource` não
  consegue declarar que uma Order pertence a um Customer ou a um
  Product, só ficou com um `reference` de texto livre em vez disso.
  Provavelmente a lacuna mais significativa que a demo encontrou até
  agora, possivelmente a que faria o Elo exprimir dados relacionais a
  sério, ainda não implementada porque a demo ainda não acabou de provar
  exactamente que forma isto precisa de ter.
- ~~Um Field numérico.~~ Lançado, ver "Acabado de sair: o segundo Field
  real (`Number`)" acima.
- Deteção de mudança de tipo no `SchemaDiff`. Confirmado ao lançar o
  `Number`: mudar o `type()` de um Field numa coluna que já existe não
  produz nenhuma operação, o `elo:sync` reporta "already in sync" e o
  tipo real da coluna fica por mudar. Correto para o desenho aditivo
  apenas tal como está escrito (ADR-001 D7/D8: nunca altera, nunca
  elimina), mas significa que o tipo declarado de um Field e o tipo
  real da coluna na base de dados podem agora divergir em silêncio,
  vale a pena um olhar a sério assim que surgir um segundo caso, um
  Operation `AlterColumn` não é uma adição pequena por si só.
- Um Field Boolean. Revelado ao preencher `ServiceResource.active`, ver
  "Acabado de sair: `Customer` e `Service` preenchidos" acima: `active`
  guarda-se e sincroniza como uma string simples (`'true'`/`'false'`),
  não um booleano a sério, sem checkbox, sem semântica true/false em
  lado nenhum da stack. Uma ocorrência não chega para nos comprometermos
  com uma forma ainda, `Product`/`Order`/`Customer` modelam todos o
  status como uma coluna `Text` multi-valor em vez de um booleano, por
  isso isto precisa de um segundo caso real antes de ser construído.
- `BlueprintCompiler` / `CompiledBlueprint`: um único pipeline de
  compilação a resolver `uses()`, validar referências duplicadas e
  pendentes, e aplicar defaults implícitos, de modo a que Form, Table,
  `elo:sync`, API e Export lessem todos a partir de uma única estrutura
  compilada em vez de cada um percorrer o `Blueprint` por conta própria.
  Ainda não: o `ResourceTable` saiu como segundo consumidor real de
  runtime e, verificado directamente, não resolve composição da forma que
  o `ResourceForm` resolve, lê `getFields()` de forma plana, tal como o
  `SchemaDiff`, sem `getLayout()`, sem resolução de `uses()`, por isso
  continua sem haver duplicação real a eliminar, só uma previsão sobre
  permissions, workflows, computed fields, ou comportamentos gerados por
  IA daqui a seis meses. Não implementar antes de existir um consumidor
  real de runtime a resolver `uses()`, validar IDs e aplicar defaults de
  forma independente, do mesmo modo que o `ResourceForm` já faz. O teste:
  se remover este conceito hoje o projecto continua limpo, ainda não é
  estrutural.
- `elo()->resource($slug)` devolver uma fachada estilo `ResourceHandle`
  (query, table, form, repository, metadata, tudo a partir de uma só
  chamada) em vez do `RepositoryQuery` que devolve hoje. Nenhum caso real
  ainda precisa de mais do que a query.
- `elo()->resource()` aceitar uma `class-string` ou um Model directamente,
  não só um slug de config.

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
