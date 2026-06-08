@php
    $paginator = $paginator ?? $reportData ?? null;
    $label = $label ?? 'report records';
@endphp

@if($paginator && method_exists($paginator, 'lastPage') && $paginator->lastPage() > 1)
    @php
        $paginator->appends(request()->query());

        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);

        if ($currentPage <= 3) {
            $endPage = min($lastPage, 5);
        }

        if ($currentPage >= $lastPage - 2) {
            $startPage = max(1, $lastPage - 4);
        }
    @endphp

    <div class="report-pagination-bar">
        <div class="report-pagination-summary-group">
            <div class="report-pagination-summary">
                Showing {{ number_format($paginator->firstItem()) }} to {{ number_format($paginator->lastItem()) }}
                of {{ number_format($paginator->total()) }} {{ $label }}
            </div>
        </div>

        <nav class="report-pagination-nav" aria-label="Report pagination">
            <a
                href="{{ $paginator->previousPageUrl() ?: 'javascript:void(0)' }}"
                class="report-page-link {{ $paginator->onFirstPage() ? 'disabled' : '' }}"
            >
                <i class="bi bi-chevron-left"></i>
                Previous
            </a>

            @if($startPage > 1)
                <a href="{{ $paginator->url(1) }}" class="report-page-number">1</a>
                @if($startPage > 2)
                    <span class="report-page-dots">...</span>
                @endif
            @endif

            @for($page = $startPage; $page <= $endPage; $page++)
                <a
                    href="{{ $paginator->url($page) }}"
                    class="report-page-number {{ $page == $currentPage ? 'active' : '' }}"
                >
                    {{ $page }}
                </a>
            @endfor

            @if($endPage < $lastPage)
                @if($endPage < $lastPage - 1)
                    <span class="report-page-dots">...</span>
                @endif
                <a href="{{ $paginator->url($lastPage) }}" class="report-page-number">{{ $lastPage }}</a>
            @endif

            <a
                href="{{ $paginator->nextPageUrl() ?: 'javascript:void(0)' }}"
                class="report-page-link {{ $paginator->hasMorePages() ? '' : 'disabled' }}"
            >
                Next
                <i class="bi bi-chevron-right"></i>
            </a>
        </nav>
    </div>
@endif

@once
    @push('styles')
        <style>
            .report-pagination-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                padding: 14px 18px;
                border-top: 1px solid #e2e8f0;
                background: #ffffff;
            }

            .report-pagination-summary-group {
                display: flex;
                flex-direction: column;
                gap: 3px;
                min-width: 210px;
            }

            .report-pagination-summary {
                color: #334155;
                font-size: 13px;
                font-weight: 850;
                white-space: nowrap;
            }

            .report-pagination-nav {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 6px;
                flex-wrap: wrap;
            }

            .report-page-link,
            .report-page-number,
            .report-page-dots {
                min-width: 38px;
                height: 36px;
                padding: 0 12px;
                border-radius: 11px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #14532d;
                font-size: 13px;
                font-weight: 900;
                text-decoration: none;
                line-height: 1;
                white-space: nowrap;
                box-shadow: none;
            }

            .report-page-link {
                gap: 6px;
            }

            .report-page-link:hover,
            .report-page-number:hover {
                background: #ecfdf3;
                color: #14532d;
                border-color: #86efac;
            }

            .report-page-number.active {
                background: #166534;
                color: #ffffff;
                border-color: #166534;
                box-shadow: 0 8px 18px rgba(22, 101, 52, 0.22);
            }

            .report-page-link.disabled {
                pointer-events: none;
                color: #94a3b8;
                background: #f1f5f9;
                border-color: #e2e8f0;
                box-shadow: none;
            }

            .report-page-dots {
                border-color: transparent;
                background: transparent;
                color: #64748b;
                min-width: 24px;
                padding: 0 4px;
            }

            @media (max-width: 991px) {
                .report-pagination-bar {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .report-pagination-summary {
                    white-space: normal;
                }

                .report-pagination-summary-group {
                    min-width: 0;
                }

                .report-pagination-nav {
                    justify-content: flex-start;
                    width: 100%;
                }
            }

            @media (max-width: 767px) {
                .report-pagination-bar {
                    padding: 14px;
                }

                .report-page-link,
                .report-page-number,
                .report-page-dots {
                    min-width: 34px;
                    height: 34px;
                    padding: 0 10px;
                    font-size: 12px;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                function isReportUrl(url) {
                    return url && url.indexOf('/portal/') !== -1 && url.indexOf('report') !== -1;
                }

                async function loadReport(url) {
                    const content = document.querySelector('.ppmf-content');
                    if (!content || !isReportUrl(url)) {
                        window.location.href = url;
                        return;
                    }

                    content.style.opacity = '0.6';

                    try {
                        const response = await fetch(url, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const html = await response.text();
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const fresh = doc.querySelector('.ppmf-content');

                        if (!fresh) {
                            window.location.href = url;
                            return;
                        }

                        content.innerHTML = fresh.innerHTML;
                        window.history.pushState({}, '', url);
                    } catch (e) {
                        window.location.href = url;
                    } finally {
                        const current = document.querySelector('.ppmf-content');
                        if (current) current.style.opacity = '1';
                    }
                }

                document.addEventListener('submit', function (event) {
                    const form = event.target.closest('.ppmf-content form[method="GET"]');
                    if (!form || !isReportUrl(form.action)) return;

                    event.preventDefault();
                    const params = new URLSearchParams(new FormData(form));
                    loadReport(form.action + (params.toString() ? '?' + params.toString() : ''));
                });

                document.addEventListener('click', function (event) {
                    const link = event.target.closest('.report-pagination-nav a[href]');
                    if (!link || link.classList.contains('disabled')) return;

                    const href = link.getAttribute('href');
                    if (!href || href === 'javascript:void(0)') return;

                    event.preventDefault();
                    loadReport(new URL(href, window.location.origin).toString());
                });
            })();
        </script>
    @endpush
@endonce
