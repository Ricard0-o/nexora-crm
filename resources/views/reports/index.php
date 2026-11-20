<div class="page-heading report-heading">
  <div><span class="eyebrow dark">Inteligência</span><h1>Relatórios executivos</h1><p>Indicadores calculados com os dados reais do CRM. Atualizado em <?= e($generatedAt) ?>.</p></div>
  <div class="export-actions" aria-label="Exportar relatórios">
    <?php foreach (['leads' => 'Leads', 'projects' => 'Projetos', 'finance' => 'Financeiro', 'audit' => 'Auditoria'] as $type => $label): ?>
      <form method="post" action="<?= url('/reports/export') ?>"><?= csrf_field() ?><input type="hidden" name="type" value="<?= e($type) ?>"><button class="button button-secondary" type="submit">CSV <?= e($label) ?></button></form>
    <?php endforeach; ?>
  </div>
</div>

<section class="metric-grid report-metrics">
  <article class="metric-card"><div><span>Conversão comercial</span><b><?= number_format((float) $metrics['conversion'], 1, ',', '.') ?>%</b><small><?= (int) $metrics['leads'] ?> leads analisados</small></div></article>
  <article class="metric-card"><div><span>Receita prevista</span><b>R$ <?= number_format((float) $metrics['forecast'], 2, ',', '.') ?></b><small>Contratos cadastrados</small></div></article>
  <article class="metric-card"><div><span>Saldo a receber</span><b>R$ <?= number_format((float) $metrics['balance'], 2, ',', '.') ?></b><small><?= (int) $metrics['overdueProjects'] ?> projeto(s) vencido(s)</small></div></article>
  <article class="metric-card"><div><span>Projetos ativos</span><b><?= (int) $metrics['activeProjects'] ?></b><small><?= (int) $metrics['loginFailures'] ?> falha(s) de login em 30 dias</small></div></article>
</section>

<section class="report-dashboard-grid">
  <article class="panel report-chart-wide"><div class="panel-heading"><div><h2>Desempenho comercial</h2><p>Leads criados e oportunidades contratadas nos últimos seis meses</p></div><span class="live-data"><i></i> Dados reais</span></div><div class="chart-frame chart-medium"><canvas id="reportConversionChart" role="img" aria-label="Gráfico de desempenho comercial"></canvas></div></article>
  <article class="panel report-chart-wide"><div class="panel-heading"><div><h2>Receita e recebimentos</h2><p>Valores contratados e recebidos por mês</p></div><a href="<?= url('/finance') ?>">Abrir financeiro</a></div><div class="chart-frame chart-medium"><canvas id="reportRevenueChart" role="img" aria-label="Gráfico de receita e recebimentos"></canvas></div></article>
  <article class="panel"><div class="panel-heading"><div><h2>Origem dos leads</h2><p>Distribuição por canal de aquisição</p></div></div><div class="chart-frame chart-donut"><canvas id="reportOriginChart" role="img" aria-label="Gráfico de origem dos leads"></canvas></div></article>
  <article class="panel"><div class="panel-heading"><div><h2>Funil comercial</h2><p>Volume atual em cada etapa</p></div><a href="<?= url('/pipeline') ?>">Abrir pipeline</a></div><div class="chart-frame chart-donut"><canvas id="reportLeadStatusChart" role="img" aria-label="Gráfico do funil comercial"></canvas></div></article>
  <article class="panel"><div class="panel-heading"><div><h2>Projetos por status</h2><p>Capacidade e andamento das entregas</p></div><a href="<?= url('/projects') ?>">Abrir projetos</a></div><div class="chart-frame chart-donut"><canvas id="reportProjectStatusChart" role="img" aria-label="Gráfico de projetos por status"></canvas></div></article>
  <article class="panel"><div class="panel-heading"><div><h2>Auditoria e LGPD</h2><p>Eventos registrados nos últimos 30 dias</p></div><a href="<?= url('/audit') ?>">Ver auditoria</a></div><div class="chart-frame chart-donut"><canvas id="reportAuditChart" role="img" aria-label="Gráfico de auditoria e LGPD"></canvas></div></article>
</section>

<section class="report-detail-grid">
  <article class="panel"><div class="panel-heading"><div><h2>Projetos recentes</h2><p>Posição operacional e financeira</p></div></div><div class="table-wrap"><table><thead><tr><th>Projeto</th><th>Status</th><th>Progresso</th><th>Saldo</th></tr></thead><tbody>
  <?php foreach ($recentProjects as $project): ?><tr><td><a class="entity-link" href="<?= url('/projects/edit?id=' . (int) $project['id']) ?>"><strong><?= e($project['nome']) ?></strong><small><?= e($project['nome_empresa']) ?></small></a></td><td><span class="badge"><?= e($project['status']) ?></span></td><td><?= (int) $project['progresso'] ?>%</td><td>R$ <?= number_format((float) $project['saldo'], 2, ',', '.') ?></td></tr><?php endforeach; ?>
  <?php if ($recentProjects === []): ?><tr><td colspan="4"><div class="empty-state compact">Nenhum projeto cadastrado.</div></td></tr><?php endif; ?>
  </tbody></table></div></article>
  <article class="panel"><div class="panel-heading"><div><h2>Eventos recentes</h2><p>Rastreabilidade de ações críticas</p></div></div><div class="activity-list">
  <?php foreach ($recentAudit as $event): ?><div><i></i><p><strong><?= e($event['acao']) ?> · <?= e($event['entidade']) ?></strong><span><?= e($event['usuario_nome'] ?? 'Sistema') ?> · <?= date('d/m/Y H:i', strtotime($event['criado_em'])) ?></span></p></div><?php endforeach; ?>
  <?php if ($recentAudit === []): ?><div class="empty-state compact">Nenhum evento registrado.</div><?php endif; ?>
  </div></article>
</section>

<script type="application/json" id="reports-data"><?= json_encode($charts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
