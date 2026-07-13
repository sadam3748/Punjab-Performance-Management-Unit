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

    function clampPercent(value) {
        return Math.max(0, Math.min(100, Number(value) || 0));
    }

    function destroyCharts() {
        Object.keys(charts).forEach(k => { if (charts[k]) { charts[k].destroy(); charts[k] = null; } });
    }

    function chartColors(count) {
        const palette = [G, B, O, R, T, '#7c3aed', '#0f766e', '#b45309'];
        return Array.from({ length: count }, (_, i) => palette[i % palette.length]);
    }

    function ensureChartCanvas(index) {
        const id = 'kpiChart_' + index;
        let canvas = document.getElementById(id);
        if (canvas) return canvas;

        const cards = document.querySelectorAll('#kpiDetailCharts .ppmu-chart-card');
        const card = cards[index];
        const body = card?.querySelector('.card-ppmf-body');
        if (!body) return null;

        body.innerHTML = `<canvas id="${id}"></canvas>`;
        return document.getElementById(id);
    }

    function buildCharts(data) {
        destroyCharts();
        const definitions = data.definitions || cfg.chartDefinitions || [];

        if (definitions.length) {
            definitions.forEach((def, index) => {
                const canvas = ensureChartCanvas(index);
                if (!canvas) return;

                const payload = def.data || {};
                const labels = payload.labels || [];
                const values = payload.values || [];

                if (!labels.length && !values.length && !(payload.datasets || []).length) {
                    const parent = canvas.closest('.card-ppmf-body');
                    if (parent) {
                        parent.innerHTML = '<div class="ppmu-chart-empty"><i class="bi bi-bar-chart"></i><span>No data available for this chart</span></div>';
                    }
                    return;
                }
                const colors = chartColors(Math.max(labels.length, values.length, 1));
                const chartType = def.type === 'grouped_bar' || def.type === 'stacked_bar'
                    ? 'bar'
                    : (def.type === 'donut' ? 'doughnut' : (def.type === 'pie' ? 'pie' : def.type));

                if (chartType === 'bar' && Array.isArray(payload.datasets) && payload.datasets.length) {
                    const isStacked = def.type === 'stacked_bar';
                    const horizontal = def.type === 'grouped_bar' || def.type === 'stacked_bar' || String(def.key || '').includes('observation');
                    const facilitiesInspected = Number(payload.facilities_inspected ?? 0);
                    const categoryLabelPairs = Array.isArray(payload.category_label_pairs) ? payload.category_label_pairs : [];
                    const isObservationAvailabilityChart = ['health_observation_availability', 'education_observation_availability']
                        .includes(String(def.key || ''));
                    const datasets = payload.datasets.map((series, seriesIndex) => ({
                        label: series.label || ('Series ' + (seriesIndex + 1)),
                        data: series.values || [],
                        backgroundColor: series.color || chartColors(payload.datasets.length)[seriesIndex],
                        borderRadius: isStacked
                            ? (seriesIndex === 0
                                ? { topLeft: 4, bottomLeft: 4, topRight: 0, bottomRight: 0 }
                                : { topLeft: 0, bottomLeft: 0, topRight: 4, bottomRight: 4 })
                            : 4,
                        borderSkipped: false,
                        maxBarThickness: 18,
                        stack: isStacked ? 'observations' : undefined,
                    }));
                    const barValueLabels = {
                        id: 'ppmuBarValueLabels',
                        afterDatasetsDraw(chart) {
                            const { ctx, chartArea } = chart;
                            if (!chartArea) return;
                            ctx.save();
                            ctx.font = '600 10px "Plus Jakarta Sans", system-ui, sans-serif';
                            ctx.textBaseline = 'middle';
                            chart.data.datasets.forEach((dataset, datasetIndex) => {
                                const meta = chart.getDatasetMeta(datasetIndex);
                                if (meta.hidden) return;
                                meta.data.forEach((bar, index) => {
                                    const value = Number(dataset.data[index] ?? 0);
                                    if (!value) return;
                                    const label = String(value);
                                    const inside = horizontal
                                        ? (bar.x - chartArea.left) > 28
                                        : (chartArea.bottom - bar.y) > 18;
                                    ctx.fillStyle = inside ? '#fff' : '#334155';
                                    ctx.textAlign = inside ? 'right' : (horizontal ? 'left' : 'center');
                                    if (horizontal) {
                                        ctx.fillText(label, inside ? bar.x - 6 : bar.x + 6, bar.y);
                                    } else {
                                        ctx.fillText(label, bar.x, inside ? bar.y + 4 : bar.y - 8);
                                    }
                                });
                            });
                            ctx.restore();
                        },
                    };

                    charts['kpiChart_' + index] = new Chart(canvas, {
                        type: 'bar',
                        data: { labels, datasets },
                        options: {
                            indexAxis: horizontal ? 'y' : 'x',
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: horizontal ? { left: 6, right: 10 } : { top: 8, bottom: 4 },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    stacked: isStacked,
                                    grid: horizontal ? { display: false } : grid,
                                    ticks: {
                                        font: fnt,
                                        autoSkip: false,
                                        padding: horizontal ? 10 : 6,
                                    },
                                    afterFit(axis) {
                                        if (horizontal) {
                                            axis.width = Math.max(axis.width, 152);
                                        }
                                    },
                                },
                                x: {
                                    beginAtZero: true,
                                    stacked: isStacked,
                                    grid: horizontal ? grid : { display: false },
                                    ticks: { font: fnt, padding: horizontal ? 6 : 4 },
                                },
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { padding: 12, usePointStyle: true, pointStyle: 'circle', font: fnt },
                                },
                                tooltip: {
                                    mode: isStacked ? 'index' : 'nearest',
                                    intersect: !isStacked,
                                    callbacks: {
                                        label(context) {
                                            const value = context.parsed?.x ?? context.parsed?.y ?? 0;
                                            if (isObservationAvailabilityChart && categoryLabelPairs.length) {
                                                const pair = categoryLabelPairs[context.dataIndex] || {};
                                                const seriesLabel = context.datasetIndex === 0
                                                    ? (pair.positive || 'Positive outcome')
                                                    : (pair.negative || 'Negative outcome');
                                                return ` ${seriesLabel}: ${value}`;
                                            }
                                            const series = context.dataset?.label || 'Value';
                                            return ` ${series}: ${value}`;
                                        },
                                        footer(items) {
                                            if (!isStacked || !items?.length) return '';
                                            const available = Number(items[0]?.parsed?.x ?? items[0]?.parsed?.y ?? 0);
                                            const notAvailable = Number(items[1]?.parsed?.x ?? items[1]?.parsed?.y ?? 0);
                                            const total = available + notAvailable;
                                            const inspected = facilitiesInspected > 0 ? facilitiesInspected : total;
                                            return `Total inspected: ${inspected}`;
                                        },
                                    },
                                },
                            },
                        },
                        plugins: [barValueLabels],
                    });
                    return;
                }

                if (chartType === 'gauge') {
                    const value = clampPercent(values[0] ?? 0);
                    charts['kpiChart_' + index] = new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            labels: ['Progress', 'Remaining'],
                            datasets: [{ data: [value, Math.max(0, 100 - value)], backgroundColor: [G, '#e2e8f0'], borderWidth: 0 }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, cutout: '72%',
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed + '%' } }
                            }
                        }
                    });
                    return;
                }

                if (chartType === 'line') {
                    charts['kpiChart_' + index] = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                label: def.title,
                                data: values,
                                borderColor: G,
                                backgroundColor: 'rgba(8,116,67,.08)',
                                fill: true,
                                tension: .38,
                                pointBackgroundColor: G,
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, grid, ticks: { font: fnt } },
                                x: { grid: { display: false }, ticks: { font: fnt, maxRotation: 45, autoSkip: true, maxTicksLimit: 10 } }
                            },
                            plugins: { legend: { display: false } }
                        }
                    });
                    return;
                }

                if (chartType === 'bar') {
                    const horizontal = String(def.key || '').includes('comparison')
                        || String(def.key || '').includes('_progress');
                    charts['kpiChart_' + index] = new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{ data: values, backgroundColor: colors, borderRadius: 6, borderSkipped: false, maxBarThickness: horizontal ? 22 : 48 }]
                        },
                        options: {
                            indexAxis: horizontal ? 'y' : 'x',
                            responsive: true, maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, grid, ticks: { font: fnt } },
                                x: { beginAtZero: true, grid: horizontal ? grid : { display: false }, ticks: { font: fnt } }
                            },
                            plugins: { legend: { display: false } }
                        }
                    });
                    return;
                }

                charts['kpiChart_' + index] = new Chart(canvas, {
                    type: chartType,
                    data: {
                        labels,
                        datasets: [{ data: values, backgroundColor: colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: chartType === 'doughnut' ? '62%' : undefined,
                        plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' } } }
                    }
                });
            });
            return;
        }

        const statusDonut = data.status_donut || {};
        const statusLabels = Object.keys(statusDonut);
        const statusColors = statusLabels.map(l => statusPalette[l] || T);

        const statusCanvas = document.getElementById('statusChart');
        if (statusCanvas) {
            charts.status = new Chart(statusCanvas, {
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
        }
    }

    function updateWeekOptions(filters) {
        if (!filters?.weeks) return;
        const select = document.querySelector('#kpiPeriodFilter [data-filter="week_no"]');
        if (!select) return;
        const current = select.value;
        select.innerHTML = '';
        Object.entries(filters.weeks).forEach(([value, label]) => {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = label;
            if (value === current) opt.selected = true;
            select.appendChild(opt);
        });
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

        const targetLabel = header.labels?.target || stats.dataset.labelTarget || 'Operational Target';
        const completedLabel = header.labels?.completed || stats.dataset.labelCompleted || 'Completed';
        const marks = header.total_marks ?? 0;

        const targetSpan = stats.querySelector('[data-stat="target"] [data-label="target"]');
        const completedSpan = stats.querySelector('[data-stat="achieved"] [data-label="completed"]');
        if (targetSpan) targetSpan.textContent = targetLabel;
        if (completedSpan) completedSpan.textContent = completedLabel;

        stats.querySelector('[data-stat="target"] strong').textContent = fmtNum(header.operational_target ?? header.target, 1);
        stats.querySelector('[data-stat="achieved"] strong').textContent = fmtNum(header.completed ?? header.achieved, 1);
        stats.querySelector('[data-stat="pct"] strong').textContent = clampPercent(header.achievement_percentage) + '%';

        const reviewEl = stats.querySelector('[data-stat="review_pct"] strong');
        if (reviewEl) {
            reviewEl.textContent = clampPercent(header.review_percentage ?? 0) + '%';
        }

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

        form.addEventListener('submit', e => {
            e.preventDefault();
            loadDashboard({ insp_page: '1' });
        });

        form.querySelectorAll('[data-insp-filter]').forEach(el => {
            el.addEventListener('change', () => {
                loadDashboard({ insp_page: '1' });
            });
        });
    }

    function bindGeoFilters() {
        const form = document.getElementById('kpiGeoFilter');
        if (!form || form.dataset.bound) return;
        form.dataset.bound = '1';

        form.addEventListener('submit', e => {
            e.preventDefault();
            loadDashboard({ insp_page: '1' });
        });

        form.querySelectorAll('[data-geo-filter]').forEach(el => {
            el.addEventListener('change', () => {
                loadDashboard({ insp_page: '1' });
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

        document.querySelectorAll('#kpiGeoFilter [data-geo-filter]').forEach(el => {
            if (el.name && el.value) params.set(el.name, el.value);
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
            updateWeekOptions(data.period_filters);
            document.getElementById('kpiDetailMetrics').innerHTML = data.metrics_html;

            if (cfg.isVisitKpiDashboard && (data.visit_map || data.health_map || data.education_map)) {
                updateHealthMapSection(data.visit_map || data.health_map || data.education_map);
            }

            const inspEl = document.getElementById('kpiDetailInspections');
            if (inspEl && data.inspections_html) {
                inspEl.innerHTML = data.inspections_html;
            }

            const recordsEl = document.getElementById('kpiDetailRecords');
            if (recordsEl && data.records_html) {
                recordsEl.innerHTML = data.records_html;
            }

            buildCharts({
                definitions: data.charts.definitions || [],
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

    let healthMapInstance = null;

    function buildHealthMapPin(color, label, statusLabel) {
        const safeLabel = escapeHtml(label || '');
        const safeStatus = escapeHtml(statusLabel || '');
        return L.divIcon({
            className: 'ppmu-health-map-pin-wrap',
            html: `
                <div class="ppmu-health-map-pin ppmu-health-map-pin-${color}" title="${safeStatus} — ${safeLabel}">
                    <span class="ppmu-health-map-pin-ring"></span>
                    <span class="ppmu-health-map-pin-core"><i class="bi bi-geo-alt-fill"></i></span>
                </div>`,
            iconSize: [38, 46],
            iconAnchor: [19, 46],
            popupAnchor: [0, -42],
        });
    }

    function spreadMapPins(pins) {
        const groups = new Map();

        pins.forEach((pin, index) => {
            const key = `${Number(pin.lat).toFixed(5)}:${Number(pin.lng).toFixed(5)}`;
            if (!groups.has(key)) {
                groups.set(key, []);
            }
            groups.get(key).push({ ...pin, _index: index });
        });

        const spread = [];
        groups.forEach((items) => {
            if (items.length === 1) {
                spread.push(items[0]);
                return;
            }

            const radius = 0.0018;
            items.forEach((pin, offset) => {
                const angle = (Math.PI * 2 * offset) / items.length;
                spread.push({
                    ...pin,
                    lat: Number(pin.lat) + Math.cos(angle) * radius,
                    lng: Number(pin.lng) + Math.sin(angle) * radius,
                });
            });
        });

        return spread.sort((a, b) => (a._index ?? 0) - (b._index ?? 0));
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function healthMapPopupHtml(pin) {
        const entityLabel = pin.institution_name ? 'Institution' : 'Facility';
        const entityValue = pin.institution_name || pin.facility_name;
        const studentRows = pin.students_enrolled !== undefined
            ? `
                    <div><dt>Students Enrolled</dt><dd>${escapeHtml(pin.students_enrolled)}</dd></div>
                    <div><dt>Students Present</dt><dd>${escapeHtml(pin.students_present)}</dd></div>`
            : '';

        return `
            <div class="ppmu-health-map-popup">
                <h4>Inspection Information</h4>
                <dl>
                    <div><dt>Inspection ID</dt><dd>${escapeHtml(pin.inspection_id)}</dd></div>
                    <div><dt>Inspection Type</dt><dd>${escapeHtml(pin.inspection_type)}</dd></div>
                    <div><dt>${entityLabel}</dt><dd>${escapeHtml(entityValue)}</dd></div>
                    <div><dt>Date &amp; Time</dt><dd>${escapeHtml(pin.inspection_date)}</dd></div>
                    <div><dt>Tehsil</dt><dd>${escapeHtml(pin.tehsil)}</dd></div>
                    <div><dt>Address</dt><dd>${escapeHtml(pin.address)}</dd></div>
                    <div><dt>Status</dt><dd><span class="ppmu-health-map-status ppmu-health-map-status-${escapeHtml(pin.color)}">${escapeHtml(pin.review_status)}</span></dd></div>
                    <div><dt>Observation Issues</dt><dd>${escapeHtml(pin.observation_issues)}</dd></div>${studentRows}
                </dl>
                <a href="${escapeHtml(pin.detail_url)}" class="ppmu-health-map-popup-btn" target="_blank" rel="noopener noreferrer">View Detail</a>
            </div>`;
    }

    function initHealthMap(mapData) {
        const el = document.getElementById('ppmuHealthDashboardMap');
        if (!el || !window.L) return;

        if (healthMapInstance) {
            healthMapInstance.remove();
            healthMapInstance = null;
        }

        const pins = spreadMapPins(Array.isArray(mapData?.pins) ? mapData.pins : []);
        const center = mapData?.center || { lat: 31.1704, lng: 72.7097, zoom: 7 };
        const frameWrap = document.getElementById('ppmuHealthMapFrameWrap');
        const emptyEl = document.getElementById('ppmuHealthMapEmpty');

        healthMapInstance = L.map(el, {
            scrollWheelZoom: true,
            zoomControl: false,
            attributionControl: false,
        });

        L.control.zoom({ position: 'topright' }).addTo(healthMapInstance);
        L.control.attribution({ prefix: false, position: 'bottomright' }).addTo(healthMapInstance);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19,
        }).addTo(healthMapInstance);

        if (!pins.length) {
            healthMapInstance.setView([center.lat, center.lng], center.zoom);
            frameWrap?.classList.add('is-empty');
            if (emptyEl) emptyEl.hidden = false;
            setTimeout(() => healthMapInstance.invalidateSize(), 200);
            return;
        }

        frameWrap?.classList.remove('is-empty');
        if (emptyEl) emptyEl.hidden = true;

        const bounds = [];
        pins.forEach(pin => {
            const marker = L.marker([pin.lat, pin.lng], {
                icon: buildHealthMapPin(pin.color, pin.facility_name, pin.review_status),
                riseOnHover: true,
            })
                .bindPopup(healthMapPopupHtml(pin), { maxWidth: 300, className: 'ppmu-health-map-leaflet-popup' })
                .bindTooltip(`${escapeHtml(pin.review_status)} — ${escapeHtml(pin.facility_name)}`, {
                    direction: 'top',
                    offset: [0, -40],
                    opacity: 0.98,
                    className: `ppmu-health-map-marker-tooltip ppmu-health-map-marker-tooltip-${pin.color}`,
                })
                .addTo(healthMapInstance);
            bounds.push([pin.lat, pin.lng]);
        });

        if (bounds.length === 1) {
            healthMapInstance.setView(bounds[0], 15);
        } else {
            healthMapInstance.fitBounds(bounds, { padding: [56, 56], maxZoom: 14 });
        }

        setTimeout(() => healthMapInstance.invalidateSize(), 200);
    }

    function updateHealthMapSection(mapData) {
        if (!cfg.isVisitKpiDashboard) return;

        const frameWrap = document.getElementById('ppmuHealthMapFrameWrap');
        const emptyEl = document.getElementById('ppmuHealthMapEmpty');
        const pinCount = Number(mapData?.pin_count ?? (mapData?.pins || []).length);
        const scopeLabel = document.querySelector('#ppmuHealthMapScope .ppmu-health-map-scope-label');
        const pinCountEl = document.getElementById('ppmuHealthMapPinCount');

        if (scopeLabel && mapData?.scope_label) {
            scopeLabel.textContent = mapData.scope_label;
        }
        if (pinCountEl) {
            pinCountEl.textContent = `${pinCount} ${pinCount === 1 ? 'inspection mapped' : 'inspections mapped'}`;
        }

        if (frameWrap) {
            frameWrap.classList.toggle('is-empty', pinCount === 0);
        }
        if (emptyEl) {
            emptyEl.hidden = pinCount > 0;
        }

        initHealthMap(mapData || {});
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

    buildCharts({ definitions: cfg.chartDefinitions || cfg.charts?.definitions || [], ...cfg.charts });
    bindInspectionFilters();
    bindGeoFilters();
    initFilters();
    if (cfg.isVisitKpiDashboard) {
        updateHealthMapSection(cfg.visitMap || cfg.healthMap || cfg.educationMap || {});
    }
})();
