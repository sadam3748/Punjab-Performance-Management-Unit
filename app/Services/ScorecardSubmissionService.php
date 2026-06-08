<?php

namespace App\Services;

use App\Models\District;
use App\Models\DistrictKpiScore;
use App\Models\KpiCategory;
use Illuminate\Support\Facades\DB;

class ScorecardSubmissionService
{
    public function __construct(
        private readonly ScorecardCalculationService $calculator,
        private readonly ScorecardService $scorecardService
    ) {
    }

    public function submit(District $district, KpiCategory $category, array $data): DistrictKpiScore
    {
        return DB::transaction(function () use ($district, $category, $data) {
            $parameters = $category->scoringParameters()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get();

            $submittedDetails = collect($data['details'] ?? [])->keyBy(
                fn (array $detail) => (int) ($detail['kpi_scoring_parameter_id'] ?? 0)
            );

            $missing = $parameters->reject(fn ($parameter) => $submittedDetails->has((int) $parameter->id));
            if ($missing->isNotEmpty()) {
                throw new \InvalidArgumentException(
                    'Missing active sub-KPI inputs: ' . $missing->pluck('parameter_name')->implode(', ')
                );
            }

            $weekNo = (string) $data['week_no'];
            $range = $this->scorecardService->getWeekDateRange($weekNo);
            $start = $range['start'];
            $calculationType = (string) ($data['calculation_type'] ?? 'general');

            $score = DistrictKpiScore::updateOrCreate(
                [
                    'district_id' => $district->id,
                    'kpi_category_id' => $category->id,
                    'period_type' => 'weekly',
                    'week_no' => $weekNo,
                    'month' => $start?->month,
                    'quarter' => $start?->quarter,
                    'year' => (int) substr($weekNo, 0, 4),
                    'calculation_type' => $calculationType,
                ],
                [
                    'division_id' => $district->division_id,
                    'date_from' => $start?->toDateString(),
                    'date_to' => $range['end']?->toDateString(),
                    'is_reported' => true,
                    'is_active' => true,
                    'verified_score' => (float) ($data['verified_score'] ?? 0),
                ]
            );

            $context = [
                ...(array) ($data['context'] ?? []),
                'week_no' => $weekNo,
            ];

            foreach ($parameters as $parameter) {
                $input = $submittedDetails->get((int) $parameter->id);
                $numerator = (float) ($input['numerator'] ?? 0);
                $denominator = array_key_exists('denominator', $input) && $input['denominator'] !== null
                    ? (float) $input['denominator']
                    : null;

                // Resolve the submitted, tier, dynamic, or common target before saving.
                $target = $this->calculator->resolveParameterTarget(
                    $parameter,
                    $district,
                    ['denominator' => $denominator],
                    $context
                );
                if (! in_array($parameter->formula_type, ['yes_no', 'direct_score'], true)
                    && ($target === null || $target <= 0)) {
                    throw new \InvalidArgumentException(
                        "Target value is required for {$parameter->parameter_name} where no tier/dynamic target exists."
                    );
                }

                $this->calculator->saveParameterResult(
                    $score,
                    $parameter,
                    $numerator,
                    $denominator,
                    isset($input['reported_score']) ? (float) $input['reported_score'] : null,
                    $input['evidence'] ?? null,
                    [
                        'submission_context' => $context,
                    ],
                    $context
                );
            }

            $score->details()
                ->whereNotIn('kpi_scoring_parameter_id', $parameters->pluck('id'))
                ->delete();

            return $this->calculator->calculateDistrictKpiFinalScore($score->fresh(['kpiCategory']));
        });
    }
}
