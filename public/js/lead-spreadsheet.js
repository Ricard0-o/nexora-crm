document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('#spreadsheet-converter');
  if (!root) return;

  const fileInput = root.querySelector('#lead-spreadsheet-file');
  const fileName = root.querySelector('#spreadsheet-file-name');
  const message = root.querySelector('#converter-message');
  const errorsBox = root.querySelector('#converter-errors');
  const preview = root.querySelector('#converter-preview');
  const previewBody = root.querySelector('#converter-preview-body');
  const generateButton = root.querySelector('#generate-leads-json');

  const fields = ['nome', 'nome_empresa', 'ramo_atividade', 'email', 'telefone', 'observacao_prospeccao', 'base_legal', 'finalidade'];
  const required = ['nome_empresa', 'ramo_atividade', 'email', 'telefone'];
  const limits = { nome: 120, nome_empresa: 180, ramo_atividade: 120, email: 190, telefone: 30, observacao_prospeccao: 3000, base_legal: 100, finalidade: 255 };
  let validRecords = [];

  const normalizeHeader = (value) => String(value ?? '')
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '');

  const reset = () => {
    validRecords = [];
    generateButton.disabled = true;
    errorsBox.hidden = true;
    errorsBox.replaceChildren();
    preview.hidden = true;
    previewBody.replaceChildren();
    message.className = 'converter-message';
  };

  const showErrors = (errors) => {
    errorsBox.replaceChildren();
    const title = document.createElement('strong');
    title.textContent = `Foram encontrados ${errors.length} problema(s):`;
    const list = document.createElement('ul');
    errors.slice(0, 15).forEach((error) => {
      const item = document.createElement('li');
      item.textContent = error;
      list.appendChild(item);
    });
    if (errors.length > 15) {
      const item = document.createElement('li');
      item.textContent = `E mais ${errors.length - 15} problema(s).`;
      list.appendChild(item);
    }
    errorsBox.append(title, list);
    errorsBox.hidden = false;
    message.textContent = 'Corrija a planilha e selecione o arquivo novamente.';
    message.className = 'converter-message error';
  };

  const renderPreview = (records) => {
    previewBody.replaceChildren();
    records.slice(0, 5).forEach((record) => {
      const row = document.createElement('tr');
      [record.nome_empresa, record.nome || '—', record.email, record.telefone].forEach((value) => {
        const cell = document.createElement('td');
        cell.textContent = value;
        row.appendChild(cell);
      });
      previewBody.appendChild(row);
    });
    preview.hidden = false;
  };

  const validateRecord = (record, spreadsheetRow) => {
    const errors = [];
    required.forEach((field) => {
      if (!record[field]) errors.push(`Linha ${spreadsheetRow}: o campo ${field} é obrigatório.`);
    });
    Object.entries(limits).forEach(([field, limit]) => {
      if (record[field] && record[field].length > limit) errors.push(`Linha ${spreadsheetRow}: ${field} excede ${limit} caracteres.`);
    });
    if (record.nome_empresa && record.nome_empresa.length < 2) errors.push(`Linha ${spreadsheetRow}: nome_empresa deve ter ao menos 2 caracteres.`);
    if (record.ramo_atividade && record.ramo_atividade.length < 2) errors.push(`Linha ${spreadsheetRow}: ramo_atividade deve ter ao menos 2 caracteres.`);
    if (record.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(record.email)) errors.push(`Linha ${spreadsheetRow}: e-mail inválido.`);
    if (record.telefone && !/^[0-9+()\-\s]{8,20}$/.test(record.telefone)) errors.push(`Linha ${spreadsheetRow}: telefone inválido.`);
    return errors;
  };

  fileInput.addEventListener('change', async () => {
    reset();
    const file = fileInput.files?.[0];
    fileName.textContent = file?.name || 'Nenhum arquivo selecionado';
    if (!file) {
      message.textContent = 'Selecione a planilha preenchida para validar os dados.';
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      showErrors(['O arquivo excede o limite de 5 MB.']);
      return;
    }
    if (!window.XLSX) {
      showErrors(['O leitor de planilhas não foi carregado. Atualize a página e tente novamente.']);
      return;
    }

    message.textContent = 'Lendo e validando a planilha…';
    try {
      const buffer = await file.arrayBuffer();
      const workbook = window.XLSX.read(buffer, { type: 'array', sheetRows: 1010, cellText: true, bookVBA: false });
      if (!workbook.SheetNames.length) throw new Error('A planilha não possui abas legíveis.');
      const worksheet = workbook.Sheets[workbook.SheetNames[0]];
      const rows = window.XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '', raw: false, blankrows: true });
      const headerIndex = rows.findIndex((row) => required.every((field) => row.map(normalizeHeader).includes(field)));
      if (headerIndex < 0) throw new Error('Os cabeçalhos obrigatórios não foram encontrados. Use a planilha-modelo fornecida pelo CRM.');

      const normalizedHeaders = rows[headerIndex].map(normalizeHeader);
      const unknownHeaders = normalizedHeaders.filter((header) => header && !fields.includes(header));
      if (unknownHeaders.length) throw new Error(`Coluna(s) não reconhecida(s): ${[...new Set(unknownHeaders)].join(', ')}.`);

      const records = [];
      const validationErrors = [];
      rows.slice(headerIndex + 1).forEach((row, index) => {
        const record = {};
        normalizedHeaders.forEach((header, columnIndex) => {
          if (!fields.includes(header)) return;
          const value = String(row[columnIndex] ?? '').trim();
          if (value !== '' || required.includes(header)) record[header] = value;
        });
        if (!fields.some((field) => String(record[field] ?? '').trim() !== '')) return;
        const spreadsheetRow = headerIndex + index + 2;
        validationErrors.push(...validateRecord(record, spreadsheetRow));
        records.push(record);
      });

      if (!records.length) validationErrors.push('Nenhum lead preenchido foi encontrado na planilha.');
      if (records.length > 1000) validationErrors.push('A planilha excede o limite de 1.000 registros.');
      if (validationErrors.length) {
        showErrors(validationErrors);
        return;
      }

      validRecords = records;
      generateButton.disabled = false;
      renderPreview(records);
      message.textContent = `${records.length} lead(s) validado(s). O arquivo JSON está pronto para ser gerado.`;
      message.className = 'converter-message success';
    } catch (error) {
      showErrors([error instanceof Error ? error.message : 'Não foi possível ler a planilha.']);
    }
  });

  generateButton.addEventListener('click', () => {
    if (!validRecords.length) return;
    const json = JSON.stringify(validRecords, null, 2);
    const blob = new Blob([json], { type: 'application/json;charset=utf-8' });
    const downloadUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    const date = new Date().toISOString().slice(0, 10);
    link.href = downloadUrl;
    link.download = `leads-importacao-${date}.json`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
    message.textContent = `JSON gerado com ${validRecords.length} lead(s). Agora você pode importá-lo no CRM.`;
    message.className = 'converter-message success';
  });
});
