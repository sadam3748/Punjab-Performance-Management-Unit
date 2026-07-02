(function () {
    'use strict';

    const cfg = window.PPMU_INSPECTIONS || {};
    const form = document.getElementById('inspectionPageFilters');
    const listWrap = document.getElementById('inspectionListWrap');
    const countEl = document.getElementById('inspectionListCount');
    let timer = null;
    let fetchController = null;

    function collectParams(extra = {}) {
        const params = new URLSearchParams();

        if (form) {
            new FormData(form).forEach((value, key) => {
                params.set(key, String(value));
            });
        }

        Object.entries(extra).forEach(([key, value]) => {
            if (key !== 'keepPage' && value !== null && value !== undefined) {
                params.set(key, String(value));
            }
        });

        return params;
    }

    function setLoading(isLoading) {
        listWrap?.classList.toggle('is-loading', isLoading);
        form?.querySelectorAll('select').forEach((select) => {
            select.disabled = isLoading;
        });
    }

    async function refreshList(extra = {}) {
        fetchController?.abort();
        fetchController = new AbortController();

        const params = collectParams(extra);
        const url = `${cfg.ajaxUrl || '/inspections/data'}?${params.toString()}`;

        setLoading(true);

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: fetchController.signal,
            });

            if (!response.ok) {
                throw new Error('Failed to load inspections.');
            }

            const data = await response.json();
            if (listWrap && data.table_html !== undefined) {
                listWrap.innerHTML = data.table_html;
            }
            if (countEl && data.total !== undefined) {
                countEl.textContent = Number(data.total).toLocaleString();
            }

            window.history.replaceState(
                {},
                '',
                `${cfg.indexUrl || '/inspections'}?${params.toString()}`
            );
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
            }
        } finally {
            setLoading(false);
        }
    }

    function scheduleRefresh() {
        clearTimeout(timer);
        timer = setTimeout(() => refreshList({ insp_page: 1 }), 150);
    }

    form?.addEventListener('change', scheduleRefresh);
    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        scheduleRefresh();
    });

    listWrap?.addEventListener('click', (event) => {
        const link = event.target.closest('.pagination a.page-link');
        if (!link || link.closest('.page-item.disabled')) {
            return;
        }

        event.preventDefault();
        const href = new URL(link.href, window.location.origin);
        const page = href.searchParams.get('insp_page') || href.searchParams.get('page') || '1';
        refreshList({ insp_page: page, keepPage: true });
        listWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
})();
