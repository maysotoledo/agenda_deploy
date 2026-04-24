# Diagnostico do Projeto Laravel Atual

## Visao Geral

O projeto e um Laravel 12 com Filament 4, Livewire, Filament Shield, FullCalendar e parsers proprios para analise telematica. A aplicacao mistura dois dominios principais:

1. Agenda operacional: eventos, bloqueios, ferias, sincronizacao Google Calendar, notificacoes e auditoria.
2. Analise telematica: upload de logs, extracao de dados, enriquecimento de IPs, planilhas/tabelas analiticas e relatorios processados.

O sistema funciona hoje como uma aplicacao administrativa monolitica, com grande parte da regra de negocio dentro de Pages/Widgets Filament. Para um novo sistema, vale reaproveitar os models, parsers, agregadores, logica de dominio e parte das telas, mas e recomendavel separar processamento pesado em Jobs/Services.

## Stack e Dependencias

- PHP: ^8.2
- Laravel: ^12
- Filament: ~4.0
- Livewire: usado via Filament e componentes proprios
- Filament Shield: controle de permissoes/policies/roles
- Saade FullCalendar: agenda e calendario de ferias
- Smalot PDF Parser: extracao de texto de PDFs
- Vite/Tailwind 4: assets front-end
- Queue configurada como database
- Ambiente local atual usa MySQL, cache/database, sessao/database e queue/database.

## Estrutura de Alto Nivel

- `app/Filament/Pages`: paginas administrativas e fluxos grandes de analise.
- `app/Filament/Resources`: CRUDs Filament de usuarios, agenda, ferias, bloqueios e logs.
- `app/Filament/Widgets`: calendarios e widgets de dashboard.
- `app/Livewire/AnaliseInteligente`: tabelas paginadas/filtradas usadas nos relatorios.
- `app/Services`: servicos de agenda, ferias, Google Calendar e analise telematica.
- `app/Models`: entidades principais do sistema.
- `resources/views/filament/pages`: blades das paginas Filament customizadas.
- `resources/views/livewire/analise-inteligente`: blades das tabelas Livewire.
- `database/migrations`: schema incremental com modulos de agenda, logs, auditoria e analise.

## Dominio de Agenda

### Models

- `Evento`: agenda de oitivas/atendimentos, com `starts_at`, `ends_at`, `intimado`, `numero_procedimento`, `whatsapp`, `oitiva_online`, status de cumprimento, auditoria e soft delete.
- `Bloqueio`: bloqueia dias por EPC/usuario, com unicidade por `user_id + dia`.
- `Ferias`: periodos de ferias por usuario/ano.
- `User`: autenticacao, roles, tokens do Google Calendar e relacoes com eventos/analises.

### Servicos

- `EventoService`: cria, edita, cancela e restaura eventos com transacao e sincronizacao Google Calendar.
- `FeriasService`: valida periodos, limite anual de 30 dias, ate 3 periodos por ano, sem sobreposicao por usuario e sem choque por role relevante.
- `GoogleCalendarService`: OAuth, refresh token, criar/atualizar/remover eventos no Google Calendar.

### Widgets e Fluxo

- `CalendarWidget`: calendario principal de agenda; seleciona EPC, cria/edita eventos, bloqueia fins de semana/dias bloqueados, colore ocupacao.
- `SelecionarUsuarioAgendaWidget`: seleciona EPC da agenda para usuarios nao-EPC.
- `FeriasCalendarWidget`: calendario de ferias, criacao via modal, validacao por `FeriasService`.
- `EventoObserver`: notifica EPC quando evento e criado/editado/cancelado; notifica admins quando EPC atualiza status.

### Reaproveitar no Novo Sistema

- Regras de agenda em `EventoService`, `FeriasService`, `EventoObserver`.
- Schema de `eventos`, `bloqueios`, `ferias`.
- Integracao Google Calendar, mas movendo chamadas externas para Jobs.
- FullCalendar e UX de selecao de EPC, caso o novo sistema mantenha agenda.

## Dominio de Analise Telematica

### Entidades

