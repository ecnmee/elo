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

**Em curso: ADR-005, Autorização, aberta para revisão, ainda não aceite**

- `docs/adr/en/ADR-005-elo-authorization.md` (e o espelho em PT) aberta
  como proposta, a tratar "gerir" e "autorizar" como as duas
  superfícies distintas já levantadas antes: ao nível da navegação
  (`viewAny`, esconde uma Resource inteira da barra lateral) e ao nível
  do CRUD (`view`/`create`/`update`/`delete` por registo).
- Recomenda construir directamente em cima do próprio `Gate`/Policy do
  Laravel, não um sistema de permissões novo, o `CoreBoundaryTest` (o
  core só depende de si mesmo, Illuminate, Livewire) torna isto quase
  a única opção disponível sem acrescentar uma dependência que esse
  teste de arquitectura rejeitaria.
- Mantido explicitamente separado do `Action::visibleWhen()`, esse
  hook é guiado pelos dados (o estado deste registo faz a acção fazer
  sentido), a autorização é guiada pelo actor (este utilizador tem
  permissão), os dois vão muitas vezes compor-se mas dar-lhes o mesmo
  nome esconderia que respondem a perguntas diferentes.
- Cinco questões em aberto registadas, a primeira (como é que uma
  `Action` personalizada se torna consciente do actor sem duplicar em
  silêncio o `visibleWhen()`) bloqueia começar por qualquer outro lado,
  tudo a jusante depende dessa forma. Descoberta de Policy, a omissão
  sem Policy registada, autorização de bulk actions, e compatibilidade
  com API/Export são as outras quatro, nenhuma respondida ainda.
- A ideia de trancar configurações "master vs dev", levantada a par
  deste mesmo pedido, explicitamente mantida fora desta ADR,
  assinalada como provavelmente o mesmo mecanismo subjacente assim que
  a autorização aterrar, não desenhada junto com ela.
- Nenhum código escrito. `src/Fields`, `ResourceController`,
  `ResourceTable`, `Navigation` esperam todos o §4 fechar, a mesma
  disciplina que a ADR-004 teve antes do `BelongsTo.php` existir.

**Acabado de sair: assets reais do logo, a substituir os de placeholder gerados por IA**

- Tamanho do logo fechado, valores finais: altura da imagem `1.2rem`,
  wordmark a `calc(var(--elo-text-sm) * 1.8)`, padding do
  `.elo-nav__logo` a bater certo com o `.elo-topbar`
  (`--elo-space-3`/`--elo-space-6`). Assente em valores literais
  explícitos em vez de percentagens compostas a meio do caminho, cada
  ajuste seguinte era mais difícil de acompanhar do que simplesmente
  nomear o tamanho pretendido.
- Os zips entregues passam agora a ter um sufixo de versão
  (`elo-monorepo-v1.zip`, a incrementar a partir daqui), várias rondas
  de ajustes ao logo usaram nomes descritivos mas sem ordem, sem forma
  de saber de relance qual era o mais recente.

- `logo-dark.png`/`logo-light.png` substituídos por completo, a fonte
  é agora a marca real, não o placeholder gerado por IA usado desde a
  passagem de branding. Processado por programa: fundo tornado
  transparente, recorte automático ao rectângulo delimitador da marca
  em si (o espaço em branco que sobrava embutido no PNG original, não
  o `gap` do CSS, era a causa real do espaçamento entre o logo e a
  wordmark parecer estranho, por mais que se reduzisse o gap no CSS),
  `logo-dark.png` (letras brancas, para a barra lateral azul-marinho)
  recolorido pixel a pixel da cor de tinta para branco, deixando o
  traço de destaque azul (`#3B36FC`-ish) intocado, `logo-light.png`
  mantém a tinta navy original, os dois iguais fora disso.
- `.elo-nav__logo`: padding vertical reduzido do que batia certo com o
  `.elo-topbar` (`--elo-space-3`) para `--elo-space-1`, por pedido. O
  `border-bottom` removido por completo, não existia nenhuma borda à
  direita neste elemento para remover (essa borda pertence ao
  `.elo-nav` em si, o divisor entre a barra lateral e o conteúdo,
  deixado como está, assinalado em vez de adivinhado).
