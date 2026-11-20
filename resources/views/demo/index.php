<div class="demo-shell">
  <aside class="demo-sidebar" id="demo-sidebar">
    <a class="brand-logo-link" href="<?= url('/') ?>" aria-label="Nelber Code"><img class="brand-logo-image" src="<?= asset('images/nelber-code-logo.png') ?>" alt="Nelber Code"></a>
    <span class="demo-mode-badge">Demonstração pública</span>
    <nav class="demo-nav" aria-label="Módulos da demonstração">
      <?php foreach ([['dashboard','D','Dashboard'],['leads','L','Leads'],['pipeline','P','Pipeline Comercial'],['projects','P','Projetos'],['finance','F','Financeiro'],['reports','R','Relatórios']] as [$section,$icon,$label]): ?>
      <button type="button" data-demo-section="<?= e($section) ?>" class="<?= $section === 'dashboard' ? 'active' : '' ?>"><span class="nav-icon"><?= e($icon) ?></span><?= e($label) ?></button>
      <?php endforeach; ?>
    </nav>
    <div class="demo-sidebar-note"><span class="status-dot"></span><div><strong>Ambiente seguro</strong><small>Nenhum dado real é utilizado</small></div></div>
    <a class="demo-back-link" href="<?= url('/') ?>">← Voltar ao site institucional</a>
  </aside>

  <div class="demo-workspace">
    <header class="demo-topbar">
      <button class="icon-button demo-menu-button" type="button" data-demo-nav-toggle aria-label="Abrir menu">☰</button>
      <button class="demo-search" type="button" data-demo-title="Busca global" data-demo-message="Aqui sua equipe encontra rapidamente leads, empresas e projetos em toda a plataforma."><span>⌕</span><span>Buscar lead, projeto ou cliente</span><kbd>Demo</kbd></button>
      <div class="demo-top-actions"><span class="demo-readonly">Somente visualização</span><a class="button button-primary" href="<?= url('/login') ?>">Entrar no sistema</a></div>
    </header>

    <div class="demo-notice"><strong>Você está explorando uma demonstração.</strong><span>Todos os nomes, valores e atividades são fictícios. Nenhuma ação será salva.</span></div>

    <main class="demo-content">
      <section class="demo-panel active" data-demo-panel="dashboard">
        <div class="page-heading"><div><span class="eyebrow dark">Visão executiva</span><h1>Dashboard demonstrativo</h1><p>Uma leitura clara da operação comercial, dos projetos e da receita.</p></div><div class="heading-actions"><button class="button button-secondary" type="button" data-demo-section="reports">Ver relatórios</button><button class="button button-primary" type="button" data-demo-title="Novo lead" data-demo-message="Aqui você adiciona um novo lead manualmente ou escolhe a importação por planilha e JSON.">+ Novo lead</button></div></div>
        <div class="metric-grid demo-metrics">
          <?php foreach ([['Total de leads','148','Base ativa'],['Em contato','42','Acompanhamento'],['Negociação','19','Prioridade comercial'],['Contratados','31','20,9% de conversão'],['Projetos ativos','12','Em andamento'],['Receita prevista','R$ 284 mil','Contratos']] as [$label,$value,$caption]): ?><article class="metric-card"><div><span><?= e($label) ?></span><b><?= e($value) ?></b><small><?= e($caption) ?></small></div><i><?= e(mb_substr($label, 0, 1)) ?></i></article><?php endforeach; ?>
        </div>
        <div class="demo-chart-grid">
          <article class="panel chart-panel"><div class="panel-heading"><div><h2>Conversão nos últimos 6 meses</h2><p>Leads captados e contratos fechados</p></div><span class="live-data"><i></i> Dados fictícios</span></div><div class="chart-frame chart-large"><canvas id="demoConversionChart" aria-label="Exemplo de conversão comercial"></canvas></div></article>
          <article class="panel chart-panel"><div class="panel-heading"><div><h2>Funil comercial</h2><p>Novo → Em Contato → Negociação → Contratado</p></div></div><div class="chart-frame chart-large"><canvas id="demoFunnelChart" aria-label="Exemplo de funil comercial"></canvas></div></article>
        </div>
        <div class="demo-summary-grid">
          <article class="panel"><div class="panel-heading"><div><h2>Atividades recentes</h2><p>Histórico organizado da equipe</p></div><button class="demo-text-action" type="button" data-demo-title="Auditoria" data-demo-message="Aqui você consulta a trilha completa de acessos e alterações para governança e conformidade.">Ver auditoria</button></div><div class="activity-list"><div><i></i><p><strong>Marina atualizou a oportunidade Atlas</strong><span>Há 18 minutos · Negociação</span></p></div><div><i></i><p><strong>Projeto Aurora avançou para Desenvolvimento</strong><span>Há 1 hora · Projetos</span></p></div><div><i></i><p><strong>Pagamento de R$ 8.400 confirmado</strong><span>Hoje, 09:42 · Financeiro</span></p></div></div></article>
          <article class="panel"><div class="panel-heading"><div><h2>Próximos vencimentos</h2><p>Valores a receber por projeto</p></div><button class="demo-text-action" type="button" data-demo-section="finance">Ver financeiro</button></div><div class="due-list"><div><span class="date-tile"><b>18</b><small>OUT</small></span><p><strong>Projeto Orion</strong><small>R$ 12.500 · Parcela final</small></p></div><div><span class="date-tile"><b>24</b><small>OUT</small></span><p><strong>Portal Horizonte</strong><small>R$ 7.800 · Segunda parcela</small></p></div></div></article>
        </div>
      </section>

      <section class="demo-panel" data-demo-panel="leads" hidden>
        <div class="page-heading"><div><span class="eyebrow dark">CRM Comercial</span><h1>Leads</h1><p>Contatos, empresas e histórico de prospecção em um só lugar.</p></div><div class="heading-actions"><button class="button button-secondary" type="button" data-demo-title="Importar leads" data-demo-message="Aqui você importa uma planilha ou arquivo JSON. O sistema valida os campos e evita duplicidades.">Importar</button><button class="button button-primary" type="button" data-demo-title="Novo lead" data-demo-message="Aqui você cadastra empresa, contato, origem, base legal e informações da prospecção.">+ Novo lead</button></div></div>
        <article class="panel"><div class="demo-table-toolbar"><strong>148 oportunidades</strong><button type="button" data-demo-title="Filtros de leads" data-demo-message="Aqui você filtra por etapa, origem, responsável e período.">Filtrar resultados</button></div><div class="table-wrap"><table><thead><tr><th>Empresa</th><th>Contato</th><th>Origem</th><th>Status</th><th>Responsável</th><th>Ações</th></tr></thead><tbody>
          <?php foreach ([['Atlas Digital','camila@atlas.demo','Indicação','Negociação','Marina'],['Lumina Tech','contato@lumina.demo','LinkedIn','Em Contato','Rafael'],['Vértice Consultoria','ana@vertice.demo','Google Ads','Novo','Juliana'],['Orbe Sistemas','comercial@orbe.demo','Evento','Contratado','Marina']] as [$company,$email,$origin,$status,$owner]): ?><tr><td><strong><?= e($company) ?></strong><small>Empresa fictícia</small></td><td><?= e($email) ?></td><td><?= e($origin) ?></td><td><span class="badge <?= $status === 'Contratado' ? 'contratado' : ($status === 'Negociação' ? 'negociação' : '') ?>"><?= e($status) ?></span></td><td><?= e($owner) ?></td><td><button class="demo-row-action" type="button" data-demo-title="Detalhes do lead" data-demo-message="Aqui você visualiza e edita todos os dados, atividades e projetos relacionados ao lead.">Visualizar</button></td></tr><?php endforeach; ?>
        </tbody></table></div></article>
      </section>

      <section class="demo-panel" data-demo-panel="pipeline" hidden>
        <div class="page-heading"><div><span class="eyebrow dark">Pipeline Comercial</span><h1>Funil de vendas</h1><p>Acompanhe oportunidades e avance cada negociação com contexto.</p></div><button class="button button-primary" type="button" data-demo-title="Nova oportunidade" data-demo-message="Aqui você cria uma oportunidade e define a primeira etapa do relacionamento comercial.">+ Nova oportunidade</button></div>
        <div class="demo-kanban">
          <?php foreach (['Novo'=>[['Vértice Consultoria','R$ 9.500'],['Nuvem Criativa','R$ 6.200']],'Em Contato'=>[['Lumina Tech','R$ 18.000'],['Plena Saúde','R$ 11.400']],'Negociação'=>[['Atlas Digital','R$ 24.800'],['Mosaico Lab','R$ 15.600']],'Contratado'=>[['Orbe Sistemas','R$ 32.000']]] as $stage=>$cards): ?><section><header><strong><?= e($stage) ?></strong><b><?= count($cards) ?></b></header><?php foreach ($cards as [$company,$value]): ?><button class="demo-deal-card" type="button" data-demo-title="Oportunidade: <?= e($company) ?>" data-demo-message="Aqui você consulta o histórico, registra contatos e move a oportunidade entre as etapas do funil."><strong><?= e($company) ?></strong><span><?= e($value) ?></span><small>Atualizado hoje</small></button><?php endforeach; ?></section><?php endforeach; ?>
        </div>
      </section>

      <section class="demo-panel" data-demo-panel="projects" hidden>
        <div class="page-heading"><div><span class="eyebrow dark">Entrega e operação</span><h1>Projetos</h1><p>Prazos, progresso e valores conectados ao cliente contratado.</p></div><button class="button button-primary" type="button" data-demo-title="Novo projeto" data-demo-message="Aqui você cria um projeto para um cliente contratado, define valor, prazo e etapa inicial.">+ Novo projeto</button></div>
        <div class="demo-project-grid"><?php foreach ([['NX-1042','Portal Atlas','Desenvolvimento',68,'R$ 24.800','28/10'],['NX-1041','Automação Lumina','Layout',35,'R$ 18.000','12/11'],['NX-1038','CRM Orbe','Homologação',88,'R$ 32.000','20/10'],['NX-1035','Landing Mosaico','Briefing',12,'R$ 15.600','05/12']] as [$code,$name,$stage,$progress,$value,$date]): ?><article class="panel demo-project-card"><span><?= e($code) ?></span><h2><?= e($name) ?></h2><p><?= e($stage) ?> · entrega <?= e($date) ?></p><div class="progress"><i style="width:<?= (int) $progress ?>%"></i></div><div><small><?= (int) $progress ?>% concluído</small><strong><?= e($value) ?></strong></div><button type="button" data-demo-title="Editar projeto" data-demo-message="Aqui você atualiza etapas, progresso, datas e informações financeiras do projeto.">Ver detalhes</button></article><?php endforeach; ?></div>
      </section>

      <section class="demo-panel" data-demo-panel="finance" hidden>
        <div class="page-heading"><div><span class="eyebrow dark">Controle financeiro</span><h1>Financeiro</h1><p>Previsão, recebimentos e saldos organizados por projeto.</p></div><button class="button button-primary" type="button" data-demo-title="Registrar pagamento" data-demo-message="Aqui você registra um recebimento e acompanha automaticamente o saldo do projeto.">+ Registrar pagamento</button></div>
        <div class="metric-grid metric-grid-3"><article class="metric-card"><div><span>Contratado</span><b>R$ 284.000</b><small>Valor total</small></div><i>C</i></article><article class="metric-card"><div><span>Recebido</span><b>R$ 196.400</b><small>69,2% realizado</small></div><i>R</i></article><article class="metric-card"><div><span>A receber</span><b>R$ 87.600</b><small>Saldo previsto</small></div><i>S</i></article></div>
        <article class="panel"><div class="panel-heading"><div><h2>Receita mensal</h2><p>Contratado versus recebido</p></div><button class="demo-text-action" type="button" data-demo-title="Exportar financeiro" data-demo-message="Aqui você exporta a visão financeira em um relatório para análise gerencial.">Exportar</button></div><div class="chart-frame chart-large"><canvas id="demoRevenueChart" aria-label="Exemplo de receita mensal"></canvas></div></article>
      </section>

      <section class="demo-panel" data-demo-panel="reports" hidden>
        <div class="page-heading"><div><span class="eyebrow dark">Inteligência de dados</span><h1>Relatórios</h1><p>Indicadores comerciais e operacionais para apoiar decisões.</p></div><button class="button button-primary" type="button" data-demo-title="Exportar relatório" data-demo-message="Aqui você escolhe o formato e exporta os indicadores respeitando suas permissões de acesso.">Exportar relatório</button></div>
        <div class="report-grid demo-report-grid"><?php foreach ([['↗','Performance comercial','Conversão, volume e evolução por período.'],['◎','Origem dos leads','Canais que geram as melhores oportunidades.'],['▥','Receita e recebimentos','Previsão financeira e valores realizados.'],['✓','Projetos e entregas','Distribuição por etapa, prazo e progresso.'],['⌁','Atividades da equipe','Ações e atualizações realizadas no CRM.'],['◇','Conformidade e LGPD','Auditoria, exportações e eventos de segurança.']] as [$icon,$title,$text]): ?><article class="panel report-card"><span><?= e($icon) ?></span><h2><?= e($title) ?></h2><p><?= e($text) ?></p><button type="button" data-demo-title="<?= e($title) ?>" data-demo-message="Aqui você abre este relatório, seleciona o período e detalha os indicadores.">Visualizar exemplo</button></article><?php endforeach; ?></div>
      </section>
    </main>
  </div>
</div>

<dialog class="demo-explainer" id="demo-explainer" aria-labelledby="demo-explainer-title">
  <div class="demo-explainer-card"><button type="button" class="dialog-close" data-close aria-label="Fechar">×</button><span class="demo-explainer-icon">◎</span><small>Demonstração interativa</small><h2 id="demo-explainer-title">Como funciona</h2><p id="demo-explainer-message">Esta ação será apresentada aqui.</p><div class="demo-explainer-note">Nesta demonstração nenhuma informação é criada, alterada ou excluída.</div><button class="button button-primary" type="button" data-close>Entendi</button></div>
</dialog>

<script type="application/json" id="demo-dashboard-data"><?= json_encode([
  'labels' => ['Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out'],
  'leads' => [18, 24, 21, 32, 27, 26],
  'sales' => [3, 5, 4, 7, 6, 6],
  'funnelLabels' => ['Novo', 'Em Contato', 'Negociação', 'Contratado'],
  'funnelValues' => [56, 42, 19, 31],
  'forecast' => [38000, 42000, 36000, 57000, 49000, 62000],
  'received' => [24000, 35000, 32000, 41000, 36000, 48400],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
