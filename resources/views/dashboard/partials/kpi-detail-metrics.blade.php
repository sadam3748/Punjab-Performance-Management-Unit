@php
    $sections = $metricSections ?? [];
    $flat = $metrics ?? [];
@endphp

@if($sections !== [])
    @foreach($sections as $section)
        @php $sectionCount = count($section['metrics']); @endphp
        <div class="ppmu-metric-section">
            <h3 class="ppmu-metric-section-title">{{ $section['title'] }}</h3>
            <div class="ppmu-pi-grid ppmu-pi-grid-section ppmu-pi-grid-count-{{ $sectionCount }}">
                @foreach($section['metrics'] as $metric)
                    <x-kpi-metric-card
                        variant="indicator"
                        :label="$metric['label']"
                        :value="$metric['value']"
                        :icon="$metric['icon'] ?? 'bi-bar-chart'"
                        :tone="$metric['tone'] ?? 'blue'"
                        :formula="$metric['formula_text'] ?? null"
                        :hint="$metric['hint'] ?? null"
                        :description="$metric['description'] ?? null"
                        :unit="$metric['unit'] ?? null"/>
                @endforeach
            </div>
        </div>
    @endforeach
@else
    <div class="ppmu-pi-grid">
        @foreach($flat as $metric)
            <x-kpi-metric-card
                variant="indicator"
                :label="$metric['label']"
                :value="$metric['value']"
                :icon="$metric['icon'] ?? 'bi-bar-chart'"
                :tone="$metric['tone'] ?? 'blue'"
                :formula="$metric['formula_text'] ?? null"
                :hint="$metric['hint'] ?? null"
                :description="$metric['description'] ?? null"
                :unit="$metric['unit'] ?? null"/>
        @endforeach
    </div>
@endif
