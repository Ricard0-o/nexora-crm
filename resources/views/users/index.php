<div class="page-heading"><div><span class="eyebrow dark">Administração</span><h1>Usuários e assinaturas</h1><p>Confirme pagamentos e controle quem pode utilizar o CRM.</p></div></div>
<section class="panel">
  <div class="panel-heading"><div><h2>Contas cadastradas</h2><p>Até 50 contas recentes. Toda alteração fica registrada na auditoria.</p></div></div>
  <div class="table-wrap"><table><thead><tr><th>Usuário</th><th>Perfil</th><th>Último login</th><th>Status da assinatura</th><th>Validade</th><th>Ação</th></tr></thead><tbody>
  <?php foreach ($users as $user): ?><tr>
    <td><strong><?= e($user['nome']) ?></strong><small><?= e($user['email']) ?></small></td>
    <td><span class="badge neutral"><?= e($user['perfil']) ?></span></td>
    <td><?= e($user['ultimo_login_em'] ? date('d/m/Y H:i', strtotime($user['ultimo_login_em'])) : 'Nunca acessou') ?></td>
    <td><span class="badge <?= $user['assinatura_status'] === 'Ativa' ? 'contratado' : 'neutral' ?>"><?= e($user['assinatura_status']) ?></span></td>
    <td><?= e($user['assinatura_valida_ate'] ? date('d/m/Y', strtotime($user['assinatura_valida_ate'])) : 'Sem vencimento') ?></td>
    <td><form class="subscription-admin-form" method="post" action="<?= url('/users/subscription') ?>"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>"><select name="subscription_status" aria-label="Status da assinatura de <?= e($user['nome']) ?>"><?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $status === $user['assinatura_status'] ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select><input type="date" name="subscription_valid_until" value="<?= e($user['assinatura_valida_ate'] ? date('Y-m-d', strtotime($user['assinatura_valida_ate'])) : '') ?>" aria-label="Validade da assinatura de <?= e($user['nome']) ?>"><button class="button button-secondary" type="submit">Salvar</button></form></td>
  </tr><?php endforeach; ?>
  </tbody></table></div>
</section>