- A `border` exterior do `.elo-table` removida, o `border-radius` +
  `overflow: hidden` mantêm-se, por isso o recorte dos cantos
  arredondados continua a funcionar, só sem o traço visível à volta.

**Acabado de sair: footer, e uma correcção de processo do meu lado**

- Logo reduzido 25% em relação ao passo anterior de `+15%/+25%/+25%`,
  ficava grande demais assim que se viu mesmo renderizado. O espaço
  entre o logo e a wordmark "Studio" também apertado,
  `--elo-space-2` reduzido para `--elo-space-1`, por pedido, para
  ficarem mais próximos.
- Uma segunda falha de processo, da mesma forma que a do `view:clear`
  acima: os comandos dados para a ronda do footer deixaram de fora o
  `vendor:publish --tag=elo-assets`, por isso a app continuou a servir
  o `elo.css` publicado anteriormente, não importa quantas vezes o
  `view:clear` corresse, o `view:clear` só toca nos templates Blade
  compilados, não nos assets do package já publicados, são dois passos
  separados e os dois são precisos depois de qualquer mudança de CSS.
  A sequência padrão a partir de agora: `composer update ecnmee/elo`
  → `vendor:publish --tag=elo-assets --force` → `view:clear`, sempre,
  não reduzida quando uma mudança parece ser só de CSS.

- `.elo-footer` novo, linha de copyright + Terms/Privacy (links de
  placeholder, a mesma honestidade que a chrome de pesquisa/idioma/
  utilizador da topbar, não existem páginas reais para eles) + um link
  de Documentation real, directo para `github.com/ecnmee/elo`. O
  `flex: 1 1 auto` que o `.elo-shell__main` já tinha empurra-o sozinho
  para o fundo da página, sem nenhum truque de sticky-footer.
- Nota de processo, não uma mudança de código: uma instrução
  `php artisan view:clear` ficou enterrada num parêntese no meio do
  texto em vez de dada como o seu próprio bloco de comando, a pessoa
  correu `cache:clear` em vez disso (uma cache completamente
  diferente, não toca nas views Blade compiladas) e continuou a ver
  HTML antigo. A partir de agora, cada comando fica no seu próprio
  bloco, nada importante fica só em prosa.

**Acabado de sair: passagem de branding, um bug real de dark mode, e ícones na demo**

- Bug real encontrado e corrigido: `.elo-panel[data-theme='dark']` só
  batia certo com um painel que tivesse o atributo directamente, o
  `ResourceTable`/`ResourceForm` envolvem-se cada um no seu próprio
  `.elo-panel` (para se manterem embutíveis sozinhos), por isso o
  toggle manual na shell exterior nunca alcançava esses painéis
  aninhados, a regra base incondicional do `.elo-panel` continuava a
  reafirmar valores claros neles independentemente do toggle. Era isto
  que fazia a tabela/paginação/botão Create ignorarem o dark mode.
  Corrigido com um selector a mais,
  `.elo-panel[data-theme='dark'] .elo-panel`, a cobrir qualquer
  profundidade de aninhamento. O caminho automático
  (`prefers-color-scheme`) nunca teve este bug, só a sobreposição
  manual tinha.
- `elo.css`'s `<link>` passa agora a ter uma query string `?v={mtime}`,
  calculada a partir da última modificação do ficheiro publicado. O
  browser estava a guardar o CSS em cache com tal agressividade que
  várias rondas de mudanças visuais nesta sessão só se viam depois de
  um hard-refresh manual, isto remove esse passo de vez, qualquer
  `vendor:publish` que mude mesmo o ficheiro invalida a cache sozinho.
