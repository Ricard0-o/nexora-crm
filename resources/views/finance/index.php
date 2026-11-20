<div class="page-heading">
  <div><span class="eyebrow dark">Financeiro</span><h1>Visão financeira</h1><p>Receita prevista, recebimentos e saldos dos projetos.</p></div>
  <form method="post" action="<?= url('/reports/export') ?>"><?= csrf_field() ?><input type="hidden" name="type" value="finance"><button class="button button-secondary" type="submit">Exportar CSV</button></form>
</div>
<section class="metric-grid metric-grid-3">
  <article class="metric-card"><div><span>Receita prevista</span><b>R$ <?= number_format($metrics['prevista'], 2, ',', '.') ?></b><small>Contratos cadastrados</small></div></article>
  <article class="metric-card"><div><span>Receita recebida</span><b>R$ <?= number_format($metrics['recebida'], 2, ',', '.') ?></b><small>Acumulado</small></div></article>
  <article class="metric-card"><div><span>Saldo a receber</span><b>R$ <?= number_format($metrics['prevista'] - $metrics['recebida'], 2, ',', '.') ?></b><small>Posição consolidada</small></div></article>
</section>
<section class="panel">
  <div class="panel-heading"><div><h2>Contratos e recebimentos</h2><p>Clique no projeto para abrir e editar seu cadastro.</p></div><a href="<?= url('/projects') ?>">Ver todos os projetos</a></div>
  <div class="table-wrap"><table><thead><tr><th>Projeto</th><th>Cliente</th><th>Valor total</th><th>Recebido</th><th>Saldo</th><th>Vencimento</th><th>Ação</th></tr></thead><tbody>
  <?php foreach ($projects as $project): ?><tr>
    <td><a class="entity-link" href="<?= url('/projects/edit?id=' . (int) $project['id']) ?>"><strong><?= e($project['nome']) ?></strong><small><?= e($project['codigo']) ?></small></a></td>
    <td><?= e($project['nome_empresa']) ?></td>
    <td>R$ <?= number_format((float) $project['valor_total'], 2, ',', '.') ?></td>
    <td class="positive">R$ <?= number_format((float) $project['valor_pago'], 2, ',', '.') ?></td>
    <td>R$ <?= number_format((float) $project['saldo'], 2, ',', '.') ?></td>
    <td><?= $project['data_vencimento'] ? date('d/m/Y', strtotime($project['data_vencimento'])) : '—' ?></td>
    <td><a class="table-action" href="<?= url('/projects/edit?id=' . (int) $project['id']) ?>">Abrir projeto</a></td>
  </tr><?php endforeach; ?>
  <?php if ($projects === []): ?><tr><td colspan="7"><div class="empty-state compact">Nenhum projeto financeiro cadastrado.</div></td></tr><?php endif; ?>
  </tbody></table></div>
</section>
