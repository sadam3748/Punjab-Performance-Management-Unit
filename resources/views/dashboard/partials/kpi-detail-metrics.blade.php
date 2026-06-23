<div class="ppmu-pi-grid">
    @foreach($metrics as $metric)
        <x-kpi-metric-card
            variant="indicator"
            :label="$metric['label']"
            :value="$metric['value']"
            :icon="$metric['icon'] ?? 'bi-bar-chart'"
            :tone="$metric['tone'] ?? 'blue'"
            :hint="$metric['hint'] ?? null"/>
    @endforeach
</div>
