<div class="ppmu-summary-pi-grid">
    <article class="ppmu-pi-card tone-blue">
        <div class="ppmu-pi-icon"><i class="bi bi-collection-fill"></i></div>
        <div class="ppmu-pi-body">
            <span class="ppmu-pi-label">Total Records</span>
            <strong class="ppmu-pi-value">{{ number_format($summary['total']) }}</strong>
            <small class="ppmu-pi-hint">All submissions in period</small>
        </div>
    </article>
    <article class="ppmu-pi-card tone-green">
        <div class="ppmu-pi-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="ppmu-pi-body">
            <span class="ppmu-pi-label">Approved</span>
            <strong class="ppmu-pi-value">{{ number_format($summary['approved']) }}</strong>
            <small class="ppmu-pi-hint">Verified and accepted</small>
        </div>
    </article>
    <article class="ppmu-pi-card tone-blue">
        <div class="ppmu-pi-icon"><i class="bi bi-send-fill"></i></div>
        <div class="ppmu-pi-body">
            <span class="ppmu-pi-label">Submitted</span>
            <strong class="ppmu-pi-value">{{ number_format($summary['submitted']) }}</strong>
            <small class="ppmu-pi-hint">Awaiting review</small>
        </div>
    </article>
    <article class="ppmu-pi-card tone-orange">
        <div class="ppmu-pi-icon"><i class="bi bi-hourglass-split"></i></div>
        <div class="ppmu-pi-body">
            <span class="ppmu-pi-label">Pending</span>
            <strong class="ppmu-pi-value">{{ number_format($summary['pending']) }}</strong>
            <small class="ppmu-pi-hint">Outstanding work</small>
        </div>
    </article>
    <article class="ppmu-pi-card tone-red">
        <div class="ppmu-pi-icon"><i class="bi bi-x-circle-fill"></i></div>
        <div class="ppmu-pi-body">
            <span class="ppmu-pi-label">Rejected</span>
            <strong class="ppmu-pi-value">{{ number_format($summary['rejected']) }}</strong>
            <small class="ppmu-pi-hint">Needs correction</small>
        </div>
    </article>
    <article class="ppmu-pi-card tone-purple">
        <div class="ppmu-pi-icon"><i class="bi bi-trophy-fill"></i></div>
        <div class="ppmu-pi-body">
            <span class="ppmu-pi-label">Top Area</span>
            <strong class="ppmu-pi-value ppmu-pi-value-text" title="{{ $summary['best_area'] }}">{{ $summary['best_area'] }}</strong>
            <small class="ppmu-pi-hint">Highest achievement</small>
        </div>
    </article>
</div>
