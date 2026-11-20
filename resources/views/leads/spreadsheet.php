<div class="page-heading"><div><span class="eyebrow dark">Conversão local</span><h1>Planilha para JSON</h1><p>Preencha a planilha-modelo e gere um arquivo pronto para importar no CRM.</p></div><a class="button button-secondary" href="<?= url('/leads') ?>">Voltar para leads</a></div>

<div class="method-switch three-methods"><a href="<?= url('/leads/create') ?>"><span>Método 1</span><strong>Cadastro manual</strong><small>Uma empresa por vez, diretamente no site.</small></a><a href="<?= url('/leads/import') ?>"><span>Método 2</span><strong>Importação JSON</strong><small>Importe um arquivo JSON já existente.</small></a><a class="active" href="<?= url('/leads/spreadsheet') ?>"><span>Método 3</span><strong>Planilha para JSON</strong><small>Converta Excel ou CSV no próprio navegador.</small></a></div>

<section class="spreadsheet-converter-grid" id="spreadsheet-converter">
  <article class="panel spreadsheet-guide">
    <div class="panel-heading"><div><h2>1. Baixe e preencha o modelo</h2><p>Os cabeçalhos já correspondem ao importador do CRM.</p></div><span class="badge neutral">Excel</span></div>
    <a class="button button-primary" href="<?= asset('assets/templates/modelo-importacao-leads.xlsx') ?>" download>Baixar planilha modelo</a>
    <ul>
      <li>Use uma linha para cada cliente ou empresa.</li>
      <li>Não altere os nomes das colunas.</li>
      <li>Empresa, ramo, e-mail e telefone são obrigatórios.</li>
      <li>O limite é de 1.000 registros e 5 MB por arquivo.</li>
    </ul>
    <div class="privacy-note"><strong>Privacidade</strong><span>A conversão acontece no seu navegador. A planilha não é enviada para serviços externos.</span></div>
  </article>

  <article class="panel spreadsheet-converter-panel">
    <div class="panel-heading"><div><h2>2. Selecione a planilha</h2><p>Formatos aceitos: XLSX, XLS e CSV.</p></div><span class="badge neutral">Até 5 MB</span></div>
    <label class="spreadsheet-file-field" for="lead-spreadsheet-file"><span>Escolher planilha</span><small id="spreadsheet-file-name">Nenhum arquivo selecionado</small><input id="lead-spreadsheet-file" type="file" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"></label>
    <div class="converter-message" id="converter-message" role="status" aria-live="polite">Selecione a planilha preenchida para validar os dados.</div>
    <div class="converter-errors" id="converter-errors" hidden></div>
    <div class="converter-preview" id="converter-preview" hidden><strong>Prévia dos dados</strong><div class="table-wrap"><table><thead><tr><th>Empresa</th><th>Contato</th><th>E-mail</th><th>Telefone</th></tr></thead><tbody id="converter-preview-body"></tbody></table></div></div>
    <div class="dialog-actions spreadsheet-actions"><a class="button button-secondary" href="<?= url('/leads/import') ?>">Ir para importação</a><button class="button button-primary" id="generate-leads-json" type="button" disabled>Gerar arquivo JSON</button></div>
  </article>
</section>

<script src="<?= asset('js/xlsx.full.min.js') ?>" defer></script>
<script src="<?= asset('js/lead-spreadsheet.js') ?>" defer></script>
