-- Banco de Dados Completo - Sistema de Protocolo (PostgreSQL)
-- Gerado automaticamente

CREATE TABLE usuario (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    email VARCHAR(200),
    login VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo VARCHAR(20) NOT NULL, -- 'gestor' ou 'cliente'
    ativo CHAR(1) DEFAULT '1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE company_templates (
    id SERIAL PRIMARY KEY, 
    name VARCHAR(255) NOT NULL
);

CREATE TABLE company_doc_templates (
    id SERIAL PRIMARY KEY, 
    company_template_id INT NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (company_template_id) REFERENCES company_templates(id)
);

CREATE TABLE projeto (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    descricao TEXT,
    documentos_json TEXT, -- Lista de documentos necessários (JSON)
    dia_vencimento INT,
    ativo CHAR(1) DEFAULT '1',
    company_template_id INT NULL,
    is_template BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_template_id) REFERENCES company_templates(id)
);

CREATE TABLE projeto_documento (
    id SERIAL PRIMARY KEY,
    projeto_id INT NOT NULL,
    nome_documento VARCHAR(255) NOT NULL,
    obrigatorio BOOLEAN DEFAULT FALSE,
    content TEXT,
    status VARCHAR(50) DEFAULT 'pendente',
    FOREIGN KEY (projeto_id) REFERENCES projeto(id)
);

CREATE TABLE cliente_projeto (
    id SERIAL PRIMARY KEY,
    cliente_id INT NOT NULL,
    projeto_id INT NOT NULL,
    data_atribuicao DATE,
    FOREIGN KEY (cliente_id) REFERENCES usuario(id),
    FOREIGN KEY (projeto_id) REFERENCES projeto(id)
);

CREATE TABLE entrega (
    id SERIAL PRIMARY KEY,
    cliente_id INT NOT NULL,
    projeto_id INT NOT NULL,
    mes_referencia INT NOT NULL,
    ano_referencia INT NOT NULL,
    status VARCHAR(50) DEFAULT 'pendente', -- 'pendente', 'em_analise', 'aprovado', 'rejeitado'
    documentos_json TEXT, -- Arquivos enviados (JSON)
    consolidado CHAR(1) DEFAULT '0',
    arquivo_consolidado VARCHAR(255),
    data_entrega TIMESTAMP,
    data_aprovacao TIMESTAMP,
    aprovado_por INT,
    observacoes TEXT,
    resumo_documentos TEXT,
    FOREIGN KEY (cliente_id) REFERENCES usuario(id),
    FOREIGN KEY (projeto_id) REFERENCES projeto(id),
    FOREIGN KEY (aprovado_por) REFERENCES usuario(id)
);

CREATE TABLE notification (
    id SERIAL PRIMARY KEY,
    system_user_id INT NOT NULL,
    type VARCHAR(50),
    title VARCHAR(200),
    message TEXT NOT NULL,
    reference_type VARCHAR(100),
    reference_id INT,
    action_url TEXT,
    action_label VARCHAR(50),
    icon VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP,
    FOREIGN KEY (system_user_id) REFERENCES usuario(id)
);

CREATE TABLE system_notification (
    id SERIAL PRIMARY KEY,
    system_user_id INT,
    system_user_to_id INT,
    title VARCHAR(200),
    message TEXT,
    dt_message TIMESTAMP,
    action_url TEXT,
    action_label VARCHAR(50),
    icon VARCHAR(50),
    checked CHAR(1) DEFAULT 'N',
    FOREIGN KEY (system_user_id) REFERENCES usuario(id),
    FOREIGN KEY (system_user_to_id) REFERENCES usuario(id)
);

CREATE TABLE mensagem (
    id SERIAL PRIMARY KEY,
    system_user_id INT NOT NULL,     -- Remetente (De)
    system_user_to_id INT NOT NULL,  -- Destinatário (Para)
    subject VARCHAR(200),
    message TEXT NOT NULL,
    dt_message TIMESTAMP,
    checked CHAR(1) DEFAULT 'N',     -- N = Não lido, Y = Lido
    FOREIGN KEY (system_user_id) REFERENCES usuario(id),
    FOREIGN KEY (system_user_to_id) REFERENCES usuario(id)
);

CREATE TABLE system_user_reset (
    id SERIAL PRIMARY KEY,
    email VARCHAR(200) NOT NULL,
    token VARCHAR(200) NOT NULL,
    created_at TIMESTAMP,
    expires_at TIMESTAMP,
    used CHAR(1) DEFAULT 'N'
);

CREATE TABLE chat_messages (
    id SERIAL PRIMARY KEY,
    sender_id INTEGER NOT NULL,
    receiver_id INTEGER NOT NULL,
    message TEXT NOT NULL,
    is_read CHAR(1) DEFAULT 'N',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Usuário Admin Padrão (Login: admin / Senha: admin)
INSERT INTO usuario (nome, email, login, senha, tipo, ativo) VALUES 
('Gestor Admin', 'admin@sistema.com', 'admin', '$2y$10$Vp3QzysidOBSOrgIEYqzTe4uKxM9zYnaQSHJ1EzMhC9/XdPZPU6y2', 'gestor', '1');

-- Usuário Cliente Teste (Login: user / Senha: user)
INSERT INTO usuario (nome, email, login, senha, tipo, ativo) VALUES 
('Cliente Teste', 'cliente@sistema.com', 'user', '$2y$10$MSFdgncOkY7/oS3TG6fjpumA4w5u7HUQX3Z7cjoKNOXa/CxkeABqq', 'cliente', '1');