- `AnaliseInvestigation`: investigacao, com `user_id`, `uuid`, `name`, `source`.
- `AnaliseRun`: execucao/alvo dentro de uma investigacao, com `target`, progresso, status e `report` JSON.
- `AnaliseRunIp`: IPs unicos por run, ocorrencias, ultimo acesso e flag de enriquecimento.
- `IpEnrichment`: cache global de enriquecimento por IP (`city`, `isp`, `org`, `mobile`, status).
- `Bilhetagem`: mensagens extraidas de logs WhatsApp, normalizadas em tabela propria.
- `AnaliseRunContact`: contatos/nome por run.

### Plataformas

- WhatsApp: `AnaliseInteligenteWPP`, `Whatsapp\RecordsHtmlParser`, `Whatsapp\ReportAggregator`.
- Instagram: `AnaliseInteligenteInsta`, `Instagram\RecordsHtmlParser`, `Instagram\ReportAggregator`.
- Google: `AnaliseInteligenteGoogle`, `GoogleLogParser`, `GoogleReportAggregator`.
- Apple: `AnaliseInteligenteApple`, `AppleLogParser`, `AppleReportAggregator`.
- Generico: `AnaliseInteligenteGenerico`, usando trait/plataforma compartilhada.
- Compartilhado: `HandlesPlatformLogAnalysis`, `PlatformLogParser`, `PlatformReportAggregator`, `GenericReportAggregator`.

### Fluxo de Analise

1. Usuario cria/seleciona investigacao.
2. Faz upload de um ou varios arquivos.
3. Sistema extrai texto de HTML/TXT/JSON/CSV/PDF/ZIP.
4. Parser transforma texto em estrutura normalizada: eventos, IPs, contas, identifiers, maps, pesquisas, devices.
5. Cria um ou varios `AnaliseRun`, geralmente agrupando por alvo.
6. Persiste IPs em `analise_run_ips`.
7. Polling Livewire chama `RunStepper` para enriquecer IPs.
8. Aggregator monta relatorio/planilhas a partir do parsed + enrichment.
9. Blades/Livewire tables exibem timeline, IPs unicos, provedores, cidades, maps, pesquisa, dispositivos, noturno, movel etc.

### Planilhas/Tabelas

- WhatsApp: timeline, IPs unicos, provedores, cidades, contatos, grupos, bilhetagem, vinculo, noturno, movel.
- Instagram: timeline, IPs unicos, provedores, cidades, residencial/noturno, movel, direct, seguidores/seguindo.
- Google/Apple/Generico: timeline, IPs unicos, provedores, cidades, maps, pesquisa, dispositivos/UA, residencial/noturno, movel.
- As tabelas Livewire hoje recebem arrays ja montados; muitas filtram/paginam em memoria.

### Reaproveitar no Novo Sistema

- Parsers por plataforma, especialmente WhatsApp, Instagram e Google.
- Normalizacao de datas/IPs/portas.
- Agregadores de relatorio e heuristicas investigativas.
- Models `AnaliseInvestigation`, `AnaliseRun`, `AnaliseRunIp`, `IpEnrichment`, `Bilhetagem`.
- UX de investigacao com varios alvos.
- Modais de provedores/IPs, contatos, direct, bilhetagem e vinculo.

## Auditoria, Logs e Seguranca

- `AccessLog`: registra login sucesso/falha.
- `AuditLog`: registra created/updated/deleted via trait `Auditable`.
- `Auditable`: sanitiza campos sensiveis e grava contexto Filament.
- Policies existem para users, roles, eventos, ferias, bloqueios, logs.
- Filament Shield esta integrado ao painel.

Ponto de atencao: `APP_DEBUG=true` no `.env` local. Para producao, precisa ser `false`. Tokens Google Calendar estao com cast `encrypted`, bom reaproveitar.

## Jobs e Processamento Assincrono

O projeto tem migrations de jobs e `QUEUE_CONNECTION=database`, mas nao ha classes em `app/Jobs`.

Hoje, o processamento pesado ocorre em Pages Livewire/Filament:

- parsing de ZIP/HTML/PDF dentro da requisicao;
- criacao de runs/IPs em tempo de request;
- enriquecimento de IP por polling Livewire chamando `RunStepper`;
- montagem de relatorio sob demanda/cache.

Isso funciona para arquivos pequenos/medios, mas e o maior gargalo arquitetural para escala.

## Gargalos de Performance

