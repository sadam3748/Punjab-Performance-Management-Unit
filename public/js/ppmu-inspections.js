(function () {
    'use strict';

    const cfg = window.PPMU_INSPECTIONS || {};
    const form = document.getElementById('inspectionPageFilters');
    const periodFilter = document.getElementById('kpiPeriodFilter');
    const geoFilter = document.getElementById('kpiGeoFilter');
    const listWrap = document.getElementById('inspectionListWrap');
    const countEl = document.getElementById('inspectionListCount');
    const periodLabel = document.getElementById('inspectionPeriodLabel');
    let timer = null;
    let fetchController = null;
    let currentPeriodType = cfg.defaults?.period_type || 'weekly';

    function applyDefaultsToForm() {
        const defaults = cfg.defaults || {};
        if (!periodFilter) return;

        periodFilter.querySelectorAll('[data-filter]').forEach((el) => {
            const key = el.dataset.filter;
            if (key && defaults[key] !== undefined && defaults[key] !== '') {
                el.value = defaults[key];
            }
        });
    }

    function togglePeriodControls(type) {
        if (!periodFilter) return;

        periodFilter.querySelectorAll('[data-period-control]').forEach((el) => {
            const scopes = (el.dataset.periodControl || '').split(/\s+/);
            el.hidden = type ? !scopes.includes(type) : !scopes.includes('all');
        });
    }

    function activatePeriodPill(type) {
        if (!periodFilter) return;

        periodFilter.querySelectorAll('.ppmu-period-pills button').forEach((btn) => {
            btn.classList.toggle('active', (btn.dataset.periodType || '') === type);
        });
        currentPeriodType = type;
        togglePeriodControls(type);
    }

    function collectParams(extra = {}) {
        const params = new URLSearchParams();

        Object.entries(extra).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                params.set(key, String(value));
            }
        });

        if (form) {
            new FormData(form).forEach((value, key) => {
                if (value !== '') params.set(key, value);
            });
        }

        if (periodFilter) {
            const type = currentPeriodType || cfg.defaults?.period_type || 'weekly';
            if (type) {
                params.set('period_type', type);
                periodFilter.querySelectorAll('[data-filter]').forEach((el) => {
                    if (el.hidden || !el.dataset.filter) return;
                    const scopes = (el.dataset.periodControl || '').split(/\s+/);
                    if (!scopes.includes(type)) return;
                    if (el.value !== '') params.set(el.dataset.filter, el.value);
                });
            }
        }

        if (geoFilter) {
            geoFilter.querySelectorAll('[name]').forEach((el) => {
                if (el.name && el.value !== '') params.set(el.name, el.value);
            });
        }

        if (!params.has('insp_per_page') && cfg.defaults?.insp_per_page) {
            params.set('insp_per_page', cfg.defaults.insp_per_page);
        }

        if (!params.has('kpi_card_id') && cfg.defaults?.kpi_card_id) {
            params.set('kpi_card_id', cfg.defaults.kpi_card_id);
        }

        return params;
    }

    function setLoading(on) {
        if (listWrap) listWrap.classList.toggle('is-loading', on);
        const loader = periodFilter?.querySelector('.ppmu-filter-loading');
        if (loader) loader.hidden = !on;
    }

    function updatePaginationMeta(from, to, total) {
        const meta = listWrap?.querySelector('#inspectionPaginationMeta');
        if (!meta) return;
        const safeTotal = Number(total) || 0;
        const safeFrom = Number(from) || 0;
        const safeTo = Number(to) || 0;
        meta.textContent = `Showing ${safeFrom.toLocaleString()}–${safeTo.toLocaleString()} of ${safeTotal.toLocaleString()} inspections`;
    }

    async function refreshList(extra = {}) {
        if (fetchController) fetchController.abort();
        fetchController = new AbortController();

        const params = collectParams(extra);
        const url = (cfg.ajaxUrl || '/inspections/data') + '?' + params.toString();

        setLoading(true);
        try {
            const res = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: fetchController.signal,
            });
            if (!res.ok) throw new Error('Failed to load inspections');

            const data = await res.json();
            if (listWrap && data.table_html) {
                listWrap.innerHTML = data.table_html;
            }
            if (countEl && data.total !== undefined) {
                countEl.textContent = Number(data.total).toLocaleString();
            }
            if (periodLabel && data.period_description) {
                periodLabel.innerHTML = '<i class="bi bi-calendar3"></i>' + data.period_description;
            }
            const rangeLabel = periodFilter?.querySelector('#kpiPeriodRangeLabel span, .ppmu-period-range span');
            if (rangeLabel && data.period_description) {
                rangeLabel.textContent = data.period_description;
            }
            updatePaginationMeta(data.from, data.to, data.total);

            const indexUrl = (cfg.indexUrl || '/inspections') + '?' + params.toString();
            window.history.replaceState({}, '', indexUrl);
        } catch (error) {
            if (error.name !== 'AbortError') console.error(error);
        } finally {
            setLoading(false);
        }
    }

    function scheduleRefresh(extra = {}) {
        clearTimeout(timer);
        if (extra.insp_page === undefined && !extra.keepPage) {
            extra.insp_page = '1';
        }
        timer = setTimeout(() => refreshList(extra), 200);
    }

    function bindAutoRefresh(root, extra = {}) {
        if (!root) return;
        root.querySelectorAll('select, input[type="date"]').forEach((el) => {
            el.addEventListener('change', () => scheduleRefresh(extra));
        });
    }

    function initPeriodFilter() {
        if (!periodFilter) return;

        activatePeriodPill(cfg.defaults?.period_type || 'weekly');
        applyDefaultsToForm();

        periodFilter.querySelectorAll('.ppmu-period-pills button').forEach((btn) => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.periodType || '';
                activatePeriodPill(type);
                if (!type) {
                    periodFilter.querySelectorAll('[data-filter]').forEach((el) => {
                        el.value = '';
                    });
                } else {
                    applyDefaultsToForm();
                }
                scheduleRefresh({ insp_page: '1' });
            });
        });

        periodFilter.querySelectorAll('[data-filter]').forEach((el) => {
            el.addEventListener('change', () => scheduleRefresh({ insp_page: '1' }));
        });

        periodFilter.querySelector('[data-filter-reset]')?.addEventListener('click', () => {
            activatePeriodPill(cfg.defaults?.period_type || 'weekly');
            applyDefaultsToForm();
            scheduleRefresh({ insp_page: '1' });
        });
    }

    function initPagination() {
        listWrap?.addEventListener('click', (event) => {
            const link = event.target.closest('.pagination a.page-link');
            if (!link || link.closest('.page-item.disabled')) return;
            event.preventDefault();

            const href = new URL(link.href, window.location.origin);
            const page = href.searchParams.get('insp_page') || href.searchParams.get('page') || '1';
            refreshList({ insp_page: page, keepPage: true });
            listWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    geoFilter?.addEventListener('submit', (event) => {
        event.preventDefault();
        scheduleRefresh({ insp_page: '1' });
    });

    bindAutoRefresh(form);
    bindAutoRefresh(geoFilter);
    initPeriodFilter();
    initPagination();
})();
