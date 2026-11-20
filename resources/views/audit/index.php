<div class="page-heading">
  <div><span class="eyebrow dark">Governança</span><h1>Auditoria</h1><p>Rastreabilidade de eventos críticos e ações de usuários.</p></div>
  <form method="post" action="<?= url('/reports/export') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="audit">
    <button class="button button-secondary" type="submit">Exportar trilha CSV</button>
  </form>
</div>
<aside class="audit-context" aria-label="Informação sobre endereços IP">
  <strong>Como o IP é identificado</strong>
  <span>Em localhost, <code>127.0.0.1</code> ou <code>::1</code> é esperado. Cabeçalhos de proxy só são aceitos quando o proxy está autorizado no ambiente.</span>
</aside>
<section class="panel">
  <div class="panel-heading"><div><h2>Eventos registrados</h2><p>Últimos 50 eventos; a exportação inclui até o limite configurado no ambiente.</p></div></div>
  <div class="table-wrap"><table><thead><tr><th>Data</th><th>Ação</th><th>Entidade</th><th>Usuário</th><th>IP</th></tr></thead><tbody>
  <?php foreach ($events as $event): ?><tr>
    <td><?= date('d/m/Y H:i:s', strtotime($event['criado_em'])) ?></td>
    <td><span class="badge neutral"><?= e($event['acao']) ?></span></td>
    <td><?= e($event['entidade']) ?> <?= $event['id_entidade'] ? '#' . (int) $event['id_entidade'] : '' ?></td>
    <td><?= e($event['usuario_nome'] ?? 'Sistema') ?></td>
    <td><code><?= e($event['ip']) ?></code></td>
  </tr><?php endforeach; ?>
  <?php if ($events === []): ?><tr><td colspan="5"><div class="empty-state compact">Nenhum evento de auditoria registrado.</div></td></tr><?php endif; ?>
  </tbody></table></div>
</section>
