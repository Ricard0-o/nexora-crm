<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e(app_setting('organization_name', 'Nelber Code')) ?></title>
  <link rel="icon" type="image/png" href="<?= asset('images/nelber-code-mark.png') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <script src="<?= asset('js/chart.umd.min.js') ?>" defer></script>
  <script src="<?= asset('js/app.js') ?>" defer></script>
</head>
<body class="app-page">
  <aside class="sidebar" id="sidebar">
    <a class="brand-logo-link" href="<?= url('/dashboard') ?>" aria-label="Nelber Code"><img class="brand-logo-image" src="<?= asset('images/nelber-code-logo.png') ?>" alt="Nelber Code"></a>
    <nav class="side-nav" aria-label="Navegação principal">
      <?php $items = [['/dashboard','grid','Dashboard'],['/leads','users','Leads'],['/pipeline','funnel','Pipeline Comercial'],['/projects','folder','Projetos'],['/finance','wallet','Financeiro'],['/reports','chart','Relatórios'],['/audit','shield','Auditoria']]; if (($_SESSION['user']['role'] ?? '') === 'Administrador') { $items[] = ['/users','account','Usuários']; } $items[] = ['/settings','settings','Configurações']; foreach ($items as [$href,$icon,$label]): ?>
      <a href="<?= url($href) ?>" class="<?= str_starts_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '', parse_url(url($href), PHP_URL_PATH) ?: '') ? 'active' : '' ?>"><span class="nav-icon"><?= strtoupper(substr($label,0,1)) ?></span><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="public-site-link" href="<?= url('/') ?>"><span class="nav-icon">⌂</span><span><strong>Ver site institucional</strong><small>Abrir landing page</small></span></a>
    <div class="sidebar-trust"><span class="status-dot"></span><div><strong><?= e(app_setting('organization_name', 'Ambiente protegido')) ?></strong><small>Monitoramento ativo</small></div></div>
  </aside>
  <div class="app-shell">
    <header class="topbar">
      <button class="icon-button menu-button" data-menu aria-label="Abrir menu">☰</button>
      <form class="global-search" method="get" action="<?= url('/search') ?>" role="search"><input type="search" name="q" value="<?= e((string) ($_GET['q'] ?? '')) ?>" placeholder="Buscar lead, projeto ou cliente" aria-label="Busca global" minlength="2"><button class="search-submit" type="submit" aria-label="Executar pesquisa"><span aria-hidden="true">⌕</span> Buscar</button></form>
      <div class="top-actions"><a class="icon-button mobile-search-button" href="<?= url('/search') ?>" aria-label="Abrir busca">⌕</a><a class="icon-button" href="<?= url('/audit') ?>" aria-label="Ver atividades recentes">♢<span class="notification-dot"></span></a><details class="profile-menu"><summary class="profile"><span><?= e(mb_strtoupper(mb_substr($_SESSION['user']['name'] ?? 'U',0,1))) ?></span><div><strong><?= e($_SESSION['user']['name'] ?? '') ?></strong><small><?= e($_SESSION['user']['role'] ?? '') ?></small></div><b>⌄</b></summary><div class="profile-dropdown"><div><strong><?= e($_SESSION['user']['email'] ?? '') ?></strong><span>Sessão protegida</span></div><a href="<?= url('/settings') ?>">Configurações</a><form method="post" action="<?= url('/logout') ?>"><?= csrf_field() ?><button type="submit">Sair da conta</button></form></div></details></div>
    </header>
    <main class="main-content">
      <?php if ($m = flash('success')): ?><div class="alert success"><?= e($m) ?></div><?php endif; ?>
      <?php if ($m = flash('error')): ?><div class="alert error"><?= e($m) ?></div><?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</body>
</html>
