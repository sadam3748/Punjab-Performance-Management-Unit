/* ============================================================
   PPMF PORTAL — Dashboard JavaScript
   Chart.js powered charts + UI interactions
   ============================================================ */

'use strict';

// ── Theme Colors ──────────────────────────────────────────
const COLORS = {
  green:   '#0a5c36',
  greenMid:'#0d7a47',
  teal:    '#0e8c6a',
  gold:    '#c9952a',
  danger:  '#d9363e',
  warning: '#e07b00',
  info:    '#1a6fa8',
  muted:   '#8896a5',
  border:  '#e2e8f0',
  bg:      '#f0f2f5',
};

// ── Chart Defaults ────────────────────────────────────────
if (window.Chart) {
  Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
  Chart.defaults.font.size   = 12;
  Chart.defaults.color       = COLORS.muted;
  Chart.defaults.plugins.legend.labels.boxWidth = 10;
  Chart.defaults.plugins.legend.labels.padding  = 16;
  Chart.defaults.plugins.tooltip.backgroundColor = '#fff';
  Chart.defaults.plugins.tooltip.titleColor      = '#0f1b2d';
  Chart.defaults.plugins.tooltip.bodyColor       = '#4a5568';
  Chart.defaults.plugins.tooltip.borderColor     = COLORS.border;
  Chart.defaults.plugins.tooltip.borderWidth     = 1;
  Chart.defaults.plugins.tooltip.padding         = 10;
  Chart.defaults.plugins.tooltip.cornerRadius    = 8;
  Chart.defaults.plugins.tooltip.titleFont       = { weight: '700', size: 13 };
}

// ── Monthly Performance Chart ─────────────────────────────
function initMonthlyChart(canvasId) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;

  const labels = ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];

  return new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Target',
          data: [80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80],
          borderColor: COLORS.gold,
          borderWidth: 1.5,
          borderDash: [6, 3],
          pointRadius: 0,
          fill: false,
          tension: 0,
        },
        {
          label: 'Achievement',
          data: [62, 68, 71, 65, 74, 70, 78, 73, 76, 81, 79, 83],
          borderColor: COLORS.green,
          backgroundColor: 'rgba(10,92,54,.06)',
          borderWidth: 2.5,
          pointBackgroundColor: COLORS.green,
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          fill: true,
          tension: .4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { position: 'top', align: 'end' } },
      scales: {
        x: {
          grid: { color: COLORS.border, drawTicks: false },
          border: { display: false },
          ticks: { padding: 8 },
        },
        y: {
          grid: { color: COLORS.border, drawTicks: false },
          border: { display: false },
          ticks: { padding: 8, callback: v => v + '%' },
          min: 0, max: 100,
        },
      },
    },
  });
}

// ── Department Performance Chart ──────────────────────────
function initDeptChart(canvasId) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;

  const depts = ['Health', 'Education', 'Agriculture', 'Finance', 'Works', 'Police', 'Revenue', 'Forest'];
  const scores = [82, 76, 71, 65, 88, 70, 77, 68];
  const colors = scores.map(s =>
    s >= 80 ? COLORS.green :
    s >= 70 ? COLORS.teal  :
    s >= 60 ? COLORS.gold  : COLORS.danger
  );

  return new Chart(ctx, {
    type: 'bar',
    data: {
      labels: depts,
      datasets: [{
        label: 'Performance Score',
        data: scores,
        backgroundColor: colors,
        borderRadius: 6,
        borderSkipped: false,
        barPercentage: .6,
        categoryPercentage: .7,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: {
          grid: { display: false },
          border: { display: false },
          ticks: { padding: 6 },
        },
        y: {
          grid: { color: COLORS.border, drawTicks: false },
          border: { display: false },
          ticks: { padding: 8, callback: v => v + '%' },
          min: 0, max: 100,
        },
      },
    },
  });
}

// ── KPI Donut Chart ───────────────────────────────────────
function initKpiDonut(canvasId) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;

  return new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Achieved', 'In Progress', 'Critical'],
      datasets: [{
        data: [148, 62, 24],
        backgroundColor: [COLORS.green, COLORS.gold, COLORS.danger],
        borderWidth: 0,
        hoverOffset: 6,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { padding: 16 },
        },
        tooltip: {
          callbacks: {
            label: ctx => ` ${ctx.label}: ${ctx.raw} KPIs`,
          },
        },
      },
    },
  });
}

// ── Tier Comparison Radar ─────────────────────────────────
function initTierRadar(canvasId) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;

  return new Chart(ctx, {
    type: 'radar',
    data: {
      labels: ['Health', 'Education', 'Law & Order', 'Revenue', 'Development', 'Environment'],
      datasets: [
        {
          label: 'Tier 1',
          data: [85, 79, 90, 72, 88, 76],
          borderColor: COLORS.green,
          backgroundColor: 'rgba(10,92,54,.12)',
          borderWidth: 2,
          pointBackgroundColor: COLORS.green,
        },
        {
          label: 'Tier 2',
          data: [70, 65, 75, 68, 72, 64],
          borderColor: COLORS.gold,
          backgroundColor: 'rgba(201,149,42,.08)',
          borderWidth: 2,
          pointBackgroundColor: COLORS.gold,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        r: {
          grid: { color: COLORS.border },
          ticks: { display: false },
          pointLabels: { font: { size: 11, weight: '600' }, color: '#4a5568' },
          min: 0, max: 100,
        },
      },
      plugins: { legend: { position: 'bottom' } },
    },
  });
}

