-- Pagamentos do acesso ao CRM via Mercado Pago.
-- Execute uma única vez após 003_user_subscriptions.sql.

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
