<main class="subscription-page">
  <section class="subscription-card">
    <a class="brand-logo-link" href="<?= url('/') ?>" aria-label="Nelber Code"><img class="brand-logo-image" src="<?= asset('images/nelber-code-logo.png') ?>" alt="Nelber Code"></a>
    <?php if ($m = flash('success')): ?><div class="alert success"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash('error')): ?><div class="alert error"><?= e($m) ?></div><?php endif; ?>
    <span class="login-confirmed"><i>✓</i> Login confirmado</span>
    <h1>Seu acesso está quase pronto.</h1>
    <p>Olá, <?= e($_SESSION['user']['name'] ?? '') ?>. Sua conta <strong><?= e($_SESSION['user']['email'] ?? '') ?></strong> foi autenticada. Escolha Pix ou cartão no ambiente protegido do Mercado Pago.</p>
    <div class="subscription-plan"><div><span><?= e($payment['planName']) ?></span><strong><?= $payment['amount'] !== null ? 'R$ ' . number_format((float) $payment['amount'], 2, ',', '.') : 'Valor a definir' ?></strong><small>Acesso por <?= (int) $payment['accessDays'] ?> dias após a confirmação.</small></div><span class="badge neutral"><?= e($_SESSION['user']['subscription_status'] ?? 'Pendente') ?></span></div>
    <div class="payment-options">
      <article><span>PIX</span><div><strong>Pix</strong><small>Pagamento à vista com confirmação consultada diretamente no Mercado Pago.</small></div></article>
      <article><span>12×</span><div><strong>Cartões</strong><small>Crédito com parcelamento de até <?= min(12, max(1, (int) \App\Helpers\Env::get('CRM_MAX_INSTALLMENTS', '12'))) ?> vezes, conforme aprovação.</small></div></article>
    </div>
    <div class="subscription-actions">
      <form class="payment-checkout-form" method="post" action="<?= url('/payments/checkout') ?>"><?= csrf_field() ?><button class="button button-primary" type="submit" <?= !$payment['checkoutReady'] ? 'disabled' : '' ?>>Pagar com Pix ou cartão</button></form>
      <a class="button button-secondary" href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener noreferrer">Tirar dúvida pelo WhatsApp</a>
      <a class="button button-secondary" href="mailto:<?= e($contactEmail) ?>?subject=Ativação%20do%20CRM">Falar por e-mail</a>
    </div>
    <?php if (!$payment['ready']): ?><div class="payment-setup-note"><strong>Integração preparada — configuração pendente</strong><span>Ainda são necessários: <?= e(implode(', ', $payment['missing'])) ?>.</span></div><?php else: ?><p class="payment-note">Ambiente: <?= e($payment['environment'] === 'production' ? 'produção' : 'testes') ?>. O acesso só é liberado depois que o Mercado Pago confirmar o pagamento.</p><?php endif; ?>
    <form class="subscription-logout" method="post" action="<?= url('/logout') ?>"><?= csrf_field() ?><button type="submit">Sair da conta</button></form>
  </section>
</main>
