(function () {
    'use strict';

    const cfg = window.PPMU_KPI_MAIN;
    if (!cfg) return;

    let currentPeriodType = cfg.period?.period_type || cfg.defaults?.period_type || 'weekly';
    let fetchController = null;

    function updatePeriodRange(description) {
        const el = document.getElementById('kpiPeriodRangeLabel');
        if (el) {
            const span = el.querySelector('span');
            if (span) span.textContent = description || '';
        }
    }

    function updateCardLinks(periodQuery) {
        document.querySelectorAll('[data-kpi-detail-link]').forEach(link => {
            const base = link.href.split('?')[0];
            link.href = periodQuery ? `${base}?${periodQuery}` : base;
        });
    }

    function applyDefaultsToForm() {
        const filter = document.getElementById('kpiPeriodFilter');
        if (!filter || !cfg.defaults) return;

        const d = cfg.defaults;
        const week = filter.querySelector('[data-filter="week_no"]');
        const month = filter.querySelector('[data-filter="month"]');
        const year = filter.querySelector('[data-filter="year"]');
        const date = filter.querySelector('[data-filter="date"]');

        if (week && d.week_no) week.value = d.week_no;
        if (month && d.month) month.value = d.month;
        if (year && d.year) year.value = d.year;
        if (date && d.date) date.value = d.date;
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

        return params;
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

    function setLoading(on) {
        const wrap = document.getElementById('kpiMainRefreshable');
        const loader = document.querySelector('.ppmu-filter-loading');
        if (wrap) wrap.classList.toggle('is-loading', on);
        if (loader) loader.hidden = !on;
    }

    async function loadDashboard() {
        if (fetchController) fetchController.abort();
        fetchController = new AbortController();
        setLoading(true);

        const params = collectFilterParams();
        const url = cfg.ajaxUrl + '?' + params.toString();

        try {
            const res = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: fetchController.signal,
            });
            if (!res.ok) throw new Error('Failed to load dashboard');
            const data = await res.json();

            document.getElementById('kpiGrid').outerHTML = data.cards_html;
            const countEl = document.getElementById('kpiMainCount');
            if (countEl) countEl.textContent = data.cards_count;
            updatePeriodRange(data.period_description);
            updateCardLinks(data.period_query);
            history.replaceState(null, '', window.location.pathname + (params.toString() ? '?' + params.toString() : ''));
        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
        } finally {
            setLoading(false);
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
        loadDashboard();
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
        togglePeriodControls(currentPeriodType);

        filter.querySelectorAll('.ppmu-period-pills button').forEach(btn => {
            btn.addEventListener('click', () => {
                activatePeriodPill(btn.dataset.periodType || '');
                if (!btn.dataset.periodType) {
                    filter.querySelectorAll('[data-filter]').forEach(el => { el.value = ''; });
                } else {
                    applyDefaultsToForm();
                }
                loadDashboard();
            });
        });

        filter.querySelectorAll('[data-filter]').forEach(el => {
            el.addEventListener('change', () => loadDashboard());
        });

        filter.querySelector('[data-filter-reset]')?.addEventListener('click', resetToDefaults);
    }

    initFilters();
})();
