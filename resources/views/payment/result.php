<main class="subscription-page">
  <section class="subscription-card payment-result-card">
    <a class="brand-logo-link" href="<?= url('/') ?>" aria-label="Nelber Code"><img class="brand-logo-image" src="<?= asset('images/nelber-code-logo.png') ?>" alt="Nelber Code"></a>
    <span class="payment-result-icon <?= $paymentStatus === 'Aprovado' ? 'approved' : ($paymentStatus === 'Pendente' ? 'pending' : 'failed') ?>"><?= $paymentStatus === 'Aprovado' ? '✓' : ($paymentStatus === 'Pendente' ? '…' : '!') ?></span>
    <span class="eyebrow dark">Status do pagamento</span>
    <h1><?= e($paymentStatus) ?></h1>
    <p><?= e($message) ?></p>
    <div class="subscription-actions">
      <?php if ($paymentStatus === 'Aprovado'): ?><a class="button button-primary" href="<?= url('/dashboard') ?>">Acessar o CRM</a><?php else: ?><a class="button button-primary" href="<?= url('/subscription') ?>">Voltar às formas de pagamento</a><?php endif; ?>
      <a class="button button-secondary" href="https://wa.me/5500000000000?text=<?= rawurlencode('Olá, Ricardo! Preciso de ajuda com o pagamento do CRM.') ?>" target="_blank" rel="noopener noreferrer">Pedir ajuda</a>
    </div>
  </section>
</main>
