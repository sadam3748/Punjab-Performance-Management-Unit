<?php
namespace App\Http\Controllers;

use App\Models\District;
use App\Models\DistrictKpiScore;
use App\Models\Division;
use App\Models\KpiCategory;
use App\Services\ScorecardCalculationService;
use App\Services\ScorecardService;
use App\Services\ScorecardSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScorecardController extends Controller
{
    protected ScorecardService $scorecardService;

    public function __construct(
        ScorecardService $scorecardService,
        protected ScorecardSubmissionService $submissionService,
        protected ScorecardCalculationService $calculator
    )
    {
        $this->scorecardService = $scorecardService;
    }

    public function submissionForm(Request $request, District $district, KpiCategory $kpiCategory)
    {
        $default = $this->scorecardService->getLatestCompletedPpmfWeekFilters();
        $weekNo = (string) $request->input('week_no', $default['week_no'] ?? '');
        $parameters = $kpiCategory->scoringParameters()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
        $contextDefaults = [
            'tehsil_count' => $district->tehsils()->where('is_active', true)->count(),
            'working_days' => 5,
        ];
        $contextFieldsBySlug = [
            'clearance-of-at-least-one-market-per-working-day-in-each-tehsil' => ['working_days'],
            'inspection-of-at-least-one-market-per-working-day-in-each-tehsil' => ['working_days'],
            'inspection-of-at-least-25-educational-institutions-for-zebra-crossings' => ['educational_institutions'],
            'inspection-of-at-least-25-sale-points-for-illegal-lpg-decanting' => ['lpg_sale_points'],
            'action-taken-on-violations-for-at-least-15-of-inspections' => ['inspections_count'],
        ];
        $requiredContextFields = $parameters
            ->flatMap(fn ($parameter) => $contextFieldsBySlug[$parameter->parameter_slug] ?? [])
            ->unique()
            ->values();
        $existingScore = DistrictKpiScore::query()
            ->with('details')
            ->where('district_id', $district->id)
            ->where('kpi_category_id', $kpiCategory->id)
            ->where('period_type', 'weekly')
            ->where('week_no', $weekNo)
            ->where('calculation_type', 'general')
            ->first();
        $existingDetails = $existingScore?->details?->keyBy('kpi_scoring_parameter_id') ?? collect();
        $parameterMeta = $parameters->mapWithKeys(function ($parameter) use ($district, $contextDefaults, $contextFieldsBySlug) {
            $target = $this->calculator->resolveParameterTarget($parameter, $district, [], $contextDefaults);
            $requiredContext = $contextFieldsBySlug[$parameter->parameter_slug] ?? [];
            $isYesNo = $parameter->formula_type === 'yes_no';
            $hasConfiguredTarget = $parameter->target_value !== null
                || $parameter->tier_1_target !== null
                || $target !== null;

            return [(int) $parameter->id => [
                'target' => $target,
                'has_configured_target' => $hasConfiguredTarget,
                'is_yes_no' => $isYesNo,
                'required_context' => $requiredContext,
                'needs_denominator' => ! $isYesNo && ! $hasConfiguredTarget && $requiredContext === [],
            ]];
        });

        return view('scorecard.submission', [
            'district' => $district,
            'kpiCategory' => $kpiCategory,
            'parameters' => $parameters,
            'weekNo' => $weekNo,
            'contextDefaults' => $contextDefaults,
            'requiredContextFields' => $requiredContextFields,
            'parameterMeta' => $parameterMeta,
            'existingDetails' => $existingDetails,
            'existingScore' => $existingScore,
        ]);
    }

    public function storeSubmission(Request $request, District $district, KpiCategory $kpiCategory)
    {
        $validated = $request->validate([
            'week_no' => ['required', 'regex:/^\d{6}$/'],
            'calculation_type' => ['nullable', Rule::in(['general', 'sixty_forty'])],
            'verified_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.kpi_scoring_parameter_id' => ['required', 'integer', 'exists:kpi_scoring_parameters,id'],
            'details.*.numerator' => ['required', 'numeric', 'min:0'],
            'details.*.denominator' => ['nullable', 'numeric', 'gt:0'],
            'details.*.reported_score' => ['nullable', 'numeric', 'min:0'],
            'details.*.evidence' => ['nullable', 'string', 'max:2000'],
            'context' => ['nullable', 'array'],
            'context.tehsil_count' => ['nullable', 'numeric', 'min:0'],
            'context.working_days' => ['nullable', 'numeric', 'min:0'],
            'context.educational_institutions' => ['nullable', 'numeric', 'min:0'],
            'context.lpg_sale_points' => ['nullable', 'numeric', 'min:0'],
            'context.inspections_count' => ['nullable', 'numeric', 'min:0'],
        ], [
            'details.required' => 'Please enter actual value for all sub-KPIs.',
            'details.*.numerator.required' => 'Please enter actual value for all sub-KPIs.',
            'details.*.denominator.gt' => 'Denominator cannot be zero.',
            'details.*.denominator.numeric' => 'Target value must be a valid number.',
            'week_no.regex' => 'Week number must use YYYYWW format.',
        ]);

        try {
            $score = $this->submissionService->submit($district, $kpiCategory, $validated);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['details' => $exception->getMessage()]);
        }

        return redirect()
            ->route('scorecard.district-detail', [
                'district' => $district,
                'week_range' => $score->week_no,
                'kpi_category_id' => $kpiCategory->id,
                'calculation_type' => $score->calculation_type,
            ])
            ->with('success', 'PPT sub-KPI scorecard data submitted and recalculated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Chief Minister Governance Scorecard
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $input = $request->only([
                'scope',
                'period',
                'week_range',
                'month',
                'year',
                'area_type',
                'division_id',
                'district_id',
                'tehsil_id',
                'kpi_category_id',
                'tier',
                'date_from',
                'date_to',
                'performance',
                'per_page',
                'calculation_type',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        $filterData = $this->scorecardService->getFilterData();

        $areaType = (string) ($filters['area_type'] ?? 'district');
        $divisionRanking = $areaType === 'division' ? $this->scorecardService->getDivisionRanking($filters) : null;
        $summary = $areaType === 'division'
            ? $this->scorecardService->getDivisionSummary($filters)
            : $this->scorecardService->getScorecardSummary($filters);
        $districtMapMeta = $this->scorecardService->getDistrictMapMeta($filters);
        $divisionMapMeta = $this->scorecardService->getDivisionMapMeta($filters);
        $districtScores = $districtMapMeta['scores'];
        $districtMapIds = $districtMapMeta['ids'];

        return view('scorecard.index', [
            'summary'         => $summary,
            'districtRanking' => $this->scorecardService->getDistrictRanking($filters),
            'divisionRanking' => $divisionRanking,
            'categoryRanking' => $this->scorecardService->getCategoryRanking($filters),
            'districtScores'  => $districtScores,
            'districtMapIds'  => $districtMapIds,
            'divisionScores'  => $divisionMapMeta['scores'],
            'divisionMapIds'  => $divisionMapMeta['ids'],
            'districtMapRanks'=> $districtMapMeta['ranks'] ?? [],
            'divisionMapRanks'=> $divisionMapMeta['ranks'] ?? [],

            'divisions'       => $filterData['divisions'],
            'districts'       => $filterData['districts'],
            'tehsils'         => $filterData['tehsils'],
            'kpiCategories'   => $filterData['kpiCategories'],

            'weekOptions'     => $this->scorecardService->getWeekRanges(
                (int) ($filters['year'] ?? now()->year),
                (int) ($filters['month'] ?? now()->month)
            ),

            'filters'         => $filters,
        ]);
    }

    public function data(Request $request)
    {
        $input = $request->only([
            'scope',
            'period',
            'week_range',
            'month',
            'year',
            'area_type',
            'division_id',
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'tier',
            'date_from',
            'date_to',
            'performance',
            'per_page',
            'calculation_type',
            'return_url',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        $filterData = $this->scorecardService->getFilterData();

        $areaType = (string) ($filters['area_type'] ?? 'district');
        $summary = $areaType === 'division'
            ? $this->scorecardService->getDivisionSummary($filters)
            : $this->scorecardService->getScorecardSummary($filters);
        $districtRanking = $areaType === 'district' ? $this->scorecardService->getDistrictRanking($filters) : null;
        $divisionRanking = $areaType === 'division' ? $this->scorecardService->getDivisionRanking($filters) : null;
        $categoryRanking = $this->scorecardService->getCategoryRanking($filters);

        $weekOptions = $this->scorecardService->getWeekRanges(
            (int) ($filters['year'] ?? now()->year),
            (int) ($filters['month'] ?? now()->month)
        );

        $districtMapMeta = $this->scorecardService->getDistrictMapMeta($filters);
        $divisionMapMeta = $this->scorecardService->getDivisionMapMeta($filters);

        return response()->json([
            'status' => 'success',
            'filters' => $filters,
            'map' => [
                'scores' => $districtMapMeta['scores'],
                'ids' => $districtMapMeta['ids'],
                'div_scores' => $divisionMapMeta['scores'],
                'div_ids' => $divisionMapMeta['ids'],
                'ranks' => $districtMapMeta['ranks'] ?? [],
                'div_ranks' => $divisionMapMeta['ranks'] ?? [],
            ],
            'html' => [
                'status_cards' => view('scorecard.partials.index-status-cards', compact('summary', 'filters'))->render(),
                'table_panel' => view('scorecard.partials.index-table-panel', compact('districtRanking', 'divisionRanking', 'filters'))->render(),
                'week_options' => view('scorecard.partials.week-options', ['weekOptions' => $weekOptions, 'selectedWeekRange' => (string) ($filters['week_range'] ?? '')])->render(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Chief Minister Governance Scorecard Tier Wise
    |--------------------------------------------------------------------------
    */
    public function tierWise(Request $request)
    {
        $input = $request->only([
                'scope',
                'period',
                'week_range',
                'month',
                'year',
                'area_type',
                'division_id',
                'district_id',
                'tehsil_id',
                'kpi_category_id',
                'tier',
                'date_from',
                'date_to',
                'performance',
                'per_page',
                'calculation_type',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        // Tier-wise page should open Tier 1 by default and keep selected tier in all filters.
        $filters['tier'] = $filters['tier'] ?? '1';
        // Tier-wise is district-only (no division-wise toggle in UI).
        $filters['area_type'] = 'district';

        $filterData = $this->scorecardService->getFilterData();

        $districtMapMeta = $this->scorecardService->getDistrictMapMeta($filters);

        return view('scorecard.tier-wise', [
            'tierSummary'   => $this->scorecardService->getTierSummary($filters),
            'tierRanking'   => $this->scorecardService->getTierDistrictRanking($filters),
            'districtScores' => $districtMapMeta['scores'],
            'districtMapIds' => $districtMapMeta['ids'],
            'districtMapRanks' => $districtMapMeta['ranks'] ?? [],

            'divisions'     => $filterData['divisions'],
            'districts'     => $filterData['districts'],
            'tehsils'       => $filterData['tehsils'],
            'kpiCategories' => $filterData['kpiCategories'],

            'weekOptions'   => $this->scorecardService->getWeekRanges(
                (int) ($filters['year'] ?? now()->year),
                (int) ($filters['month'] ?? now()->month)
            ),

            'filters'       => $filters,
        ]);
    }

    public function tierWiseData(Request $request)
    {
        $input = $request->only([
            'scope',
            'period',
            'week_range',
            'month',
            'year',
            'area_type',
            'division_id',
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'tier',
            'date_from',
            'date_to',
            'performance',
            'per_page',
            'calculation_type',
            'return_url',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        $filters['tier'] = $filters['tier'] ?? '1';
        // Tier-wise is district-only (no division-wise toggle in UI).
        $filters['area_type'] = 'district';

        $tierSummary = $this->scorecardService->getTierSummary($filters);
        $tierRanking = $this->scorecardService->getTierDistrictRanking($filters);
        $districtMapMeta = $this->scorecardService->getDistrictMapMeta($filters);

        $weekOptions = $this->scorecardService->getWeekRanges(
            (int) ($filters['year'] ?? now()->year),
            (int) ($filters['month'] ?? now()->month)
        );

        return response()->json([
            'status' => 'success',
            'filters' => $filters,
            'map' => [
                'scores' => $districtMapMeta['scores'],
                'ids' => $districtMapMeta['ids'],
                'ranks' => $districtMapMeta['ranks'] ?? [],
            ],
            'html' => [
                'results' => view('scorecard.partials.tier-results', compact('tierSummary', 'tierRanking', 'filters'))->render(),
                'week_options' => view('scorecard.partials.week-options', ['weekOptions' => $weekOptions, 'selectedWeekRange' => (string) ($filters['week_range'] ?? '')])->render(),
            ],
        ]);
    }

    public function divisionDetail(Request $request, Division $division)
    {
        $input = $request->only([
            'scope',
            'period',
            'week_range',
            'month',
            'year',
            'area_type',
            'division_id',
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'tier',
            'date_from',
            'date_to',
            'performance',
            'per_page',
            'calculation_type',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        $filters['division_id'] = $division->id;
        $filters['area_type'] = 'district';

        $detail = $this->scorecardService->getDivisionScorecardDetail($division, $filters);

        return view('scorecard.division-detail', [
            'division' => $division,
            'filters' => $filters,
            ...$detail,
        ]);
    }

    public function districtDetail(Request $request, District $district)
    {
        $input = $request->only([
            'scope',
            'period',
            'week_range',
            'month',
            'year',
            'area_type',
            'division_id',
            'district_id',
            'tehsil_id',
            'kpi_category_id',
            'tier',
            'date_from',
            'date_to',
            'performance',
            'per_page',
            'calculation_type',
            'return_url',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        $detail = $this->scorecardService->getDistrictScorecardDetail($district, $filters);

        return view('scorecard.district-detail', [
            'district' => $district,
            'filters' => $filters,
            ...$detail,
        ]);
    }

    public function districtKpiDetails(Request $request, District $district, KpiCategory $kpiCategory)
    {
        $input = $request->only([
            'scope',
            'period',
            'period_type',
            'week_range',
            'week_no',
            'month',
            'year',
            'area_type',
            'division_id',
            'district_id',
            'tehsil_id',
            'tier',
            'date_from',
            'date_to',
            'performance',
            'per_page',
            'detail_per_page',
            'calculation_type',
            'return_url',
        ]);

        $hasAnyFilter = collect($input)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
        $filters = $hasAnyFilter
            ? $this->scorecardService->normalizeFilters($input)
            : $this->scorecardService->getLatestCompletedPpmfWeekFilters();

        $detail = $this->scorecardService->getDistrictKpiSubDetail($district, $kpiCategory, $filters);

        return view('scorecard.kpi-sub-detail', [
            'district' => $district,
            'kpiCategory' => $kpiCategory,
            'filters' => $filters,
            ...$detail,
        ]);
    }
}
