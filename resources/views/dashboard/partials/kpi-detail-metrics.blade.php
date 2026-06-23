<div class="ppmu-pi-grid">
    @foreach($metrics as $metric)
        <x-kpi-metric-card
            variant="indicator"
            :label="$metric['label']"
            :value="$metric['value']"
            :icon="$metric['icon'] ?? 'bi-bar-chart'"
            :tone="$metric['tone'] ?? 'blue'"
            :formula="$metric['formula_text'] ?? null"
            :hint="$metric['hint'] ?? null"
            :description="$metric['description'] ?? null"/>
    @endforeach
</div>