- Logo aumentado, uma wordmark ("Studio") acrescentada ao lado, sempre
  a variante com letras brancas do `logo-dark.png` agora, sem trocar
  consoante o `prefers-color-scheme` (um resto de antes de o fundo da
  barra lateral passar a ser azul-marinho fixo nos dois temas, a troca
  por preferência do SO deixou de fazer sentido assim que esse fundo
  parou de mudar com o tema). Aumentado mais, compondo `+15%/+25%/+25%`
  sobre o original (um passo de `+50%` foi tentado e revertido, deixava
  o logo mais alto que a `.elo-topbar`, considerado grande demais assim
  que o padding da faixa de cabeçalho passou a bater certo com o da
  topbar, o `.elo-nav__logo` já não força uma altura fixa para se
  manter ao nível da topbar, o padding do `.elo-nav__logo` iguala agora
  o do `.elo-topbar` exactamente, por pedido, isto significa que as
  duas linhas já não se alinham em altura, a linha do logo é agora mais
  alta de propósito, uma troca intencional, não um esquecimento). O
  `.elo-nav__body` é novo, a guardar o padding que os grupos ainda
  precisam, separado agora da faixa de cabeçalho.
- Chrome em light-mode: `.elo-nav`/`.elo-topbar` usam agora o azul da
  marca (`#07132F`, a mesma tinta que o próprio logo usa) em vez de
  branco, escrito directamente no `elo.css` como escolha de marca
  deliberada, não um token temático, os dois blocos de dark mode no
  `tokens.css` repõem-no para os tokens normais da superfície escura,
  por isso o dark mode não é afectado.
- `partials/topbar-user.blade.php` extraído do layout, de propósito
  mantido genérico ("Guest", avatar vazio) no package. O nome e a foto
  de uma pessoa específica são conteúdo de projecto, não conteúdo de
  framework, embutir a identidade de um developer na omissão publicada
  significaria que qualquer outro projecto a instalar o Elo veria um
  estranho no seu próprio painel administrativo. O `demo.elo` sobrepõe-o
  através da própria convenção do Laravel para sobrepor views de
  packages (`resources/views/vendor/elo/partials/topbar-user.blade.php`),
  não um mecanismo novo construído para isto.
- As quatro Resources da demo ganharam `->icon()`. Confirmado
  anteriormente que o `Navigation` já lê `getIcon()`, nenhum código de
  framework precisou, só conteúdo que a demo nunca tinha declarado.

**Ainda avaliado, não construído:** confirmado que o dark/light mode já
segue a preferência do browser/SO automaticamente
(`prefers-color-scheme`), sobreposto pelo toggle manual assim que usado,
nada de novo precisou aqui. Pesquisa e filtros no `ResourceTable`
continuam o mesmo item registado, não desenhado, da entrada anterior,
nenhuma tabela na demo está perto de precisar deles ainda.

**Acabado de sair: polimento da navegação, logo, toggle de dark mode real, dois bugs reais corrigidos**

- Dois bugs reais, encontrados ao olhar mesmo para a demo renderizada,
  não por cobertura de testes: margem branca à volta do `.elo-shell` (o
  `margin: 8px` por defeito do `body` do browser, nunca resetado, o
  `elo.css` nunca toca em `body`/`html` de propósito, já que um package
  Composer não é dono do documento que o hospeda, corrigido antes dentro
  do próprio `layouts/app.blade.php`, que é dono da sua própria página
  por inteiro). O `.elo-nav__link:hover` usava
  `var(--elo-color-neutral-100)`, um valor cru da escala nunca
  remapeado para dark mode (só os tokens semânticos são), por isso o
  hover mantinha-se claro independentemente do tema, a chocar com o
  texto em dark mode. Corrigido para `var(--elo-color-surface-raised)`,
  já theme-aware.
- O logo do próprio Elo aparece agora na barra lateral, trocado entre
  dark/light via `<picture>`, a mesma técnica que os READMEs já usam.
  Publicado através da tag `elo-assets` do `vendor:publish` já
  existente, o `EloServiceProvider::publishes()` ganhou uma segunda
  entrada, `resources/images` ao lado de `resources/css`, mesmo
  destino, mesma tag, um único comando continua a publicar tudo.
