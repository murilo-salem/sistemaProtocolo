# Sistema de Protocolo

Aplicacao web para gestao de protocolos e documentos, com controle por perfil de usuario, fluxo de entregas, validacao por gestor, consolidacao de arquivos em PDF, notificacoes e recursos de IA local (Ollama) para resumo de documentos.

## Sumario

1. [Objetivo do projeto](#objetivo-do-projeto)
2. [Principais funcionalidades](#principais-funcionalidades)
3. [Perfis e permissoes](#perfis-e-permissoes)
4. [Arquitetura tecnica](#arquitetura-tecnica)
5. [Estrutura do repositorio](#estrutura-do-repositorio)
6. [Requisitos](#requisitos)
7. [Execucao recomendada com Docker](#execucao-recomendada-com-docker)
8. [Configuracoes importantes](#configuracoes-importantes)
9. [Fluxos principais do sistema](#fluxos-principais-do-sistema)
10. [Execucao local sem Docker](#execucao-local-sem-docker)
11. [Testes automatizados e cobertura](#testes-automatizados-e-cobertura)
12. [Comandos de operacao](#comandos-de-operacao)
13. [Seguranca e boas praticas](#seguranca-e-boas-praticas)
14. [Troubleshooting](#troubleshooting)
15. [Documentacao complementar](#documentacao-complementar)

## Objetivo do projeto

O sistema centraliza o processo de coleta, validacao e historico de documentos por projeto/cliente, reduzindo operacao manual e dando rastreabilidade para gestores e administracao.

## Principais funcionalidades

- Cadastro e gestao de clientes, projetos e empresas.
- Associacao cliente-projeto.
- Registro de entregas por periodo de referencia (mes/ano).
- Fluxo de status de entrega (`pendente`, `em_analise`, `aprovado`, `rejeitado`).
- Validacao e acompanhamento de entregas pelo perfil gestor.
- Consolidacao de documentos em PDF unico com capa e sumario.
- Notificacoes internas para cliente e gestores.
- Chat interno entre usuarios.
- Dashboards por perfil (cliente, gestor e root).
- Resumo de documentos com IA local via Ollama.

## Perfis e permissoes

### Cliente

- Acesso ao dashboard de cliente.
- Criacao e envio de entregas.
- Visualizacao das proprias entregas e notificacoes.
- Uso de chat.

### Gestor

- Acesso ao dashboard de gestor.
- Cadastro e manutencao de projetos/clientes/empresas.
- Validacao de entregas.
- Acompanhamento historico.
- Uso de chat e notificacoes.

### Root

- Login root via variaveis de ambiente (`ROOT_USER` e `ROOT_PASS`).
- Acesso a telas de observabilidade e estatisticas de governanca.
- Nao depende da autenticacao tradicional da tabela de usuarios.

## Arquitetura tecnica

- Backend: PHP 8.2.
- Framework/base UI: Adianti.
- Banco de dados principal: PostgreSQL.
- Web server: Apache (no container da app).
- IA local: Ollama (`gemma2:2b` e `moondream`).
- Processamento de documentos/PDF: FPDF/FPDI + Ghostscript + PDF Parser.
- E-mail: SMTP via PHPMailer.
- Testes: PHPUnit.

## Estrutura do repositorio

```text
.
|-- app/
|   |-- control/         # Controllers e telas
|   |-- model/           # Models (TRecord)
|   |-- service/         # Servicos de negocio
|   `-- config/          # Configuracoes da aplicacao
|-- docker/
|   |-- apache/
|   |-- php/
|   `-- database.ini     # Config DB usado no Docker
|-- tests/               # Suite de testes (Unit/Integration/Functional)
|-- banco_completo_pg.sql
|-- docker-compose.yml
|-- Dockerfile
|-- phpunit.xml
|-- README_DOCKER.md
`-- README.md
```

## Requisitos

### Para execucao com Docker (recomendado)

- Docker Desktop em execucao.
- Portas livres no host:
- `80` para aplicacao.
- `5432` para PostgreSQL.
- `11434` para Ollama.

### Para execucao local sem Docker

- PHP 8.2 com extensoes equivalentes as usadas no container.
- PostgreSQL ativo e base configurada.
- Dependencias via Composer instaladas.
- Ajuste de `app/config/database.ini` para seu ambiente local.

## Execucao recomendada com Docker

### 1) Subir ambiente

```bash
docker compose up -d --build
```

### 2) Validar containers

```bash
docker compose ps
```

Containers esperados:
- `sistema-protocolo-app`
- `sistema-protocolo-db`
- `sistema-protocolo-ollama`

### 3) Acessos

- Aplicacao: `http://localhost`
- PostgreSQL: `localhost:5432`
- Ollama: `http://localhost:11434`

### 4) Baixar modelos do Ollama (primeira vez)

```bash
docker exec -it sistema-protocolo-ollama ollama run gemma2:2b
docker exec -it sistema-protocolo-ollama ollama run moondream
```

## Configuracoes importantes

### docker-compose.yml

Servicos:
- `app`: PHP/Apache da aplicacao.
- `db`: Postgres 16.
- `ollama`: API local de IA.

Variaveis de ambiente relevantes no servico `app`:
- `OLLAMA_HOST=ollama`
- `ROOT_USER=...`
- `ROOT_PASS=...`

Volumes relevantes:
- `./:/var/www/html` (codigo no container da app)
- `./docker/database.ini:/var/www/html/app/config/database.ini` (override DB no Docker)
- `db_data` (persistencia do Postgres)
- `ollama_data` (persistencia dos modelos Ollama)

### Banco no Docker

No Docker, a app usa `docker/database.ini` com host interno `db`.

Importante:
- `app/config/database.ini` local pode diferir do ambiente Docker.
- Dentro do container, o arquivo montado de `docker/database.ini` e o que vale.

### Inicializacao de dados

O arquivo `banco_completo_pg.sql` e executado na inicializacao do Postgres apenas na primeira criacao do volume `db_data`.

Para reset completo:

```bash
docker compose down -v
docker compose up -d --build
```

## Fluxos principais do sistema

### Autenticacao

- Login tradicional por usuario/senha (tabela de usuarios ativos).
- Login root por variavel de ambiente para perfil administrativo avancado.

### Entregas e validacao

- Cliente envia documentos vinculados a projeto e periodo.
- Gestor revisa e altera status da entrega.
- Entregas aprovadas podem ser consolidadas em PDF.

### Consolidacao de documentos

- Junta documentos em arquivo unico.
- Inclui capa com metadados e sumario com paginas.
- Usa FPDI/FPDF e estrategias de fallback para formatos e PDFs complexos.
- Pode usar Ghostscript para compatibilizacao de arquivos PDF.

### Notificacoes

- Notificacao para cliente quando eventos relevantes ocorrem.
- Notificacao para gestores em fluxos de controle.
- Dropdown/listagem de notificacoes com marcacao de leitura.

### IA local (resumo de documentos)

- Servico de resumo chama Ollama na rede Docker (`ollama:11434`).
- Modelo textual principal: `gemma2:2b`.
- Modelo para visao/extracao de imagem: `moondream`.

### E-mail

- SMTP configurado em `app/config/mail.ini`.
- Usado para envio de credenciais e mensagens transacionais.

## Execucao local sem Docker

Este caminho e util para debug especifico com stack local (exemplo: XAMPP + Postgres local).

Passos basicos:

1. Instalar dependencias:

```bash
composer install
```

2. Configurar `app/config/database.ini` para seu banco local.

3. Garantir que os diretorios de escrita existem e tem permissao:
- `app/output`
- `tmp`

4. Iniciar servidor web local e abrir aplicacao.

Observacao:
- Para recursos de IA, mantenha Ollama acessivel e ajuste `OLLAMA_HOST` conforme necessario.

## Testes automatizados e cobertura

A suite de testes inclui:
- `tests/Unit`
- `tests/Integration`
- `tests/Functional`

### Executar testes

```bash
vendor/bin/phpunit
```

ou

```bash
composer test
```

### Gerar cobertura (HTML + Clover)

```bash
phpdbg -qrr vendor/bin/phpunit --coverage-text --coverage-html coverage --coverage-clover coverage/clover.xml
```

Arquivos gerados:
- `coverage/index.html`
- `coverage/clover.xml`

## Comandos de operacao

Subir/rebuild:

```bash
docker compose up -d --build
```

Status:

```bash
docker compose ps
```

Logs:

```bash
docker compose logs -f
docker compose logs -f app
docker compose logs -f db
docker compose logs -f ollama
```

Shell no container da app:

```bash
docker compose exec app bash
```

Parar mantendo dados:

```bash
docker compose down
```

Parar removendo volumes:

```bash
docker compose down -v
```

## Seguranca e boas praticas

- Nao versionar segredos reais em arquivos de configuracao.
- Use senhas fortes para `ROOT_PASS` e credenciais SMTP.
- Restrinja acesso ao Postgres/Ollama quando nao forem necessarios externamente.
- Revise `mail.ini` para nao expor credenciais em ambientes compartilhados.
- Considere usar `.env`/secret manager para dados sensiveis.

## Troubleshooting

### Porta ocupada

Se `80`, `5432` ou `11434` estiverem em uso, altere o mapeamento no `docker-compose.yml`.

### Banco nao reinicializa schema

Se `db_data` ja existe, o script de init nao roda novamente.
Use `docker compose down -v` para reset completo.

### App nao conecta no banco

Verifique arquivo efetivo dentro do container:

```bash
docker compose exec app cat /var/www/html/app/config/database.ini
```

O host deve ser `db` no ambiente Docker.

### Funcionalidade de IA falhando

- Verifique se o container `ollama` esta `Up`.
- Teste endpoint:

```bash
curl http://localhost:11434/api/tags
```

- Liste modelos instalados:

```bash
docker exec -it sistema-protocolo-ollama ollama list
```

## Documentacao complementar

- Guia Docker detalhado: [README_DOCKER.md](README_DOCKER.md)

Se voce vai operar o sistema em Docker no dia a dia, esse arquivo e o principal para detalhes de arquitetura e diagnostico do ambiente de containers.
