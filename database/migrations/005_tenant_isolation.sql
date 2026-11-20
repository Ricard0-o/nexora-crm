-- Isolamento de dados por empresa (tenant). Faça backup antes da aplicação.
-- Compatível com MySQL 8 / MariaDB e phpMyAdmin.

CREATE TABLE IF NOT EXISTS tenants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(160) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_tenants_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tenants (nome, slug)
VALUES ('Nelber Code', 'nelber-code')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

ALTER TABLE usuarios ADD COLUMN tenant_id BIGINT UNSIGNED NULL AFTER id;

-- Contas administrativas existentes ficam no workspace proprietário.
UPDATE usuarios
SET tenant_id = (SELECT id FROM tenants WHERE slug = 'nelber-code')
WHERE perfil = 'Administrador';

-- Cada conta antiga não administrativa recebe seu próprio workspace isolado.
INSERT INTO tenants (nome, slug)
SELECT CONCAT('Workspace ', id), CONCAT('legacy-user-', id)
FROM usuarios
WHERE perfil <> 'Administrador';

UPDATE usuarios u
INNER JOIN tenants t ON t.slug = CONCAT('legacy-user-', u.id)
SET u.tenant_id = t.id
WHERE u.perfil <> 'Administrador';

-- Se uma base antiga não tiver usuários, cria um usuário futuro no workspace Nelber Code.
UPDATE usuarios
SET tenant_id = (SELECT id FROM tenants WHERE slug = 'nelber-code')
WHERE tenant_id IS NULL;

ALTER TABLE usuarios
  MODIFY tenant_id BIGINT UNSIGNED NOT NULL,
  ADD UNIQUE KEY uk_usuarios_id_tenant (id, tenant_id),
  ADD KEY idx_usuarios_tenant_perfil (tenant_id, perfil),
  ADD CONSTRAINT fk_usuarios_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT;

ALTER TABLE leads ADD COLUMN tenant_id BIGINT UNSIGNED NULL AFTER id;

UPDATE leads l
LEFT JOIN usuarios u ON u.id = l.responsavel_id
SET l.tenant_id = COALESCE(u.tenant_id, (SELECT id FROM tenants WHERE slug = 'nelber-code'));

ALTER TABLE leads
  MODIFY tenant_id BIGINT UNSIGNED NOT NULL,
  DROP INDEX uk_leads_email,
  DROP INDEX uk_leads_telefone,
  ADD UNIQUE KEY uk_leads_tenant_id (id, tenant_id),
  ADD UNIQUE KEY uk_leads_tenant_email (tenant_id, email),
  ADD UNIQUE KEY uk_leads_tenant_telefone (tenant_id, telefone),
  ADD KEY idx_leads_tenant_status_criado (tenant_id, status, criado_em),
  ADD CONSTRAINT fk_leads_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_leads_responsavel_tenant FOREIGN KEY (responsavel_id, tenant_id) REFERENCES usuarios(id, tenant_id) ON DELETE RESTRICT;

ALTER TABLE projetos ADD COLUMN tenant_id BIGINT UNSIGNED NULL AFTER id;

UPDATE projetos p
INNER JOIN leads l ON l.id = p.lead_id
SET p.tenant_id = l.tenant_id;

ALTER TABLE projetos
  MODIFY tenant_id BIGINT UNSIGNED NOT NULL,
  DROP FOREIGN KEY fk_projetos_lead,
  DROP INDEX uk_projetos_codigo,
  ADD UNIQUE KEY uk_projetos_tenant_codigo (tenant_id, codigo),
  ADD KEY idx_projetos_tenant_status (tenant_id, status),
  ADD KEY idx_projetos_tenant_vencimento (tenant_id, data_vencimento),
  ADD CONSTRAINT fk_projetos_lead_tenant FOREIGN KEY (lead_id, tenant_id) REFERENCES leads(id, tenant_id) ON DELETE RESTRICT;

ALTER TABLE auditoria ADD COLUMN tenant_id BIGINT UNSIGNED NULL AFTER id;

UPDATE auditoria a
LEFT JOIN usuarios u ON u.id = a.id_usuario
SET a.tenant_id = COALESCE(u.tenant_id, (SELECT id FROM tenants WHERE slug = 'nelber-code'));

ALTER TABLE auditoria
  ADD KEY idx_auditoria_tenant_data (tenant_id, criado_em),
  ADD CONSTRAINT fk_auditoria_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_auditoria_usuario_tenant FOREIGN KEY (id_usuario, tenant_id) REFERENCES usuarios(id, tenant_id) ON DELETE RESTRICT;

-- Configurações da organização também passam a ter chave e valor por empresa.
CREATE TABLE IF NOT EXISTS tenant_configuracoes (
  tenant_id BIGINT UNSIGNED NOT NULL,
  chave VARCHAR(100) NOT NULL,
  valor TEXT NOT NULL,
  atualizado_por BIGINT UNSIGNED NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (tenant_id, chave),
  CONSTRAINT fk_tenant_configuracoes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_tenant_configuracoes_usuario FOREIGN KEY (atualizado_por, tenant_id) REFERENCES usuarios(id, tenant_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tenant_configuracoes (tenant_id, chave, valor, atualizado_por)
SELECT (SELECT id FROM tenants WHERE slug = 'nelber-code'), chave, valor, NULL
FROM configuracoes
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- Pagamentos continuam associados à conta que pagou; o tenant facilita auditoria e conciliação.
ALTER TABLE pagamentos ADD COLUMN tenant_id BIGINT UNSIGNED NULL AFTER id;
UPDATE pagamentos p INNER JOIN usuarios u ON u.id = p.usuario_id SET p.tenant_id = u.tenant_id;
ALTER TABLE pagamentos
  MODIFY tenant_id BIGINT UNSIGNED NOT NULL,
  ADD KEY idx_pagamentos_tenant_data (tenant_id, criado_em),
  ADD CONSTRAINT fk_pagamentos_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_pagamentos_usuario_tenant FOREIGN KEY (usuario_id, tenant_id) REFERENCES usuarios(id, tenant_id) ON DELETE RESTRICT;
