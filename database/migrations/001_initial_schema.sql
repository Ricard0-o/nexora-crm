-- Nelber Code CRM · MySQL 8 / MariaDB · compatível com phpMyAdmin
SET NAMES utf8mb4;
SET time_zone = '-03:00';

CREATE TABLE IF NOT EXISTS usuarios (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  perfil VARCHAR(30) NOT NULL DEFAULT 'Comercial',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  assinatura_status VARCHAR(20) NOT NULL DEFAULT 'Pendente',
  assinatura_valida_ate DATETIME NULL,
  ultimo_login_em DATETIME NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_usuarios_email (email),
  KEY idx_usuarios_ativo_perfil (ativo, perfil),
  KEY idx_usuarios_assinatura (assinatura_status, assinatura_valida_ate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NULL,
  nome_empresa VARCHAR(180) NOT NULL,
  ramo_atividade VARCHAR(120) NOT NULL,
  telefone VARCHAR(30) NOT NULL,
  email VARCHAR(190) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Novo',
  origem VARCHAR(80) NOT NULL DEFAULT 'Manual',
  observacao_prospeccao TEXT NULL,
  base_legal VARCHAR(100) NOT NULL,
  finalidade VARCHAR(255) NOT NULL,
  responsavel_id BIGINT UNSIGNED NULL,
  anonimizado_em DATETIME NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_leads_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT ck_leads_status CHECK (status IN ('Novo','Em Contato','Negociação','Contratado','Recusado')),
  UNIQUE KEY uk_leads_email (email),
  UNIQUE KEY uk_leads_telefone (telefone),
  KEY idx_leads_status_criado (status, criado_em),
  KEY idx_leads_responsavel (responsavel_id),
  KEY idx_leads_empresa (nome_empresa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projetos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id BIGINT UNSIGNED NOT NULL,
  codigo VARCHAR(30) NOT NULL,
  nome VARCHAR(180) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Briefing',
  progresso TINYINT UNSIGNED NOT NULL DEFAULT 0,
  valor_total DECIMAL(15,2) NOT NULL DEFAULT 0,
  valor_pago DECIMAL(15,2) NOT NULL DEFAULT 0,
  saldo DECIMAL(15,2) NOT NULL DEFAULT 0,
  data_contratacao DATE NOT NULL,
  data_entrega DATE NULL,
  data_vencimento DATE NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_projetos_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE RESTRICT,
  CONSTRAINT ck_projetos_status CHECK (status IN ('Briefing','Layout','Desenvolvimento','Homologação','Entregue')),
  CONSTRAINT ck_projetos_valores CHECK (valor_total >= 0 AND valor_pago >= 0 AND saldo >= 0),
  UNIQUE KEY uk_projetos_codigo (codigo),
  KEY idx_projetos_status_entrega (status, data_entrega),
  KEY idx_projetos_vencimento (data_vencimento),
  KEY idx_projetos_lead (lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auditoria (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario BIGINT UNSIGNED NULL,
  acao VARCHAR(40) NOT NULL,
  entidade VARCHAR(80) NOT NULL,
  id_entidade BIGINT UNSIGNED NULL,
  ip VARCHAR(45) NOT NULL,
  user_agent VARCHAR(255) NOT NULL,
  metadados JSON NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL,
  KEY idx_auditoria_entidade (entidade, id_entidade),
  KEY idx_auditoria_usuario_data (id_usuario, criado_em),
  KEY idx_auditoria_acao_data (acao, criado_em),
  KEY idx_auditoria_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tentativas_login (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  sucesso TINYINT(1) NOT NULL DEFAULT 0,
  user_agent VARCHAR(255) NOT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_tentativas_email_data (email, criado_em),
  KEY idx_tentativas_ip_data (ip, criado_em),
  KEY idx_tentativas_sucesso_data (sucesso, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes (
  chave VARCHAR(100) PRIMARY KEY,
  valor TEXT NOT NULL,
  atualizado_por BIGINT UNSIGNED NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_configuracoes_usuario FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pagamentos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NOT NULL,
  provedor VARCHAR(30) NOT NULL DEFAULT 'Mercado Pago',
  referencia_externa CHAR(36) NOT NULL,
  preferencia_provedor_id VARCHAR(120) NULL,
  pagamento_provedor_id VARCHAR(120) NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Criado',
  status_detalhe VARCHAR(100) NULL,
  meio_pagamento VARCHAR(60) NULL,
  tipo_pagamento VARCHAR(60) NULL,
  valor DECIMAL(12,2) NOT NULL,
  moeda CHAR(3) NOT NULL DEFAULT 'BRL',
  ambiente VARCHAR(15) NOT NULL DEFAULT 'sandbox',
  dados_provedor JSON NULL,
  aprovado_em DATETIME NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pagamentos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
  UNIQUE KEY uk_pagamentos_referencia (referencia_externa),
  UNIQUE KEY uk_pagamentos_provedor_id (pagamento_provedor_id),
  KEY idx_pagamentos_usuario_data (usuario_id, criado_em),
  KEY idx_pagamentos_status_data (status, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
