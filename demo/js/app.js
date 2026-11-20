document.addEventListener('DOMContentLoaded', () => {
  const demoCharts = [];
  const menu = document.querySelector('[data-menu]');
  menu?.addEventListener('click', () => document.querySelector('#sidebar')?.classList.toggle('open'));

  document.querySelector('[data-demo-nav-toggle]')?.addEventListener('click', () => {
    document.querySelector('#demo-sidebar')?.classList.toggle('open');
  });

  const demoExplainer = document.querySelector('#demo-explainer');
  document.querySelectorAll('[data-demo-message]').forEach((button) => {
    button.addEventListener('click', () => {
      const title = demoExplainer?.querySelector('#demo-explainer-title');
      const message = demoExplainer?.querySelector('#demo-explainer-message');
      if (title) title.textContent = button.dataset.demoTitle || 'Como funciona';
      if (message) message.textContent = button.dataset.demoMessage || 'Esta funcionalidade está disponível no ambiente autenticado.';
      demoExplainer?.showModal();
    });
  });

  document.querySelectorAll('[data-demo-section]').forEach((button) => {
    button.addEventListener('click', () => {
      const section = button.dataset.demoSection;
      document.querySelectorAll('[data-demo-panel]').forEach((panel) => {
        const isActive = panel.dataset.demoPanel === section;
        panel.hidden = !isActive;
        panel.classList.toggle('active', isActive);
      });
      document.querySelectorAll('[data-demo-section]').forEach((item) => {
        item.classList.toggle('active', item.dataset.demoSection === section);
      });
      document.querySelector('#demo-sidebar')?.classList.remove('open');
      window.scrollTo({ top: 0, behavior: 'smooth' });
      requestAnimationFrame(() => demoCharts.forEach((chart) => chart.resize()));
    });
  });

  document.querySelector('[data-password]')?.addEventListener('click', (event) => {
    const input = event.currentTarget.parentElement.querySelector('input');
    input.type = input.type === 'password' ? 'text' : 'password';
  });

  document.querySelectorAll('[data-dialog]').forEach((button) => {
    button.addEventListener('click', () => document.getElementById(button.dataset.dialog)?.showModal());
  });
  document.querySelectorAll('[data-close]').forEach((button) => {
    button.addEventListener('click', () => button.closest('dialog')?.close());
  });

  document.querySelectorAll('[data-row-link]').forEach((row) => {
    const navigate = () => {
      const destination = row.dataset.rowLink;
      if (destination) window.location.assign(destination);
    };
    row.addEventListener('click', (event) => {
      if (event.target.closest('a, button, input, select, textarea, label, form')) return;
      navigate();
    });
    row.addEventListener('keydown', (event) => {
      if (event.target !== row || !['Enter', ' '].includes(event.key)) return;
      event.preventDefault();
      navigate();
    });
  });

  document.querySelectorAll('[data-auto-submit]').forEach((select) => {
    select.addEventListener('change', () => select.form?.requestSubmit());
  });

  if (!window.Chart) return;

  const colors = ['#1E88E5', '#42A5F5', '#0F4C81', '#2E7D32', '#ED6C02', '#7A5AF8'];
  const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 7, padding: 18 } } },
    scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#eef2f6' }, ticks: { precision: 0 } } }
  };

  const doughnut = (selector, labels, values) => {
    const canvas = document.querySelector(selector);
    if (!canvas) return;
    const hasData = values.length > 0 && values.some((value) => Number(value) > 0);
    new Chart(canvas, {
      type: 'doughnut',
      data: { labels: hasData ? labels : ['Sem dados'], datasets: [{ data: hasData ? values : [1], backgroundColor: hasData ? colors : ['#dce6ef'], borderWidth: 0 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }
    });
  };

  const dashboardSource = document.querySelector('#dashboard-data');
  if (dashboardSource) {
    const data = JSON.parse(dashboardSource.textContent);
    const timelineOptions = {
      ...baseOptions,
      scales: {
        ...baseOptions.scales,
        x: {
          ...baseOptions.scales.x,
          ticks: { autoSkip: true, maxTicksLimit: Number(data.maxXTicks) || 12, maxRotation: 0 }
        }
      }
    };
    const conversion = document.querySelector('#conversionChart');
    if (conversion) new Chart(conversion, {
      type: 'line',
      data: { labels: data.labels, datasets: [
        { label: 'Leads', data: data.leads, borderColor: '#1E88E5', backgroundColor: 'rgba(30,136,229,.10)', fill: true, tension: .38, pointRadius: 3 },
        { label: 'Contratos', data: data.sales, borderColor: '#2E7D32', backgroundColor: 'transparent', tension: .38, pointRadius: 3 }
      ] },
      options: timelineOptions
    });

    const revenue = document.querySelector('#revenueChart');
    if (revenue) new Chart(revenue, {
      type: 'bar',
      data: { labels: data.labels, datasets: [
        { label: 'Contratado', data: data.forecast, backgroundColor: '#0F4C81', borderRadius: 6 },
        { label: 'Recebido', data: data.received, backgroundColor: '#42A5F5', borderRadius: 6 }
      ] },
      options: { ...timelineOptions, plugins: { ...timelineOptions.plugins, tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${Number(ctx.raw).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}` } } } }
    });

    const funnel = document.querySelector('#funnelChart');
    if (funnel) new Chart(funnel, {
      type: 'bar',
      data: {
        labels: data.funnelLabels,
        datasets: [{
          label: 'Leads',
          data: data.funnelValues,
          backgroundColor: ['#42A5F5', '#1E88E5', '#ED6C02', '#2E7D32'],
          borderRadius: 7,
          borderSkipped: false
        }]
      },
      options: {
        ...baseOptions,
        indexAxis: 'y',
        plugins: { ...baseOptions.plugins, legend: { display: false } },
        scales: {
          x: { beginAtZero: true, grid: { color: '#eef2f6' }, ticks: { precision: 0 } },
          y: { grid: { display: false } }
        }
      }
    });

    doughnut('#originChart', data.originLabels, data.originValues);
    doughnut('#projectStatusChart', data.projectStatusLabels, data.projectStatusValues);
  }

  const demoSource = document.querySelector('#demo-dashboard-data');
  if (demoSource) {
    const data = JSON.parse(demoSource.textContent);
    const conversion = document.querySelector('#demoConversionChart');
    if (conversion) demoCharts.push(new Chart(conversion, {
      type: 'line',
      data: { labels: data.labels, datasets: [
        { label: 'Leads', data: data.leads, borderColor: '#1E88E5', backgroundColor: 'rgba(30,136,229,.10)', fill: true, tension: .38, pointRadius: 3 },
        { label: 'Contratos', data: data.sales, borderColor: '#2E7D32', backgroundColor: 'transparent', tension: .38, pointRadius: 3 }
      ] },
      options: baseOptions
    }));

    const funnel = document.querySelector('#demoFunnelChart');
    if (funnel) demoCharts.push(new Chart(funnel, {
      type: 'bar',
      data: { labels: data.funnelLabels, datasets: [{ label: 'Leads', data: data.funnelValues, backgroundColor: ['#42A5F5', '#1E88E5', '#ED6C02', '#2E7D32'], borderRadius: 7, borderSkipped: false }] },
      options: {
        ...baseOptions,
        indexAxis: 'y',
        plugins: { ...baseOptions.plugins, legend: { display: false } },
        scales: { x: { beginAtZero: true, grid: { color: '#eef2f6' }, ticks: { precision: 0 } }, y: { grid: { display: false } } }
      }
    }));

    const revenue = document.querySelector('#demoRevenueChart');
    if (revenue) demoCharts.push(new Chart(revenue, {
      type: 'bar',
      data: { labels: data.labels, datasets: [
        { label: 'Contratado', data: data.forecast, backgroundColor: '#0F4C81', borderRadius: 6 },
        { label: 'Recebido', data: data.received, backgroundColor: '#42A5F5', borderRadius: 6 }
      ] },
      options: { ...baseOptions, plugins: { ...baseOptions.plugins, tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${Number(ctx.raw).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}` } } } }
    }));
  }

  const reportsSource = document.querySelector('#reports-data');
  if (reportsSource) {
    const data = JSON.parse(reportsSource.textContent);
    const commercial = document.querySelector('#reportConversionChart');
    if (commercial) new Chart(commercial, {
      type: 'line',
      data: { labels: data.labels, datasets: [
        { label: 'Leads', data: data.leads, borderColor: '#1E88E5', backgroundColor: 'rgba(30,136,229,.10)', fill: true, tension: .38, pointRadius: 3 },
        { label: 'Contratados', data: data.contracts, borderColor: '#2E7D32', backgroundColor: 'transparent', tension: .38, pointRadius: 3 }
      ] },
      options: baseOptions
    });

    const revenue = document.querySelector('#reportRevenueChart');
    if (revenue) new Chart(revenue, {
      type: 'bar',
      data: { labels: data.labels, datasets: [
        { label: 'Contratado', data: data.forecast, backgroundColor: '#0F4C81', borderRadius: 6 },
        { label: 'Recebido', data: data.received, backgroundColor: '#42A5F5', borderRadius: 6 }
      ] },
      options: { ...baseOptions, plugins: { ...baseOptions.plugins, tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${Number(ctx.raw).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}` } } } }
    });

    doughnut('#reportOriginChart', data.originLabels, data.originValues);
    doughnut('#reportLeadStatusChart', data.leadStatusLabels, data.leadStatusValues);
    doughnut('#reportProjectStatusChart', data.projectStatusLabels, data.projectStatusValues);
    doughnut('#reportAuditChart', data.auditLabels, data.auditValues);
  }
});
