-- Controle de acesso comercial para instalações existentes.
-- Execute uma única vez após 001_initial_schema.sql e 002_settings.sql.

ALTER TABLE usuarios
  ADD COLUMN assinatura_status VARCHAR(20) NOT NULL DEFAULT 'Pendente' AFTER ativo,
  ADD COLUMN assinatura_valida_ate DATETIME NULL AFTER assinatura_status,
  ADD KEY idx_usuarios_assinatura (assinatura_status, assinatura_valida_ate);

-- O administrador existente permanece com acesso integral ao CRM.
UPDATE usuarios
SET assinatura_status = 'Ativa', assinatura_valida_ate = NULL
WHERE perfil = 'Administrador';
