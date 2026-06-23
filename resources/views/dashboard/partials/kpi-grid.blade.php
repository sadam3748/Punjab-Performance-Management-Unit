<div class="ppmu-kpi-grid" id="kpiGrid">
    @forelse($cards as $card)
        <x-kpi-card :card="$card" :period-query="$periodQuery ?? ''" />
    @empty
        <div class="ppmu-empty">
            <i class="bi bi-grid"></i>
            <h4>No KPI cards assigned</h4>
            <p>Contact the Super Admin to configure KPI assignments for your role.</p>
        </div>
    @endforelse
</div>
