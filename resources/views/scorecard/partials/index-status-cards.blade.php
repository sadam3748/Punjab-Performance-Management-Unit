@php
    $filters = $filters ?? [];
    $selectedPerformance = $filters['performance'] ?? 'all';
    $mainRoute = Route::has('scorecard.index') ? route('scorecard.index') : url()->current();

    $perfHref = function (string $key) use ($mainRoute, $selectedPerformance) {
        $query = request()->query();
        if ($selectedPerformance === $key) {
            unset($query['performance']);
        } else {
            $query['performance'] = $key;
        }
        $query['page'] = 1;
        return $mainRoute . (count($query) ? ('?' . http_build_query($query)) : '');
    };

    $perfCards = $perfCards ?? [
        ['key'=>'excellent','title'=>'Excellent','range'=>'90-100','icon'=>'bi-trophy-fill','class'=>'excellent','count'=>(int)($summary['excellent_count'] ?? 0)],
        ['key'=>'good','title'=>'Good','range'=>'70-89','icon'=>'bi-check-circle-fill','class'=>'good','count'=>(int)($summary['good_count'] ?? 0)],
        ['key'=>'average','title'=>'Average','range'=>'50-69','icon'=>'bi-exclamation-circle-fill','class'=>'average','count'=>(int)($summary['average_count'] ?? 0)],
        ['key'=>'critical','title'=>'Critical','range'=>'< 50','icon'=>'bi-x-octagon-fill','class'=>'critical','count'=>(int)($summary['critical_count'] ?? 0)],
    ];
@endphp

<div class="row g-2">
    @foreach($perfCards as $card)
        <div class="col-6 col-md-3 col-xl-3">
            <a href="{{ $perfHref($card['key']) }}"
               class="sc-perf-card {{ $selectedPerformance===$card['key'] ? 'active' : '' }} text-decoration-none">
                <span class="sc-perf-ico {{ $card['class'] }}"><i class="bi {{ $card['icon'] }}"></i></span>
                <span class="d-block">
                    <span class="sc-perf-title d-block">{{ $card['title'] }}</span>
                    <span class="sc-perf-count d-block">{{ number_format($card['count']) }}</span>
                    <span class="sc-perf-sub d-block">{{ $card['range'] }}</span>
                </span>
            </a>
        </div>
    @endforeach
</div>