- Um toggle de dark mode a sério, a funcionar, numa topbar nova. Não é
  uma funcionalidade inventada hoje, o `tokens.css` já tinha sido
  explicitamente construído para isto ("a future theme toggle needs no
  new tokens, only a way to set the [data-theme] attribute"), este é
  esse toggle a chegar finalmente. Persiste via `localStorage`, aplica-se
  antes do primeiro paint para evitar um flash claro-depois-escuro.
- Um campo de pesquisa, um botão de idioma, e um avatar/nome de
  utilizador também estão na topbar, explicitamente estáticos,
  `disabled`, e comentados como tal no ficheiro Blade. Pedidos como uma
  maquete visual, construídos como exactamente isso, nada ligado a um
  motor de pesquisa, a um sistema de i18n, ou a um modelo User que não
  existe, o Elo não é dono da autenticação e não deve assumir uma forma
  para um.

**Avaliado, não construído, registado para uma ADR futura cada um:**

- **i18n completo (múltiplas línguas na UI).** Nenhuma string da UI da
  framework está sequer extraída para tradução ainda ("Previous",
  "Next", "Page X of Y", a mensagem de estado vazio). Construir suporte
  para um número específico de línguas sem traduções reais por trás
  seria exactamente a antecipação que as regras da ADR-003 proíbem.
  Vale a pena fazer assim que o punhado de strings da própria framework
  estiver inventariado, começando pelas duas línguas que este projecto
  já usa em todo o lado (EN/PT), não um número arbitrário decidido à
  partida.
- **Sub-menus aninhados para muitas Resources.** Nenhum consumidor
  registado, demo ou package, está sequer perto de precisar de mais do
  que o único nível de agrupamento que o `navigationGroup()` já dá.
  Registado, revisitado quando um caso real bater nesse teto.
- **Ícones nos itens de menu.** Não precisa de nenhum código novo, o
  `Navigation` já lê `ResourceMetadata::getIcon()`, as quatro Resources
  da demo é que nunca chamam `->icon()`. Uma lacuna de conteúdo, não da
  framework.
- **Autorização: quem vê um item de menu, quem pode fazer uma operação
  CRUD.** O item maior de todos os levantados, correctamente separado
  da navegação simples ("gerir" vs "autorizar" são preocupações
  diferentes). Deliberadamente não improvisado dentro do `Navigation`,
  isto é território de uma ADR-005 à parte, a merecer o mesmo
  tratamento de cinco questões em aberto que a ADR-004 teve antes de
  qualquer código: forma da política, onde a verificação corre (Field,
  Action, middleware de rota, os três), como compõe com o
  `Action::visibleWhen()`, que já existe por outra razão e pode ser o
  sítio errado para pendurar permissões.
- **Um nível de acesso "master" vs "dev" que tranca que opções de
  personalização um não-developer pode mudar.** Levantado a par da
  autorização, e provavelmente a mesma funcionalidade subjacente em vez
  de duas, um sistema de permissões já responde "quem pode mudar esta
  definição" assim que existir. Construir um segundo mecanismo
  paralelo primeiro significaria refazê-lo assim que a ADR de
  autorização a sério aterrar.
- **Configuração do Elo exportável e reutilizável entre projectos.**
  Não existe um segundo projecto ainda para provar o que "reutilizável"
  deveria sequer significar aqui. Registado, não desenhado.
- **Um sistema de backup construído dentro do próprio Elo.** Assinalado
  como provavelmente fora do âmbito do que o Elo é, um construtor de
  painel administrativo declarativo, não uma ferramenta de backup. O
  `spatie/laravel-backup` já resolve isto para qualquer app Laravel
  baseada em Eloquent, com ou sem Elo, construir um equivalente dentro
  do `Ecnmee\Elo` também falharia o `CoreBoundaryTest` (o core só
  depende de si mesmo, Illuminate, e Livewire) a menos que fosse buscar
  um package de terceiros que o core não tem nada que ver com depender.
- **Filtros e campos de pesquisa nas tabelas do `ResourceTable`.**
  Trabalho futuro real, explicitamente opt-in por pedido ("a critério
  do dev"). Nenhuma tabela na demo está sequer perto de ser grande o
  suficiente para precisar disto ainda. Registado.

**Acabado de sair: navegação lateral, `Navigation`, construída a partir de Resources já declaradas**

- `Ecnmee\Elo\Navigation::groups()` constrói toda a barra lateral
  directamente a partir do próprio `ResourceMetadata` de cada Resource
  registada, `getLabel()`/`getPluralLabel()`/`getIcon()`/`getNavigationGroup()`,
  quatro getters que existiam desde que o `ResourceMetadata` foi escrito
  mas nunca tinham consumidor até agora, o mesmo tipo de lacuna morta
  até ser usada que o `Number` fechou para o preço e o `BelongsTo`
  fechou para as relações. Nada novo para declarar: as quatro Resources
  da demo já chamam `->navigationGroup('Business')`, a navegação
  agrupa-as correctamente sem nenhuma mudança no `demo.elo`.
- O `Module::menu()` mantém-se exactamente como estava, ainda por usar,
  ainda disponível para um link futuro que genuinamente não esteja
  ligado a uma Resource, nenhum caso desses existe ainda (ADR-003,
  nenhuma ADR por antecipação), por isso o `Navigation` não o consulta.
- Gatilho real, não antecipação: o `PROGRESS.md` já tinha adiado isto
  explicitamente ("a demo pode revelar que precisamos de navegação
  porque temos quatro Resources"), e, à parte, esta mesma sessão viu
  uma pessoa a escrever `/elo/product/create` à mão e a receber
  "resource not registered", exactamente a fricção que uma barra
  lateral existe para eliminar.
- Layout partilhado novo, `layouts/app.blade.php`, `pages/table.blade.php`
  e `pages/form.blade.php` agora fazem `@extends` dele em vez de cada
  um duplicar o documento `<html>` inteiro. O `partials/navigation.blade.php`
  renderiza os grupos, realçando o link da Resource actual.
  CSS `.elo-nav`/`.elo-shell` acrescentado ao `elo.css`, as mesmas
  variáveis de tokens e nomenclatura BEM que qualquer outro componente
  já usa, incluindo o mesmo breakpoint responsivo de 768px que o
  precedente da tabela em cartões já estabeleceu.
- Os grupos renderizam pela ordem de registo (config primeiro, depois
  Modules), o mesmo precedente "ordem de inserção, não ordenado" que o
  `ModuleRegistry` já tinha estabelecido, não alfabeticamente. Uma
  Resource sem `navigationGroup()` renderiza sem nenhum título, não sob
  um rótulo literal inventado como "General" que ninguém declarou.

**Acabado de sair: `elo-demo-app` ganha um README a sério, logo, e um travessão corrigido**

- O `elo-demo-app` tinha o README de fábrica do `laravel/laravel`, nunca
  tocado, até agora. `README.md` (inglês, predefinição) e `README.pt.md`
  (português) novos, a mesma convenção de logo e alternância de idioma
  que `elo`/`elo-monorepo` já usam, `.github/assets/logo-{dark,light}.png`
  copiados desses dois repos em vez de redesenhados, uma marca, três
  repositórios.
- O README documenta o estado actual, não a história: as quatro
  Resources (`Product`, `Service`, `Customer`, `Order`), cada tipo de
  Field em uso hoje (`Text`, `Number`, `BelongsTo`), e assinala o
  `Order` como o primeiro uso real do `BelongsTo` fora dos testes do
  próprio package. O `BUILD.md` (em português, o registo original passo
  a passo da construção) mantém-se como história, explicitamente não é
  o ficheiro mantido actual.
- Assinalado, não corrigido: a entrada `path` do repositório do
  `ecnmee/elo` no `composer.json` é um caminho local do Windows escrito
  directamente, comitado tal como está num repo agora público, mais
  ninguém consegue `composer install` sem editar essa linha primeiro.
  Documentado na secção "Getting Started" do README como um passo
  conhecido, a correcção a sério é publicar o `ecnmee/elo` no Packagist
  e substituir o path repository por uma versão normal, registado, não
  resolvido aqui.
- Um travessão longo real encontrado num ficheiro já publicado,
  o placeholder da opção vazia em `resources/views/fields/belongs-to.blade.php`,
  `—` no `<option>`, substituído por um `-` simples. Verificados todos
  os ficheiros tocados nesta sessão, `PROGRESS.md`/`PROGRESS.pt.md`/a
  ADR-004 já estavam limpos, este foi o único.
- Daqui para a frente: a documentação passa a ser tratada como viva,
  actualizada na mesma mudança que o código que descreve, não escrita
  uma vez só. Isto aplica-se aos três repositórios: o README do
  `elo-demo-app` (o que a demo mostra actualmente), a documentação de
  guia/ADR do `elo` (o registo público de arquitectura), e o
  `PROGRESS.md`/`CONTRIBUTING.md` do `elo-monorepo` (o registo de
  construção privado, para developers), cada um já a seguir este padrão
  em graus variados, agora nomeado explicitamente como a norma, não
  deixado implícito.

**Acabado de sair: `SyncCommand` descreve o `AddForeignKey` correctamente**

- O output da consola era `[orders] change orders` para a chave
  estrangeira nova, `[orders] add column to orders` para a coluna,
  revelado ao correr o `elo:sync` a sério contra o `OrderResource` (ver
  a entrada acima). O `SyncCommand::describeOperation()` tem um `match`
  que nomeia cada tipo de Operation, o `AddForeignKey` foi acrescentado
  ao código junto com o `BelongsTo` mas nunca ganhou um caso aqui,
  caindo em silêncio no `default => 'change'` genérico. Corrigido:
  `AddForeignKey => 'add foreign key to'`, a seguir exactamente o mesmo
  padrão que `CreateTable`/`AddColumn` já tinham. A migration em si
  esteve sempre correcta, isto só corrigiu a etiqueta impressa ao
  escrevê-la.
- Teste novo confirma a linha exacta, `[test-articles] add foreign key
  to test_articles`, para que uma Operation futura acrescentada sem um
  caso no `describeOperation()` caia em `'change'` em voz alta, num
  teste a falhar, não em silêncio no terminal de alguém meses depois.

**Acabado de sair: `OrderResource` declara relações a sério, fecha o ciclo**

- O `OrderResource` da demo declara agora `BelongsTo::make('customer')
  ->resource(CustomerResource::class)` e `BelongsTo::make('product')
  ->resource(ProductResource::class)`, sem substituir nada
  (`reference`, `status`, `total` mantêm-se), acrescentando as duas
  relações que a própria primeira decisão do roadmap (`Product ->
  Customer -> Order`, no topo deste ficheiro) apontou como o teste real
  da arquitectura.
  O `Order::$fillable` ganhou `customer_id`/`product_id`, o
  `EloquentRepository::save()` passa por `fill()`, um Field que uma
  Resource declara mas para o qual o modelo Eloquent por baixo não
  permite mass-assignment é um no-op silencioso, não um erro, vale a
  pena lembrar para a próxima Resource que acrescente um `BelongsTo` a
  um modelo já existente.
- O próximo `php artisan elo:sync` no `demo.elo` escreve um par
  `AddColumn` + `AddForeignKey` a sério para cada relação, a primeira
  vez que o `AddForeignKey` corre fora de um teste.
- Esta era a última peça "Deliberadamente ainda não feito" da entrada
  anterior. A ADR-004 é agora exercitada por uma Resource de aplicação
  real, não só pelos testes do package, o `OrderResource` consegue
  finalmente declarar "pertence a Customer" da forma que a ADR-001 D3
  sempre quis.

**Acabado de sair: `BelongsTo` ligado ao `ResourceForm`/`ResourceTable`**

- `ResourceForm::optionsById()`: cada `<select>` de um Field `BelongsTo`
  recebe as suas opções a partir do próprio `Repository::query()
  ->orderBy(displayAttribute)->get()` da Resource relacionada, ADR-004
  §4.3, v1, ainda sem pesquisa nem paginação. Falha alto, ADR-004 §3:
  lança `LogicException` se um registo relacionado não tiver o
  `displayAttribute` declarado, em vez de renderizar uma etiqueta vazia.
- `ResourceForm::rules()`: a regra de validação de um Field `BelongsTo`
  ganha `|exists:{table},id` por cima de required/nullable, fechando a
  nota "a validação é a parte fácil" da ADR-004 §4 (o rascunho original
  das questões em aberto).
- `ResourceTable::displayValues()`: cada coluna `BelongsTo` resolve para
  o valor de apresentação do registo relacionado, não a chave
  estrangeira em bruto, via um `whereIn()` por coluna contra o próprio
  Repository da Resource relacionada, agrupando todas as linhas da
  página actual numa única query, ADR-004 §4.2. Mesma regra de falha
  alto que o `optionsById()`. Um teste dedicado confirma exactamente uma
  query contra a tabela relacionada por página, não uma por linha,
  provando que o N+1 que a ADR foi escrita para evitar não acontece.
  Nem o `resource-form.blade.php` nem o `resource-table.blade.php`
  precisaram de saber que o `BelongsTo` existe, ambos só consomem o
  `$options`/`$displayValues` que os componentes Livewire já calcularam,
  o contrato de view do Field (`$field`, `$value`, `$context`, `$error`,
  `$wireModel`, agora `$options`) mantém-se a mesma forma que qualquer
  outro Field já usa.
- Fixtures novas, `TestArticle`/`TestArticleResource`, um `BelongsTo`
  para `TestAuthorResource`, a exercitar a ligação de ponta a ponta
  (render do formulário, guardar, validação, apresentação na tabela,
  agrupamento) sem tocar em `TestPost`/`TestPostResource`, que vários
  testes já não relacionados afirmam contra o output exacto.
- O `OrderResource` na demo continua a usar um `reference` de texto
  livre, ainda não trocado por `BelongsTo::make('customer')`, isso é
  uma mudança ao nível da aplicação no `demo.elo`, não do package,
  registado como próximo passo.

**Acabado de sair: `BelongsTo`, `AddForeignKey`, `RepositoryQuery::whereIn()`**

- `Ecnmee\Elo\Fields\BelongsTo`, o terceiro Field real e o primeiro cujo
  valor é o registo de outra Resource. `BelongsTo::make('customer')
  ->resource(CustomerResource::class)->attribute('customer_id')
  ->displayUsing('name')`, exatamente a sintaxe que a ADR-004 aceitou. O
  `attributeName()` sobrepõe a omissão do `Field` base para derivar
  `{id}_id` (`customer` -> `customer_id`), não o id literal, o único
  Field até agora onde essa sobreposição faz sentido. `displayUsing()`
  opcional, com omissão para `'name'`. O `->resource()` é obrigatório,
  não opcional, `resourceClass()`/`foreignKeyDefinition()` lançam
  `LogicException` imediatamente se lidos antes de ser declarado, um
  alvo de relação em falta é um erro de declaração, não um valor por
  omissão para contornar em tempo de execução.
- `Field::foreignKeyDefinition(): ?ForeignKeyDefinition`, `null` por
  omissão, sobreposto só pelo `BelongsTo`. Deixa o `SchemaDiff`
  genérico contra `Field`, sem nunca precisar de saber que o
  `BelongsTo` existe como classe concreta, o mesmo padrão de emenda de
  contrato que `Repository::table()` e `Field::attribute()` já usaram.
- `Sync\ForeignKeyDefinition`, um objeto de valor simples (coluna,
  coluna referenciada, tabela referenciada), e
  `Sync\Operations\AddForeignKey`, a sua própria Operation,
  deliberadamente não dobrada no `AddColumn`, ADR-004 §4.1. Usa
  `foreign()->references()->on()`, não `constrained()`, já que esta
  operação só adiciona a constraint, nunca a coluna.
- O `SchemaDiff` acrescenta um `AddForeignKey` logo a seguir à operação
  que cria a coluna (`CreateTable` ou `AddColumn`) para qualquer Field
  cujo `foreignKeyDefinition()` não seja `null`. Política aditiva sem
  alteração: uma coluna `customer_id` que já existisse antes de passar
  a `BelongsTo` nunca ganha uma constraint adicionada depois, a mesma
  lacuna de mudança de tipo que o `Number` já revelou, a mesma
  limitação conhecida, não resolvida de forma diferente aqui, confirmado
  por um teste novo. O `MigrationWriter` não precisou de nenhuma
  alteração: a sua regra já existente de "ordem inversa" no `down()` já
  elimina a constraint antes da coluna quando ambas estão a ser
  removidas, o `down()` do `AddForeignKey` só cai depois do `AddColumn`
  na lista invertida, de graça.
- `RepositoryQuery::whereIn()`, ADR-004 §4.2, adicionado da mesma forma
  que o `paginate()` se juntou à interface, quando um consumidor real
  precisou, não por antecipação, o `RepositoryQuery` não está na lista
  congelada da ADR-001 §5. O `EloquentRepositoryQuery` implementa-o
  através do próprio `whereIn()` do Eloquent.
- `resources/views/fields/belongs-to.blade.php`, um `<select>`, a
  seguir o mesmo contrato de view que `Text`/`Number` já estabeleceram,
  mais uma lista `$options` que a própria view não vai buscar, a ADR-004
  §4.3 deixou isso para o chamador na v1.
- Fixtures de teste novas, `TestAuthor`/`TestAuthorResource`, a dar ao
  `BelongsTo` uma Resource relacionada real para apontar nos testes,
  espelhando `TestPost`/`TestPostResource`.
- **Deliberadamente ainda não feito:** ligar o `BelongsTo` ao
  `ResourceForm`/`ResourceTable`, povoar `$options` a partir do próprio
  Repository da Resource relacionada, agrupar `whereIn()` por página no
  `ResourceTable` em vez de uma query por linha, resolver o valor de
  apresentação. O `OrderResource` ainda não consegue declarar "pertence
  a Customer" de ponta a ponta até isso sair, registado como próximo
  passo, mantido à parte de propósito, esta mudança já era grande
  o suficiente para rever como uma unidade.

**Acabado de sair: ADR-004 aceite, o Field de Relação está totalmente especificado**

- As cinco questões em aberto fechadas. `docs/adr/en/ADR-004-elo-relation-field.md`
  (e o espelho em PT) passou de Proposta a Aceite.
- Sintaxe confirmada: `BelongsTo::make('customer')
  ->resource(CustomerResource::class)->attribute('customer_id')
  ->displayUsing('name')`, `displayUsing()` opcional, com omissão para a
  coluna literal `'name'`, falha alto ao compilar/renderizar se ausente
  e não declarada, nunca uma célula em branco silenciosa.
- `elo:sync`: um `BelongsTo` produz `AddColumn` + uma nova operação
  `AddForeignKey`, usando o `foreignId()->constrained()` do Laravel,
  política aditiva (D7) sem alteração, o `ColumnDefinition` mantém-se
  sem conhecimento de constraints.
- N+1: o `RepositoryQuery` ganha `whereIn()`, o mesmo precedente de
  contrato não congelado que o `paginate()` já estabeleceu, sai junto
  com o `BelongsTo`, não adiado, o `ResourceTable` agrupa uma query por
  página em vez de uma por linha.
- Opções do select: a v1 carrega todos os registos relacionados via o
  `Repository::query()` já existente, nenhum mecanismo novo,
  explicitamente não a resposta final em escala, registado para depois.
- `onDelete`: nenhuma API na v1, a FK sai sem cláusula `ON DELETE`, que
  é `RESTRICT`/`NO ACTION` por omissão em MySQL/Postgres/SQLite, seguro
  por construção, sem código nenhum. `cascade`/`setNull` registados,
  não construídos.
- Relações entre Modules: resolvido pela arquitetura existente, nenhum
  trabalho novo, o `Module` não tem fronteira em tempo de execução para
  atravessar, o `BelongsTo` referencia uma Resource por class-string.
- Adiado, de propósito: `ResourceMetadata::displayField()` (uma
  Resource a declarar o seu próprio atributo de apresentação por
  omissão uma vez), ainda não existe nenhum consumidor real para
  confirmar que o conceito é preciso.
- O `Select::relationship()` do Filament usado como referência de
  mercado para a forma da UX, não copiado, a declaração do Elo vive no
  Field, a informar Form/Table/Sync a partir de uma única fonte, em
  vez de configurar em cima de um método de relação Eloquent escrito
  primeiro.
- Ainda nenhum código. `src/Fields/BelongsTo.php`, `AddForeignKey`, e
  `RepositoryQuery::whereIn()` são os próximos.

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

- **Um Field de relação, parcialmente lançado.** `BelongsTo`,
  `AddForeignKey`, e `RepositoryQuery::whereIn()` já existem, ver
  "Acabado de sair: `BelongsTo`, `AddForeignKey`,
  `RepositoryQuery::whereIn()`" acima. O que continua adiado: ligar isto
  ao `ResourceForm`/`ResourceTable`, o `OrderResource` continua a usar
  um `reference` de texto livre, ainda não consegue declarar "pertence
  a Customer" de ponta a ponta.
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
