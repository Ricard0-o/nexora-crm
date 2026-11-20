<?= csrf_field() ?>
<?php if(!empty($lead['id'])): ?><input type="hidden" name="id" value="<?= (int)$lead['id'] ?>"><?php endif; ?>
<div class="form-section-heading"><span>01</span><div><h2>Empresa e contato</h2><p>Dados necessários para o relacionamento comercial.</p></div></div>
<div class="form-grid">
  <label>Empresa <em>*</em><input name="nome_empresa" value="<?= old('nome_empresa',(string)($lead['nome_empresa']??'')) ?>" maxlength="180" required><?php if(isset($errors['nome_empresa'])): ?><small class="field-error"><?= e($errors['nome_empresa']) ?></small><?php endif; ?></label>
  <label>Nome do contato<input name="nome" value="<?= old('nome',(string)($lead['nome']??'')) ?>" maxlength="120"><?php if(isset($errors['nome'])): ?><small class="field-error"><?= e($errors['nome']) ?></small><?php endif; ?></label>
  <label>Ramo de atividade <em>*</em><input name="ramo_atividade" value="<?= old('ramo_atividade',(string)($lead['ramo_atividade']??'')) ?>" maxlength="120" required><?php if(isset($errors['ramo_atividade'])): ?><small class="field-error"><?= e($errors['ramo_atividade']) ?></small><?php endif; ?></label>
  <label>E-mail <em>*</em><input type="email" name="email" value="<?= old('email',(string)($lead['email']??'')) ?>" maxlength="190" required><?php if(isset($errors['email'])): ?><small class="field-error"><?= e($errors['email']) ?></small><?php endif; ?></label>
  <label>Telefone <em>*</em><input name="telefone" value="<?= old('telefone',(string)($lead['telefone']??'')) ?>" maxlength="30" placeholder="(11) 99999-9999" required><?php if(isset($errors['telefone'])): ?><small class="field-error"><?= e($errors['telefone']) ?></small><?php endif; ?></label>
  <label>Origem<?php $selectedOrigin=htmlspecialchars_decode(old('origem',(string)($lead['origem']??'Manual'))); ?><select name="origem"><?php foreach(['Manual','Indicação','Landing Page','LinkedIn','Google Ads','Evento','Orgânico','Importação JSON'] as $origin): ?><option <?= $selectedOrigin===$origin?'selected':'' ?>><?= e($origin) ?></option><?php endforeach; ?></select></label>
</div>
<div class="form-section-heading"><span>02</span><div><h2>Qualificação e LGPD</h2><p>Estágio comercial, justificativa e finalidade do tratamento.</p></div></div>
<div class="form-grid">
  <?php $selectedStatus=htmlspecialchars_decode(old('status',(string)($lead['status']??'Novo'))); ?>
  <label>Status<select name="status"><?php foreach(['Novo','Em Contato','Negociação','Contratado','Recusado'] as $s): ?><option <?= $selectedStatus===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?></select></label>
  <?php $selectedBasis=htmlspecialchars_decode(old('base_legal',(string)($lead['base_legal']??'Legítimo interesse'))); ?>
  <label>Base legal<select name="base_legal"><?php foreach(['Legítimo interesse','Consentimento','Execução de contrato'] as $basis): ?><option <?= $selectedBasis===$basis?'selected':'' ?>><?= e($basis) ?></option><?php endforeach; ?></select></label>
  <label class="form-span-2">Finalidade<input name="finalidade" value="<?= old('finalidade',(string)($lead['finalidade']??'Prospecção comercial B2B')) ?>" maxlength="255" required></label>
  <label class="form-span-2">Observações<textarea name="observacao_prospeccao" rows="5" maxlength="3000" placeholder="Contexto, necessidade e próxima ação..."><?= old('observacao_prospeccao',(string)($lead['observacao_prospeccao']??'')) ?></textarea></label>
</div>
<div class="form-footer"><p><strong>Privacidade por padrão</strong><span>Mantenha somente dados necessários à finalidade informada.</span></p><div><a class="button button-secondary" href="<?= url('/leads') ?>">Cancelar</a><button class="button button-primary" type="submit"><?= e($submitLabel) ?></button></div></div>