// ── Division Horizontal Bar ───────────────────────────────
function initDivisionChart(canvasId) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;

  const divisions  = ['Lahore', 'Multan', 'Rawalpindi', 'Faisalabad', 'Gujranwala', 'Sargodha', 'Bahawalpur', 'Sahiwal', 'DG Khan'];
  const scores     = [88, 82, 79, 84, 76, 71, 68, 74, 65];
  const colors     = scores.map(s =>
    s >= 80 ? COLORS.green :
    s >= 70 ? COLORS.teal  : COLORS.gold
  );

  return new Chart(ctx, {
    type: 'bar',
    data: {
      labels: divisions,
      datasets: [{
        label: 'Score',
        data: scores,
        backgroundColor: colors,
        borderRadius: 5,
        borderSkipped: false,
        barPercentage: .65,
      }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: {
          grid: { color: COLORS.border, drawTicks: false },
          border: { display: false },
          ticks: { callback: v => v + '%' },
          min: 0, max: 100,
        },
        y: {
          grid: { display: false },
          border: { display: false },
          ticks: { font: { size: 11 } },
        },
      },
    },
  });
}

// ── Sidebar toggle ────────────────────────────────────────
function initSidebar() {
  const toggle   = document.querySelectorAll('.header-toggle, .sidebar-toggle');
  const sidebar  = document.querySelector('.ppmf-sidebar');
  const overlay  = document.querySelector('.sidebar-overlay');
  const isMobile = () => window.matchMedia && window.matchMedia('(max-width: 1024px)').matches;

  if (!sidebar) return;

  toggle.forEach(btn => {
    btn.addEventListener('click', () => {
      if (!isMobile()) return;
      sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('show');
    });
  });

  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('show');
    });
  }

  window.addEventListener('resize', () => {
    if (!isMobile()) {
      sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('show');
    }
  });
}

// ── Tab switching ─────────────────────────────────────────
function initTabs() {
  document.querySelectorAll('[data-tab-target]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target  = btn.dataset.tabTarget;
      const parent  = btn.closest('[data-tabs]') || document;

      parent.querySelectorAll('[data-tab-target]').forEach(b => b.classList.remove('active'));
      parent.querySelectorAll('[data-tab-pane]').forEach(p => p.classList.add('d-none'));

      btn.classList.add('active');
      const pane = document.getElementById(target);
      if (pane) pane.classList.remove('d-none');
    });
  });
}

// ── Dropdown ──────────────────────────────────────────────
function initDropdowns() {
  document.querySelectorAll('[data-dropdown]').forEach(trigger => {
    const menu = document.getElementById(trigger.dataset.dropdown);
    if (!menu) return;

    trigger.addEventListener('click', e => {
      e.stopPropagation();
      const isOpen = menu.classList.contains('show');
      document.querySelectorAll('.dropdown-menu-ppmf.show').forEach(m => m.classList.remove('show'));
      if (!isOpen) menu.classList.add('show');
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu-ppmf.show').forEach(m => m.classList.remove('show'));
  });
}

// ── Animate stat counters ─────────────────────────────────
function animateCounters() {
  document.querySelectorAll('[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count, 10);
    const duration = 1200;
    const start    = performance.now();

    const tick = now => {
      const elapsed = now - start;
      const progress = Math.min(elapsed / duration, 1);
      const ease     = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(ease * target).toLocaleString();
      if (progress < 1) requestAnimationFrame(tick);
    };

    requestAnimationFrame(tick);
  });
}

// ── Progress bar animation ────────────────────────────────
function animateProgressBars() {
  document.querySelectorAll('[data-progress]').forEach(bar => {
    const val = parseInt(bar.dataset.progress, 10);
    setTimeout(() => { bar.style.width = val + '%'; }, 100);
  });
}

// ── Toast notification ────────────────────────────────────
function showToast(message, type = 'success') {
  const colors = { success: COLORS.green, danger: COLORS.danger, warning: COLORS.warning };
  const icons  = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', warning: 'bi-exclamation-circle-fill' };

  const toast = document.createElement('div');
  toast.style.cssText = `
    position:fixed; bottom:24px; right:24px; z-index:9999;
    background:#fff; border:1px solid #e2e8f0; border-left:4px solid ${colors[type] || COLORS.green};
    border-radius:10px; padding:14px 18px; display:flex; align-items:center; gap:10px;
    box-shadow:0 10px 40px rgba(0,0,0,.12); font-family:var(--font);
    font-size:13px; font-weight:500; max-width:340px;
    animation:fadeInUp .3s ease;
  `;
  toast.innerHTML = `<i class="bi ${icons[type] || icons.success}" style="color:${colors[type] || COLORS.green};font-size:18px;"></i> ${message}`;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3200);
}

// ── Filter reset ──────────────────────────────────────────
function initFilterReset() {
  document.querySelectorAll('[data-reset-filters]').forEach(btn => {
    btn.addEventListener('click', () => {
      const form = btn.closest('.filter-panel');
      if (!form) return;
      form.querySelectorAll('select, input').forEach(el => {
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
      });
      showToast('Filters have been reset.', 'success');
    });
  });
}

// ── Init all ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initTabs();
  initDropdowns();
  animateCounters();
  animateProgressBars();
  initFilterReset();

  // Charts — dashboard
  initMonthlyChart('monthlyChart');
  initDeptChart('deptChart');
  initKpiDonut('kpiDonut');
  initTierRadar('tierRadar');
  initDivisionChart('divisionChart');

  // Charts — reports / scorecard pages (may not all exist on every page)
  initDeptChart('reportDeptChart');
  initDivisionChart('reportDivChart');
});
