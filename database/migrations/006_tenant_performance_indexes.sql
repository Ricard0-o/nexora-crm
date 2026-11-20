-- Índices para consultas frequentes filtradas por empresa.
ALTER TABLE leads
  ADD KEY idx_leads_tenant_criado (tenant_id, criado_em),
  ADD KEY idx_leads_tenant_atualizado (tenant_id, atualizado_em);

ALTER TABLE projetos
  ADD KEY idx_projetos_tenant_atualizado (tenant_id, atualizado_em),
  ADD KEY idx_projetos_tenant_contratacao (tenant_id, data_contratacao);