1. Upload/parsing dentro do request: risco de timeout, tela branca e estouro de memoria.
2. JSON grande em `analise_runs.report`: pode estourar memoria ao codificar/decodificar, especialmente se incluir listas grandes.
3. Tabelas Livewire baseadas em arrays: paginacao em memoria fica cara para milhares de linhas.
4. Enriquecimento IP por polling: depende do usuario manter a tela aberta e multiplica requisicoes HTTP externas.
5. `RunStepper` processa 1 IP por poll no estado atual; e seguro contra timeout, mas lento.
6. Cache de relatorio por run pode mascarar dados antigos se a chave nao muda ao mudar parser/aggregator.
7. Migrations defensivas com `Schema::hasTable` ajudam deploy improvisado, mas reduzem previsibilidade de ambientes novos.
8. Muitas responsabilidades em Pages Filament grandes, sobretudo `AnaliseInteligenteWPP` e `AnaliseInteligenteInsta`.
9. Falta suite de testes real: existem apenas exemplos padrao.
10. Logs antigos indicam erros de `Maximum execution time`, `Allowed memory size` e arquivo temporario Livewire.

## Riscos Tecnicos

- Acoplamento forte entre UI, parsing, persistencia e processamento.
- Duplicacao de padroes entre WhatsApp, Instagram e plataformas genericas.
- Relatorios dependem de arrays aninhados com contratos implicitos.
- Alguns arquivos/blades com nomes suspeitos ou duplicados em `resources/views/livewire/analise-inteligente`.
- Ausencia de Jobs impede reprocessamento robusto e retomada automatica.
- Uso de APIs externas gratuitas para IP enrichment pode falhar/rate-limit; falta controle centralizado de provider, retry e backoff.
- Falta de testes de parser com arquivos reais de referencia.

## Recomendacao Para o Novo Sistema

### Manter

- Laravel + Filament, se o foco continuar sendo painel administrativo.
- Models centrais de investigacao/run/IP/enrichment/bilhetagem.
- Parsers e aggregators existentes como base.
- UI de planilhas e modais como referencia funcional.
- Auditoria, AccessLog, roles/policies e notificacoes.
- Agenda/ferias se o novo sistema continuar com esse modulo.

### Refatorar

- Criar Jobs:
  - `ProcessUploadedLogJob`
  - `ParseLogFileJob`
  - `CreateRunsFromParsedLogJob`
  - `EnrichRunIpsJob`
  - `BuildReportSnapshotJob`
- Mover parsing das Pages para Services/Actions.
- Persistir dados grandes em tabelas normalizadas, nao em JSON unico.
- Separar `report` em snapshot leve + tabelas detalhadas.
- Criar contratos de parser:
  - `LogParserInterface`
  - `ParsedLogDTO`
  - `ReportAggregatorInterface`
- Substituir tabelas Livewire baseadas em array por queries paginadas quando houver grande volume.
- Criar historico de processamento com status, erro, started_at, finished_at.
- Criar comandos artisan para reprocessar investigacao/run.
- Centralizar IP enrichment com rate limit, retries, provider priority e cache.

### Schema Sugerido Para Evolucao

- `investigations`
- `investigation_targets`
- `log_uploads`
- `log_files`
- `analysis_runs`
- `ip_events`
- `ip_enrichments`
- `message_events`
- `map_events`
- `search_events`
- `device_events`
- `report_snapshots`
- `processing_steps`

## Prioridades Praticas

1. Tirar parsing/enrichment da requisicao Livewire e mover para fila.
2. Normalizar eventos grandes em tabelas.
3. Definir DTO/contrato unico de parser.
4. Criar testes com fixtures reais para WhatsApp, Instagram, Google e Apple.
5. Reduzir responsabilidades das Pages Filament.
6. Criar painel de progresso baseado em banco, nao em processamento no poll.
7. Reaproveitar aggregators apenas como camada de leitura/consulta.
8. Padronizar nomes, encoding e mensagens para UTF-8 limpo.

## Conclusao

O projeto atual tem bastante valor funcional ja implementado: parsers reais, telas de investigacao, modelos de analise, enriquecimento de IP, bilhetagem, mapas, pesquisa, auditoria e agenda. O principal problema nao e a regra de negocio, mas a arquitetura de execucao: processamento pesado ainda vive dentro de Pages Livewire/Filament.

No novo sistema, o melhor caminho e reaproveitar o conhecimento dos parsers/agregadores e redesenhar o pipeline como processamento assicrono, orientado por jobs, dados normalizados e relatorios consultaveis por query.
