# Sistema de Protocolo - Documentação Técnica Completa

**Versão:** 1.0.0
**Data:** 16/02/2026

---

## 1. Visão Geral do Sistema

O **Sistema de Protocolo** é uma solução corporativa baseada em web para gestão centralizada de entregas de documentos recorrentes. O sistema orquestra a interação entre **Clientes** (que enviam documentos) e **Gestores** (que validam e consolidam esses documentos).

O sistema resolve o problema da desorganização no recebimento de documentos fiscais, contábeis e jurídicos, fornecendo uma trilha de auditoria completa, validação de fluxo de trabalho e ferramentas de automação para consolidação de arquivos.

---

## 2. Pilha Tecnológica (Stack)

O projeto utiliza uma arquitetura monolítica baseada no padrão MVC, alavancando componentes modernos do ecossistema PHP.

| Camada | Tecnologia | Detalhes |
| :--- | :--- | :--- |
| **Linguagem** | PHP | Versão 8.2+ (Tipagem forte, Attributes, JIT) |
| **Framework** | Adianti Framework | Versão 7.x/8.x (Componentes Visuais, ORM, Template Engine) |
| **Banco de Dados** | MySQL | Versão 8.0 (InnoDB, Foreign Keys, JSON Columns) |
| **Servidor Web** | Apache | 2.4 com `mod_rewrite` habilitado |
| **Frontend** | HTML5 / Bootstrap 5 | Integrado via Adianti Template (AdminLTE modificado) |
| **PDF Engine** | FPDF / FPDI | Bibliotecas para manipulação e merge de PDFs |
| **Container** | Docker | Docker Compose para orquestração de App + DB + PMA |

---

## 3. Arquitetura de Software

O sistema segue rigorosamente o padrão **MVC (Model-View-Controller)** implementado pelo Adianti Framework.

### 3.1. Estrutura de Diretórios Detalhada

```
sistemaProtocolo/
├── app/
│   ├── config/              # Arquivos de configuração (.ini, .php)
│   │   ├── application.php  # Configurações gerais da aplicação (timezone, debug)
│   │   ├── database.ini     # Credenciais do banco (mapeado para Docker via volume)
│   │   └── mail.ini         # Configurações SMTP para e-mails
│   ├── control/             # CONTROLADORES (Page Controllers & Action Controllers)
│   │   ├── admin/           # Controles administrativos (Login, System Users)
│   │   ├── cadastros/       # CRUDs (ClienteForm, ProjetoForm, CompanyTemplate)
│   │   ├── entregas/        # Lógica Core (Upload, Validação, Consolidação)
│   │   └── notification/    # Gestão de notificações internas
│   ├── model/               # MODELOS (Active Record Pattern)
│   │   ├── Entrega.php      # Entidade principal de fluxo
│   │   ├── Usuario.php      # Entidade de usuários (Gestor/Cliente)
│   │   └── ...              # Outras entidades mapeadas
│   ├── service/             # SERVIÇOS (Lógica de Negócio Complexa)
│   │   └── ConsolidacaoService.php # Engine de geração de PDFs
│   ├── lib/                 # Bibliotecas Auxiliares e Widgets Customizados
│   └── templates/           # Layouts HTML (Header, Menu, Footer)
├── files/                   # ARMAZENAMENTO (Storage Local)
│   ├── consolidados/        # Arquivos gerados (ZIP/PDF) organizados por Ano/Mês
│   └── documents/           # Uploads brutos dos clientes
├── docker/                  # Configurações de Infraestrutura
│   ├── apache/              # VHost Configs
│   └── php/                 # php.ini customizado
└── ...
```

### 3.2. Padrões de Projeto Utilizados

*   **Active Record**: Todas as classes em `app/model` estendem `TRecord`. Permite manipulação de dados como objetos (ex: `$entrega->store()`, `$usuario->delete()`).
*   **Service Layer**: Lógica complexa (como a manipulação de PDFs na consolidação) é isolada em classes de Serviço (`ConsolidacaoService`) para manter os Controllers magros.
*   **Front Controller**: Todo o tráfego passa por `engine.php` (para ações internas) ou `index.php` (para carregamento de páginas), que despacham para as classes corretas.
*   **Template Method**: Os formulários e datagrids herdam de `TPage` e `TWindow`, seguindo estruturas pré-definidas de ciclo de vida (`onCreate`, `onReload`).

---

## 4. Banco de Dados e Modelo de Dados

O esquema do banco de dados `banco_completo` é normalizado, mas utiliza colunas JSON para flexibilidade em estruturas variáveis (listas de documentos).

### 4.1. Tabelas Principais

#### `usuario`
Tabela unificada para autenticação. Utiliza Single Table Inheritance conceitual via coluna `tipo`.
*   `id` (PK): Inteiro, Auto Increment.
*   `login` (Unique): Identificador de acesso.
*   `senha`: Hash Bcrypt (`$2y$...`).
*   `tipo`: Enum virtual ('gestor', 'cliente').
*   `ativo`: Flag booleana (1/0).

#### `projeto`
Define os tipos de obrigações que os clientes devem cumprir.
*   `id` (PK).
*   `documentos_json` (TEXT): Array JSON contendo a lista de documentos obrigatórios (ex: `["FGTS", "DARF", "Relatório"]`). Isso permite que cada projeto tenha requisitos dinâmicos sem alterar o schema.

#### `entrega`
A tabela central do sistema. Representa um pacote mensal de documentos.
*   `id` (PK).
*   `cliente_id` (FK -> usuario).
*   `projeto_id` (FK -> projeto).
*   `mes_referencia` / `ano_referencia`: Competência dos documentos.
*   `status`: Máquina de estado ('pendente', 'em_analise', 'aprovado', 'rejeitado').
*   `documentos_json`: Array JSON mapeando 'Nome do Doc' -> 'Caminho do Arquivo'.
*   `consolidado` (Boolean): Indica se o processamento final pcorreu.
*   `arquivo_consolidado`: Path para o PDF/ZIP gerado.

