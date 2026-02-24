-- Banco de Dados Completo - Sistema de Protocolo
-- Gerado automaticamente

CREATE DATABASE IF NOT EXISTS banco_completo;
USE banco_completo;

CREATE TABLE usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    email VARCHAR(200),
    login VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo VARCHAR(20) NOT NULL, -- 'gestor' ou 'cliente'
    ativo CHAR(1) DEFAULT '1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE projeto (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    descricao TEXT,
    documentos_json TEXT, -- Lista de documentos necessários (JSON)
    dia_vencimento INT,
    ativo CHAR(1) DEFAULT '1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE cliente_projeto (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    projeto_id INT NOT NULL,
    data_atribuicao DATE,
    FOREIGN KEY (cliente_id) REFERENCES usuario(id),
    FOREIGN KEY (projeto_id) REFERENCES projeto(id)
);

CREATE TABLE entrega (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    projeto_id INT NOT NULL,
    mes_referencia INT NOT NULL,
    ano_referencia INT NOT NULL,
    status VARCHAR(50) DEFAULT 'pendente', -- 'pendente', 'em_analise', 'aprovado', 'rejeitado'
    documentos_json TEXT, -- Arquivos enviados (JSON)
    consolidado CHAR(1) DEFAULT '0',
    arquivo_consolidado VARCHAR(255),
    data_entrega DATETIME,
    data_aprovacao DATETIME,
    aprovado_por INT,
    observacoes TEXT,
    resumo_documentos TEXT,
    FOREIGN KEY (cliente_id) REFERENCES usuario(id),
    FOREIGN KEY (projeto_id) REFERENCES projeto(id),
    FOREIGN KEY (aprovado_por) REFERENCES usuario(id)
);

CREATE TABLE mensagem (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_user_id INT NOT NULL,     -- Remetente (De)
    system_user_to_id INT NOT NULL,  -- Destinatário (Para)
    subject VARCHAR(200),
    message TEXT NOT NULL,
    dt_message DATETIME,
    checked CHAR(1) DEFAULT 'N',     -- N = Não lido, Y = Lido
    FOREIGN KEY (system_user_id) REFERENCES usuario(id),
    FOREIGN KEY (system_user_to_id) REFERENCES usuario(id)
);

-- Usuário Admin Padrão (Login: admin / Senha: admin)
INSERT INTO usuario (nome, email, login, senha, tipo, ativo) VALUES 
('Gestor Admin', 'admin@sistema.com', 'admin', '$2y$10$Vp3QzysidOBSOrgIEYqzTe4uKxM9zYnaQSHJ1EzMhC9/XdPZPU6y2', 'gestor', '1');

-- Usuário Cliente Teste (Login: user / Senha: user)
INSERT INTO usuario (nome, email, login, senha, tipo, ativo) VALUES 
('Cliente Teste', 'cliente@sistema.com', 'user', '$2y$10$MSFdgncOkY7/oS3TG6fjpumA4w5u7HUQX3Z7cjoKNOXa/CxkeABqq', 'cliente', '1');
