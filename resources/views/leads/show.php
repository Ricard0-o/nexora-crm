<?php
$createdAt = strtotime((string) $lead['criado_em']);
$updatedAt = strtotime((string) $lead['atualizado_em']);
$phoneLink = preg_replace('/[^0-9+]/', '', (string) $lead['telefone']);
?>
<div class="page-heading lead-detail-heading"><div><span class="eyebrow dark">Ficha do lead #<?= (int) $lead['id'] ?></span><h1><?= e($lead['nome_empresa']) ?></h1><p>Visualização completa das informações comerciais e de privacidade.</p></div><div class="heading-actions"><a class="button button-secondary" href="<?= url('/leads') ?>">Voltar para leads</a><a class="button button-primary" href="<?= url('/leads/edit?id='.(int)$lead['id']) ?>">Editar cadastro</a></div></div>

<section class="lead-summary-grid">
  <article><span>Status comercial</span><strong><span class="badge <?= strtolower(str_replace(' ','-',$lead['status'])) ?>"><?= e($lead['status']) ?></span></strong></article>
  <article><span>Origem</span><strong><?= e($lead['origem']) ?></strong></article>
  <article><span>Responsável</span><strong><?= e($lead['responsavel_nome'] ?: 'Não atribuído') ?></strong></article>
  <article><span>Cadastrado em</span><strong><?= $createdAt ? date('d/m/Y H:i', $createdAt) : 'Não informado' ?></strong></article>
</section>

<section class="lead-detail-grid">
  <div class="lead-detail-main">
    <article class="panel detail-panel"><div class="panel-heading"><div><h2>Empresa e contato</h2><p>Dados usados no relacionamento comercial.</p></div></div><dl class="detail-list"><div><dt>Empresa</dt><dd><?= e($lead['nome_empresa']) ?></dd></div><div><dt>Contato</dt><dd><?= e($lead['nome'] ?: 'Não informado') ?></dd></div><div><dt>Ramo de atividade</dt><dd><?= e($lead['ramo_atividade']) ?></dd></div><div><dt>E-mail</dt><dd><a class="entity-link" href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a></dd></div><div><dt>Telefone</dt><dd><a class="entity-link" href="tel:<?= e($phoneLink) ?>"><?= e($lead['telefone']) ?></a></dd></div></dl></article>
    <article class="panel detail-panel"><div class="panel-heading"><div><h2>Acompanhamento comercial</h2><p>Contexto registrado para a prospecção.</p></div><a href="<?= url('/pipeline') ?>">Abrir pipeline</a></div><div class="detail-note"><?= trim((string) $lead['observacao_prospeccao']) !== '' ? nl2br(e($lead['observacao_prospeccao'])) : '<span>Nenhuma observação registrada.</span>' ?></div></article>
  </div>
  <aside class="lead-detail-side">
    <article class="panel detail-panel"><div class="panel-heading"><div><h2>LGPD</h2><p>Justificativa do tratamento.</p></div></div><dl class="detail-list compact"><div><dt>Base legal</dt><dd><?= e($lead['base_legal']) ?></dd></div><div><dt>Finalidade</dt><dd><?= e($lead['finalidade']) ?></dd></div></dl></article>
    <article class="panel detail-panel"><div class="panel-heading"><div><h2>Controle do registro</h2></div></div><dl class="detail-list compact"><div><dt>Identificador</dt><dd>#<?= (int) $lead['id'] ?></dd></div><div><dt>Criado em</dt><dd><?= $createdAt ? date('d/m/Y H:i', $createdAt) : 'Não informado' ?></dd></div><div><dt>Última atualização</dt><dd><?= $updatedAt ? date('d/m/Y H:i', $updatedAt) : 'Não informado' ?></dd></div></dl></article>
  </aside>
</section>

<section class="panel lead-projects-panel"><div class="panel-heading"><div><h2>Projetos vinculados</h2><p><?= count($projects) ?> projeto(s) associado(s) a este cliente.</p></div><a href="<?= url('/projects') ?>">Ver todos os projetos</a></div><?php if($projects !== []): ?><div class="lead-project-list"><?php foreach($projects as $project): ?><a href="<?= url('/projects/edit?id='.(int)$project['id']) ?>"><div><span><?= e($project['codigo']) ?></span><strong><?= e($project['nome']) ?></strong><small><?= e($project['status']) ?> · <?= (int)$project['progresso'] ?>% concluído</small></div><div class="project-financial-summary"><strong>R$ <?= number_format((float)$project['valor_total'],2,',','.') ?></strong><small>Saldo: R$ <?= number_format((float)$project['saldo'],2,',','.') ?></small></div></a><?php endforeach; ?></div><?php else: ?><div class="empty-state compact">Nenhum projeto está vinculado a este lead.</div><?php endif; ?></section>
