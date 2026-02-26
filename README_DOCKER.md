# Docker no Sistema de Protocolo

Este documento descreve como o ambiente Docker do projeto funciona na pratica, do build ate a execucao dos fluxos principais (aplicacao web, banco PostgreSQL e IA local com Ollama).

## Visao Geral da Arquitetura

O `docker-compose.yml` sobe **3 servicos** na mesma rede bridge (`app-network`):

1. **app** (`sistema-protocolo-app`)
   - Imagem customizada via `Dockerfile` (PHP 8.2 + Apache)
   - Porta externa: `80` -> interna `80`
   - Codigo da aplicacao montado por volume em `/var/www/html`
   - Usa variaveis de ambiente para integracao com Ollama e login root

2. **db** (`sistema-protocolo-db`)
   - Imagem `postgres:16-alpine`
   - Porta externa: `5432` -> interna `5432`
   - Volume persistente: `db_data` (dados do Postgres)
   - Inicializacao do schema/dados via `banco_completo_pg.sql`

3. **ollama** (`sistema-protocolo-ollama`)
   - Imagem `ollama/ollama:latest`
   - Porta externa: `11434` -> interna `11434`
   - Volume persistente: `ollama_data` (modelos baixados)

## Como o Ambiente Sobe

### 1) Build da aplicacao (`Dockerfile`)

A imagem `app` faz:
- Instalacao de dependencias de SO (incluindo `ghostscript` e cliente Postgres)
- Instalacao de extensoes PHP usadas no sistema (`pdo_pgsql`, `pgsql`, `gd`, `mbstring`, etc.)
- Habilitacao do `mod_rewrite` no Apache
- Copia de configuracoes customizadas:
  - `docker/apache/vhost.conf`
  - `docker/php/php.ini`
- `composer install` no build

### 2) Subida do compose

Comando recomendado:

```bash
docker compose up -d --build
```

O servico `app` depende de `db` e `ollama` (`depends_on`).

## Configuracoes Criticas em Docker

### Banco de dados dentro do container

No Docker, a app usa **override de configuracao**:

- Arquivo montado: `./docker/database.ini`
- Destino no container: `/var/www/html/app/config/database.ini`

Conteudo efetivo no Docker:
- Host: `db`
- Porta: `5432`
- Banco: `banco_completo`
- Usuario: `root`
- Senha: `rootparams_password`
- Tipo: `pgsql`

Importante:
- O `app/config/database.ini` local pode ter valores diferentes para ambiente fora do Docker.
- Dentro do container, o arquivo montado do diretorio `docker/` prevalece.

### Credenciais Root (login sem depender do banco)

No `docker-compose.yml` (servico `app`):

- `ROOT_USER`
- `ROOT_PASS`

Essas variaveis sao lidas no `LoginForm` para autenticar o usuario root administrativo.

### Integracao com Ollama

No `docker-compose.yml` (servico `app`):

- `OLLAMA_HOST=ollama`

Isso permite que a aplicacao se conecte ao endpoint interno:

- `http://ollama:11434`

## Inicializacao de Dados

No servico `db`, o arquivo `banco_completo_pg.sql` e montado em:

- `/docker-entrypoint-initdb.d/init.sql`

Esse script roda automaticamente **somente na primeira criacao do volume** `db_data`.

Se precisar reinicializar do zero:

```bash
docker compose down -v
docker compose up -d --build
```

## Endpoints e Acesso

- Sistema web: `http://localhost`
- PostgreSQL (host): `localhost:5432`
- Ollama API (host): `http://localhost:11434`

## Preparacao do Ollama (modelos)

Depois de subir os containers, baixe os modelos usados pelo sistema:

```bash
docker exec -it sistema-protocolo-ollama ollama run gemma2:2b
docker exec -it sistema-protocolo-ollama ollama run moondream
```

Observacoes:
- O download fica persistido no volume `ollama_data`.
- Sem esses modelos, funcionalidades de resumo/visao com IA vao falhar.

## Fluxo de Funcionamento Entre Containers

1. Usuario acessa `http://localhost` -> container `app` (Apache/PHP)
2. App conecta no Postgres usando host `db` (rede interna Docker)
3. Funcionalidades de IA chamam Ollama em `ollama:11434`
4. Arquivos e codigo sao lidos/escritos no volume do projeto montado em `/var/www/html`

## Persistencia de Dados

- `db_data`: persiste banco Postgres entre reinicios
- `ollama_data`: persiste modelos Ollama entre reinicios
- Codigo/projeto: bind mount `./:/var/www/html`

## Comandos Operacionais

Subir ambiente:

```bash
docker compose up -d --build
```

Ver status:

```bash
docker compose ps
```

Ver logs:

```bash
docker compose logs -f
docker compose logs -f app
docker compose logs -f db
docker compose logs -f ollama
```

Acessar shell da app:

```bash
docker compose exec app bash
```

Parar mantendo dados:

```bash
docker compose down
```

Parar removendo dados persistentes:

```bash
docker compose down -v
```

## Troubleshooting

### Porta 80 ocupada

Edite o mapeamento da app em `docker-compose.yml`:

```yaml
ports:
  - "8080:80"
```

Depois:

```bash
docker compose up -d
```

### Banco nao inicializa como esperado

- Verifique logs: `docker compose logs -f db`
- Se o volume `db_data` ja existia, o `init.sql` nao roda novamente
- Para forcar reinicializacao completa: `docker compose down -v`

### App nao conecta no banco dentro do Docker

Confirme no container `app`:

```bash
docker compose exec app cat /var/www/html/app/config/database.ini
```

Deve apontar para `host = db`.

### Erros de IA/Ollama

- Verifique logs: `docker compose logs -f ollama`
- Teste API:

```bash
curl http://localhost:11434/api/tags
```

- Confirme modelos baixados:

```bash
docker exec -it sistema-protocolo-ollama ollama list
```

## Arquivos Docker Relevantes

- `docker-compose.yml`: orquestracao dos servicos
- `Dockerfile`: imagem da aplicacao PHP/Apache
- `docker/database.ini`: conexao DB para ambiente Docker
- `docker/apache/vhost.conf`: virtual host Apache
- `docker/php/php.ini`: configuracoes PHP do container
- `banco_completo_pg.sql`: script de inicializacao do banco

## Resumo Rapido

Se o ambiente estiver no estado esperado, voce deve ter:
- `sistema-protocolo-app` em `Up` na porta `80`
- `sistema-protocolo-db` em `Up` na porta `5432`
- `sistema-protocolo-ollama` em `Up` na porta `11434`
- Modelos Ollama instalados (`gemma2:2b` e `moondream`)
- Login root funcional via `ROOT_USER`/`ROOT_PASS`
