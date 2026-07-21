@props(['observation'])
@php
    $tone = $observation['status_tone'] ?? 'neutral';
    $badge = match($tone) { 'danger' => 'danger', 'warning' => 'warning', 'neutral' => 'secondary', default => 'success' };
    $hasEvidence = (bool) ($observation['has_evidence'] ?? false);
@endphp
<article class="ppmu-detail-observation-card is-{{ $tone }}">
    <div class="ppmu-detail-observation-heading">
        <div class="ppmu-detail-observation-icon" aria-hidden="true"><i class="bi bi-clipboard2-check"></i></div>
        <div>
            <h5>{{ $observation['label'] }}</h5>
        </div>
    </div>
    <span class="badge rounded-pill text-bg-{{ $badge }} ppmu-obs-status-badge">{{ $observation['value'] }}</span>
    <small class="ppmu-detail-observation-evidence">
        Evidence: Picture {{ $observation['picture_number'] }} &middot;
        <span class="{{ $hasEvidence ? 'is-available' : 'is-missing' }}">{{ $hasEvidence ? 'Available' : 'Missing' }}</span>
    </small>
    <div class="ppmu-detail-observation-action">
        @if($hasEvidence)
            <button type="button" class="ppmu-obs-evidence-link"
                    data-bs-toggle="modal" data-bs-target="#ppmuObservationEvidenceModal"
                    data-evidence-url="{{ $observation['evidence_url'] }}"
                    data-evidence-label="{{ $observation['label'] }}"
                    data-observation-key="{{ $observation['observation_key'] ?? '' }}">
                <i class="bi bi-image"></i> View Evidence
            </button>
        @else
            <span class="ppmu-obs-evidence-muted"><i class="bi bi-image"></i> Evidence Missing</span>
        @endif
    </div>
</article>