#### `mensagem`
Sistema de chat interno assíncrono.
*   `system_user_id` / `system_user_to_id`: Remetente e Destinatário.
*   `checked`: Status de leitura.

---

## 5. Fluxos de Negócio Detalhados

### 5.1. Fluxo de Entrega de Documentos

1.  **Instanciação**: O Cliente inicia uma entrega selecionando Projeto e Competência via `EntregaForm`.
2.  **Validação de Requisitos**: O sistema carrega a lista de documentos do `Projeto` e gera campos de upload dinâmicos (`TFile`).
3.  **Persistência**: Ao salvar:
    *   Os arquivos são movidos de `tmp/` para `files/documents/`.
    *   O array de caminhos é serializado em JSON e salvo na coluna `documentos_json` da tabela `entrega`.
    *   Status inicial: `pendente`.
4.  **Notificação**: Um registro é inserido na tabela `notification` para alertar gestores.

### 5.2. Fluxo de Validação e Consolidação

Este é o processo mais complexo do sistema, gerido por `EntregaValidacao` (Controller) e `ConsolidacaoService` (Model/Service).

1.  **Revisão**: Gestor visualiza os uploads.
2.  **Aprovação**:
    *   Gestor clica em "Aprovar".
    *   Status muda para `aprovado`.
    *   Edição é bloqueada para o cliente.
3.  **Consolidação Automática** (`ConsolidacaoService::consolidarEntrega`):
    *   **Setup**: Cria diretório `files/consolidados/{ano}/{mes}`.
    *   **Paginação**: Analisa cada arquivo enviado. Se for PDF, usa `Fpdi` para contar páginas. Se for Imagem, conta como 1 página.
    *   **Geração de Capa**: O serviço desenha uma capa vetorial com `FPDF`, contendo metadados (Projeto, Cliente, Data).
    *   **Geração de Sumário**: Gera um índice dinâmico com link para as páginas iniciais de cada documento no PDF final.
    *   **Merge**: Itera sobre os documentos.
        *   **PDFs**: Importa página por página como templates e redimensiona para caber na área útil A4.
        *   **Imagens**: Redimensiona proporcionalmente e insere na página.
        *   **Outros**: Gera uma página de "Placeholder" informando que o arquivo deve ser baixado separadamente.
    *   **Finalização**: Salva o arquivo final (`Consolidado_ID...pdf`) e atualiza o registro da entrega.
4.  **Entrega Final**: O cliente recebe uma notificação com link direto para download do relatório consolidado.

### 5.3. Sistema de Segurança e Permissões

*   **Autenticação**: Via `LoginForm`. Verifica hash de senha e status `ativo`.
*   **Sessão**: `TSession` armazena `userid`, `username` e `usertype`.
*   **Autorização**:
    *   Controllers verificam `TSession::getValue('usertype')` no construtor ou em métodos específicos para restringir acesso.
    *   Menu dinâmico (`menu.xml`) exibe opções diferentes baseado no grupo do usuário (admin/gestor vs public/cliente).
    *   `TTransaction` garante atomicidade nas operações de banco, prevenindo inconsistências parciais.

---

## 6. Configuração e Infraestrutura (DevOps)

### 6.1. Docker Containerization

O ambiente é 100% containerizado para garantir consistência entre desenvolvimento e produção.

*   **Serviço `app`**:
    *   Base Image: `php:8.2-apache`
    *   Extensions: `gd`, `pdo_mysql`, `zip` (crítico para consolidação), `mbstring`.
    *   Config: Mapeia `docker/php/php.ini` para tunar `upload_max_filesize` (essencial para uploads grandes).
    *   Volume: O código fonte é montado em `/var/www/html`. **Importante**: `docker/database.ini` sobrescreve `app/config/database.ini` via mount para apontar para o host `db` automaticamente.
*   **Serviço `db`**:
    *   Image: `mysql:8.0`
    *   Init: Executa `banco_completo.sql` na primeira subida (`/docker-entrypoint-initdb.d`).

### 6.2. Configurações Críticas

*   **`app/config/application.php`**: Define o tema (`adminbs5`) e classes de serviço público.
*   **`docker/php/php.ini`**: Ajustado para:
    ```ini
    memory_limit = 256M      ; Necessário para merge de PDFs grandes
    upload_max_filesize = 64M ; Permite envio de documentos pesados
    post_max_size = 64M
    ```

---

## 7. Referência de API (Interna)

Embora não exponha uma API REST pública, o sistema utiliza parâmetros GET para roteamento:

*   **Rota Padrão**: `index.php?class={ControllerName}&method={MethodName}`
*   **Download de Arquivos**: `engine.php?class=ConsolidarEntregaV2&method=onDownload&id={ID}`
    *   Nota: Usa `engine.php` para processar requisições sem carregar toda a interface visual (UI Chrome), ideal para downloads binários ou AJAX.

---

## 8. Manutenção e Extensão

### Adicionar Novos Tipos de Documentos
1.  Edite a coluna JSON no banco ou a lógica de validação em `EntregaForm`.
2.  Para suporte a novos formatos na consolidação, edite `ConsolidacaoService::processarDocumentos`.

### Debugging
*   Logs do Apache/PHP podem ser vistos via `docker-compose logs -f`.
*   Erros de SQL são capturados por `TTransaction` e exibidos em `TMessage` (em modo debug).

---

*Documentação gerada automaticamente pela equipe de desenvolvimento.*
