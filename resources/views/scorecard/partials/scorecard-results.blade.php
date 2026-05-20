@php
    $filters = $filters ?? [];
    $areaType = $filters['area_type'] ?? 'district';
@endphp

@if($areaType === 'division')
    @include('scorecard.partials.division-results', ['summary' => $summary, 'divisionRanking' => $divisionRanking, 'filters' => $filters])
@else
    @include('scorecard.partials.district-results', ['summary' => $summary, 'districtRanking' => $districtRanking, 'categoryRanking' => $categoryRanking, 'filters' => $filters])
@endif

