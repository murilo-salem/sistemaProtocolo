# Sistema de Protocolo - Docker Setup

Este projeto foi configurado para rodar em containers Docker, facilitando o desenvolvimento e deploy.

## Pré-requisitos

- Docker Desktop instalado e rodando.
- Porta 80 livre (ou configure outra porta no `docker-compose.yml`).

## Como rodar

1. **Clone o repositório** (se ainda não o fez).
2. **Navegue até a pasta do projeto**:
   ```bash
   cd sistemaProtocolo
   ```
3. **Suba os containers**:
   ```bash
   docker-compose up -d --build
   ```

## Serviços

- **Aplicação Web**: Acessível em [http://localhost](http://localhost)
- **Banco de Dados (MySQL)**: Host `db`, Porta `3306`, Usuário `root`, Senha `rootparams_password`
- **phpMyAdmin**: Acessível em [http://localhost:8080](http://localhost:8080) (para gerenciar o banco)

## Estrutura Docker

- `Dockerfile`: Configuração da imagem PHP 8.2 com Apache e extensões necessárias.
- `docker-compose.yml`: Orquestração dos serviços (App, Banco, PMA).
- `docker/`: Pasta com arquivos de configuração (php.ini, vhost.conf, database.ini).

## Observações Importantes

- O arquivo `app/config/database.ini` original será **substituído dentro do container** pelo arquivo `docker/database.ini` através de um volume. Isso garante que a conexão com o banco funcione automaticamente no Docker sem alterar seus arquivos locais para o XAMPP (exceto se você editar o `database.ini` localmente, o que não afetará o Docker, e vice-versa).
- O banco de dados será inicializado automaticamente com o script `banco_completo.sql` na primeira execução.
- As permissões das pastas `app/output` e `tmp` são ajustadas automaticamente no `Dockerfile`, mas em ambiente Windows/Docker Desktop, as permissões de arquivo do host são preservadas.

## Comandos Úteis

- Parar os containers: `docker-compose down`
- Ver logs: `docker-compose logs -f`
- Acessar o container da app: `docker-compose exec app bash`
