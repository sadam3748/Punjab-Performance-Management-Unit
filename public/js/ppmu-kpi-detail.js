(function () {
    'use strict';

    const cfg = window.PPMU_KPI_DETAIL;
    if (!cfg) return;

    const G = '#087443', B = '#2563eb', O = '#e07b00', R = '#dc2626', T = '#0891b2';
    const statusPalette = { Approved: G, Submitted: B, Pending: O, Rejected: R };
    const grid = { color: 'rgba(100,116,139,.12)', drawBorder: false };
    const fnt = { family: "'Plus Jakarta Sans', system-ui, sans-serif", size: 11 };

    Chart.defaults.font = fnt;
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;

    const charts = {};
    let currentPeriodType = cfg.period?.period_type || cfg.defaults?.period_type || 'weekly';
    let fetchController = null;

    function statusBadgeHtml(label) {
        const map = { excellent: 'success', good: 'primary', attention: 'warning', critical: 'danger', approved: 'success', submitted: 'primary', rejected: 'danger', pending: 'warning' };
        const cls = map[String(label).toLowerCase()] || 'warning';
        const text = String(label).replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        return `<span class="badge rounded-pill text-bg-${cls}">${text}</span>`;
    }

    function fmtNum(n, decimals) {
        return Number(n).toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    }

    function destroyCharts() {
        Object.keys(charts).forEach(k => { if (charts[k]) { charts[k].destroy(); charts[k] = null; } });
    }

    function buildCharts(data) {
        destroyCharts();
        const statusDonut = data.status_donut || {};
        const statusLabels = Object.keys(statusDonut);
        const statusColors = statusLabels.map(l => statusPalette[l] || T);

        charts.status = new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{ data: Object.values(statusDonut), backgroundColor: statusColors, borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' } } }
            }
        });

        const ta = data.target_achieved || {};
        charts.target = new Chart(document.getElementById('targetChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(ta),
                datasets: [{ data: Object.values(ta), backgroundColor: [B, G], borderRadius: 8, borderSkipped: false, maxBarThickness: 48 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, grid, ticks: { font: fnt } }, x: { grid: { display: false }, ticks: { font: fnt } } },
                plugins: { legend: { display: false } }
            }
        });

        const trend = data.trend || {};
        charts.trend = new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: Object.keys(trend),
                datasets: [{
                    label: 'Achievement %', data: Object.values(trend), borderColor: G, backgroundColor: 'rgba(8,116,67,.08)',
                    fill: true, tension: .38, pointBackgroundColor: G, pointBorderColor: '#fff', pointBorderWidth: 2, pointRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100, grid, ticks: { font: fnt, callback: v => v + '%' } },
                    x: { grid: { display: false }, ticks: { font: fnt, maxRotation: 45, autoSkip: true, maxTicksLimit: 10 } }
                },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + '%' } } }
            }
        });

        const areas = data.areas || {};
        const areaColors = data.area_colors || Object.values(areas).map(v => v >= 85 ? G : (v >= 70 ? B : (v >= 50 ? O : R)));
        charts.area = new Chart(document.getElementById('areaChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(areas),
                datasets: [{ data: Object.values(areas), backgroundColor: areaColors, borderRadius: 6, borderSkipped: false, maxBarThickness: 22 }]
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { beginAtZero: true, max: 100, grid, ticks: { font: fnt, callback: v => v + '%' } },
                    y: { grid: { display: false }, ticks: { font: fnt } }
                },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.x + '%' } } }
            }
        });

        if (data.comparison_label) {
            const titleEl = document.getElementById('areaChart')?.closest('.ppmu-chart-card')?.querySelector('.card-ppmf-title');
            if (titleEl) titleEl.textContent = data.comparison_label;
        }
    }

    function updatePeriodRange(description) {
        const el = document.getElementById('kpiPeriodRangeLabel');
        if (el) {
            const span = el.querySelector('span');
            if (span) span.textContent = description || '';
        }
        const periodEl = document.getElementById('kpiDetailPeriodLabel');
        if (periodEl && description) {
            periodEl.innerHTML = '<i class="bi bi-calendar3"></i>' + description;
        }
    }

    function applyDefaultsToForm() {
        const filter = document.getElementById('kpiPeriodFilter');
        if (!filter || !cfg.defaults) return;
        const d = cfg.defaults;
        const set = (name, val) => {
            const el = filter.querySelector(`[data-filter="${name}"]`);
            if (el && val) el.value = val;
        };
        set('week_no', d.week_no);
        set('month', d.month);
        set('year', d.year);
        set('date', d.date);
    }

    function updateHeader(header) {
        const stats = document.getElementById('kpiDetailHeaderStats');
        if (!stats || !header) return;
        stats.querySelector('[data-stat="target"] strong').textContent = fmtNum(header.target, 1);
        stats.querySelector('[data-stat="reported"] strong').textContent = fmtNum(header.reported, 0);
        stats.querySelector('[data-stat="achieved"] strong').textContent = fmtNum(header.achieved, 1);
        stats.querySelector('[data-stat="pending"] strong').textContent = fmtNum(header.pending, 1);
        stats.querySelector('[data-stat="pct"] strong').textContent = header.achievement_percentage + '%';
        stats.querySelector('[data-stat="score"] strong').textContent = fmtNum(header.score, 2);
        const statusEl = stats.querySelector('[data-stat="status"]');
        if (statusEl) {
            const badge = statusEl.querySelector('.badge') || statusEl.lastElementChild;
            if (badge) badge.outerHTML = statusBadgeHtml(header.status_label);
        }
    }

    function bindInspectionFilters() {
        const form = document.getElementById('kpiInspectionFilter');
        if (!form || form.dataset.bound) return;
        form.dataset.bound = '1';
        form.querySelectorAll('[data-insp-filter]').forEach(el => {
            el.addEventListener('change', () => {
                const params = collectFilterParams({ insp_page: '1' });
                window.location.href = window.location.pathname + '?' + params.toString();
            });
        });
    }

    function collectFilterParams(extra) {
        const filter = document.getElementById('kpiPeriodFilter');
        const params = new URLSearchParams(extra || {});
        if (!filter) return params;

        if (currentPeriodType) {
            params.set('period_type', currentPeriodType);
            filter.querySelectorAll('[data-filter]').forEach(el => {
                if (el.hidden) return;
                if (el.value) params.set(el.dataset.filter, el.value);
            });
        }

        document.querySelectorAll('#kpiInspectionFilter [data-insp-filter]').forEach(el => {
            if (el.value) params.set(el.dataset.inspFilter, el.value);
        });

        return params;
    }

    function setLoading(on) {
        const wrap = document.getElementById('kpiDetailRefreshable');
        const loader = document.querySelector('.ppmu-filter-loading');
        if (wrap) wrap.classList.toggle('is-loading', on);
        if (loader) loader.hidden = !on;
    }

    async function loadDashboard(extraParams) {
        if (fetchController) fetchController.abort();
        fetchController = new AbortController();
        setLoading(true);

        const params = collectFilterParams(extraParams);
        const url = cfg.ajaxUrl + '?' + params.toString();

        try {
            const res = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: fetchController.signal,
            });
            if (!res.ok) throw new Error('Failed to load dashboard data');
            const data = await res.json();

            updateHeader(data.header);
            updatePeriodRange(data.period_description);
            document.getElementById('kpiDetailSummary').innerHTML = data.summary_html;
            document.getElementById('kpiDetailMetrics').innerHTML = data.metrics_html;

            const inspEl = document.getElementById('kpiDetailInspections');
            if (inspEl && data.inspections_html) {
                inspEl.innerHTML = data.inspections_html;
                bindInspectionFilters();
            }

            buildCharts({
                status_donut: data.charts.status_donut,
                target_achieved: data.charts.target_achieved,
                trend: data.charts.trend,
                areas: data.charts.areas,
                area_colors: data.area_chart_colors,
                comparison_label: data.charts.comparison_label,
            });

            history.replaceState(null, '', window.location.pathname + (params.toString() ? '?' + params.toString() : ''));
        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
        } finally {
            setLoading(false);
        }
    }

    function togglePeriodControls(type) {
        const filter = document.getElementById('kpiPeriodFilter');
        if (!filter) return;

        filter.querySelectorAll('[data-period-control]').forEach(el => {
            const scopes = (el.dataset.periodControl || '').split(/\s+/);
            el.hidden = type ? !scopes.includes(type) : !scopes.includes('all');
        });

        if (!type) {
            filter.querySelector('.ppmu-filter-month')?.removeAttribute('hidden');
            filter.querySelector('.ppmu-filter-year')?.removeAttribute('hidden');
        }
    }

    function activatePeriodPill(type) {
        const filter = document.getElementById('kpiPeriodFilter');
        if (!filter) return;
        filter.querySelectorAll('.ppmu-period-pills button').forEach(b => b.classList.remove('active'));
        filter.querySelector(`.ppmu-period-pills button[data-period-type="${type}"]`)?.classList.add('active');
        currentPeriodType = type;
        togglePeriodControls(type);
    }

    function resetToDefaults() {
        activatePeriodPill(cfg.defaults?.period_type || 'weekly');
        applyDefaultsToForm();
        loadDashboard({ page: '1' });
    }

    function syncUrlOnLoad() {
        if (!window.location.search && cfg.defaults) {
            activatePeriodPill(cfg.defaults.period_type || 'weekly');
            applyDefaultsToForm();
            const params = collectFilterParams();
            history.replaceState(null, '', window.location.pathname + '?' + params.toString());
        }
    }

    function initFilters() {
        const filter = document.getElementById('kpiPeriodFilter');
        if (!filter) return;

        syncUrlOnLoad();

        filter.querySelectorAll('.ppmu-period-pills button').forEach(btn => {
            btn.addEventListener('click', () => {
                activatePeriodPill(btn.dataset.periodType || '');
                if (!btn.dataset.periodType) {
                    filter.querySelectorAll('[data-filter]').forEach(el => { el.value = ''; });
                } else {
                    applyDefaultsToForm();
                }
                loadDashboard({ page: '1' });
            });
        });

        filter.querySelectorAll('[data-filter]').forEach(el => {
            el.addEventListener('change', () => loadDashboard({ page: '1' }));
        });

        filter.querySelector('[data-filter-reset]')?.addEventListener('click', resetToDefaults);

        document.addEventListener('click', e => {
            const link = e.target.closest('#kpiDetailInspections .pagination a.page-link');
            if (!link || link.closest('.disabled')) return;
            e.preventDefault();
            const page = new URL(link.href).searchParams.get('insp_page') || '1';
            loadDashboard({ insp_page: page });
        });
    }

    buildCharts(cfg.charts);
    bindInspectionFilters();
    initFilters();
})();
