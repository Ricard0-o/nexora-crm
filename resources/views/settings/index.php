<div class="page-heading"><div><span class="eyebrow dark">Administração</span><h1>Configurações</h1><p>Preferências persistentes da organização e da sessão.</p></div></div>

<form class="settings-form" method="post" action="<?= url('/settings') ?>">
  <?= csrf_field() ?>
  <div class="settings-grid">
    <section class="panel">
      <div class="form-section-heading"><span>01</span><div><h2>Organização</h2><p>Identidade e referência de horário do ambiente.</p></div></div>
      <label>Nome da organização<input name="organization_name" maxlength="120" required value="<?= old('organization_name', $settings['organization_name']) ?>"></label>
      <label>Fuso horário<select name="timezone" required><?php $selectedTimezone = $_SESSION['_old']['timezone'] ?? $settings['timezone']; foreach ($timezones as $timezone): ?><option value="<?= e($timezone) ?>" <?= $selectedTimezone === $timezone ? 'selected' : '' ?>><?= e($timezone) ?></option><?php endforeach; ?></select></label>
    </section>
    <section class="panel">
      <div class="form-section-heading"><span>02</span><div><h2>Dashboard e sessão</h2><p>Comportamento padrão para usuários autenticados.</p></div></div>
      <label>Período padrão do Dashboard<select name="dashboard_period" required><?php $selectedPeriod = $_SESSION['_old']['dashboard_period'] ?? $settings['dashboard_period']; foreach ($periods as $value => $label): ?><option value="<?= e($value) ?>" <?= $selectedPeriod === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
      <label>Expiração por inatividade<select name="session_timeout_minutes" required><?php $selectedTimeout = $_SESSION['_old']['session_timeout_minutes'] ?? $settings['session_timeout_minutes']; foreach ($sessionTimeouts as $value => $label): ?><option value="<?= e($value) ?>" <?= $selectedTimeout === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
    </section>
    <section class="panel settings-status-panel">
      <h2>Segurança</h2>
      <div class="setting-row"><div><strong>CSRF e sessão segura</strong><span>Proteções ativas em todas as gravações.</span></div><b class="status-ready">Ativo</b></div>
      <div class="setting-row"><div><strong>Autenticação em dois fatores</strong><span>Programada para a próxima fase de segurança.</span></div><b class="status-planned">Planejado</b></div>
    </section>
    <section class="panel settings-status-panel">
      <h2>Integrações</h2>
      <div class="integration-list"><span>Mercado Pago <b>Planejado</b></span><span>WhatsApp API <b>Planejado</b></span><span>SMTP / SendGrid <b>Planejado</b></span><span>Webhooks HMAC <b>Preparado</b></span></div>
    </section>
  </div>
  <div class="settings-save-bar"><div><strong>Alterações auditadas</strong><span>O sistema registra quais configurações foram modificadas.</span></div><button class="button button-primary" type="submit">Salvar configurações</button></div>
</form>
