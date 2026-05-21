<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Division;
use App\Models\KpiCategory;
use App\Models\KpiMetricValue;
use App\Services\ScorecardService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiMetricValueSeeder extends Seeder
{
    public function run(): void
    {
        // Keep seeding fast: truncate and insert controlled rows.
        DB::table('kpi_metric_values')->truncate();

        $categories = KpiCategory::where('is_active', true)->orderBy('id')->get(['id', 'name', 'slug']);
        $divisions = Division::where('is_active', true)->orderBy('id')->get(['id', 'name']);
        $districts = District::where('is_active', true)->orderBy('id')->get(['id', 'name', 'division_id']);

        $scorecard = app(ScorecardService::class);

        $latest = $scorecard->getLatestCompletedPpmfWeekFilters();
        $latestWeekNo = (string) ($latest['week_no'] ?? '');
        $latestRange = $latestWeekNo ? $scorecard->getWeekDateRange($latestWeekNo) : null;
        $weekStart = $latestRange['start'] ?? now()->copy()->subWeek()->startOfDay();

        // Seed 6 completed weekly snapshots (Thu->Wed) for province + district charts.
        $weeklyStarts = [];
        $cursor = $weekStart->copy()->startOfDay();
        for ($i = 0; $i < 6; $i++) {
            $weeklyStarts[] = $cursor->copy()->subWeeks($i);
        }

        $rows = [];

        foreach ($weeklyStarts as $weekCursor) {
            $dateFrom = $weekCursor->copy()->startOfDay();
            $dateTo = $weekCursor->copy()->addDays(6)->endOfDay();
            $year = (int) $dateFrom->format('Y');
            $month = (int) $dateFrom->format('n');
            $quarter = (int) ceil($month / 3);
            $weekNo = sprintf('%d%02d', (int) $dateFrom->isoFormat('GGGG'), (int) $dateFrom->isoWeek());

            foreach ($categories as $category) {
                $defs = $this->metricDefinitionsForCategory($category->slug, $category->name);

                // Province-level cards (all metric keys for the category)
                $sort = 1;
                $baseScale = mt_rand(92, 110) / 100;
                $provinceValues = [];
                foreach ($defs as $def) {
                    $val = $this->valueForProvinceMetric($category->slug, $def['key'], $baseScale);
                    $provinceValues[$def['key']] = $val;

                    $rows[] = [
                        'kpi_category_id' => $category->id,
                        'metric_key'      => $def['key'],
                        'metric_title'    => $def['title'],
                        'metric_value'    => $val,
                        'metric_score'    => null,
                        'metric_unit'     => $def['unit'] ?? null,
                        'area_level'      => 'province',
                        'division_id'     => null,
                        'district_id'     => null,
                        'tehsil_id'       => null,
                        'period_type'     => 'weekly',
                        'year'            => $year,
                        'week_no'         => $weekNo,
                        'month'           => $month,
                        'quarter'         => $quarter,
                        'date_from'       => $dateFrom->toDateString(),
                        'date_to'         => $dateTo->toDateString(),
                        'sort_order'      => $sort++,
                        'is_active'       => true,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }

                // District-level chart values:
                // - For all categories: seed one "primary" metric (keeps total rows reasonable).
                // - For Water Filtration: also seed functional/non_functional so special charts work.
                $districtMetricKeys = [$defs[0]['key']]; // first metric is primary
                if ($this->isWaterCategory($category->slug, $category->name)) {
                    $extra = ['functional', 'non_functional', 'ro_filter_changed', 'ro_filter_unchanged'];
                    foreach ($extra as $k) {
                        if (in_array($k, array_column($defs, 'key'), true)) {
                            $districtMetricKeys[] = $k;
                        }
                    }
                }
                $districtMetricKeys = array_values(array_unique($districtMetricKeys));

                foreach ($districts as $district) {
                    foreach ($districtMetricKeys as $metricKey) {
                        $pBase = (float) ($provinceValues[$metricKey] ?? 0);
                        $val = $this->valueForDistrictMetric($category->slug, $metricKey, $pBase, (int) $district->id);
                        $score = $this->scoreFromValue($pBase, $val);

                        $rows[] = [
                            'kpi_category_id' => $category->id,
                            'metric_key'      => $metricKey,
                            'metric_title'    => $this->titleForKey($defs, $metricKey),
                            'metric_value'    => $val,
                            'metric_score'    => $score,
                            'metric_unit'     => $this->unitForKey($defs, $metricKey),
                            'area_level'      => 'district',
                            'division_id'     => $district->division_id,
                            'district_id'     => $district->id,
                            'tehsil_id'       => null,
                            'period_type'     => 'weekly',
                            'year'            => $year,
                            'week_no'         => $weekNo,
                            'month'           => $month,
                            'quarter'         => $quarter,
                            'date_from'       => $dateFrom->toDateString(),
                            'date_to'         => $dateTo->toDateString(),
                            'sort_order'      => 1,
                            'is_active'       => true,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ];
                    }
                }
            }
        }

        // Division-level summary for latest week only (keeps volume small)
        if (! empty($weeklyStarts)) {
            $latestStart = $weeklyStarts[0];
            $dateFrom = $latestStart->copy()->startOfDay();
            $dateTo = $latestStart->copy()->addDays(6)->endOfDay();
            $year = (int) $dateFrom->format('Y');
            $month = (int) $dateFrom->format('n');
            $quarter = (int) ceil($month / 3);
            $weekNo = sprintf('%d%02d', (int) $dateFrom->isoFormat('GGGG'), (int) $dateFrom->isoWeek());

            foreach ($categories as $category) {
                $defs = $this->metricDefinitionsForCategory($category->slug, $category->name);
                $primaryKey = $defs[0]['key'];

                foreach ($divisions as $division) {
                    $districtIds = $districts->where('division_id', $division->id)->pluck('id')->all();
                    if (! count($districtIds)) {
                        continue;
                    }

                    $divisionTotal = KpiMetricValue::query()
                        ->where('area_level', 'district')
                        ->where('kpi_category_id', $category->id)
                        ->where('period_type', 'weekly')
                        ->where('week_no', $weekNo)
                        ->where('metric_key', $primaryKey)
                        ->whereIn('district_id', $districtIds)
                        ->sum('metric_value');

                    $rows[] = [
                        'kpi_category_id' => $category->id,
                        'metric_key'      => $primaryKey,
                        'metric_title'    => $defs[0]['title'],
                        'metric_value'    => round((float) $divisionTotal, 2),
                        'metric_score'    => null,
                        'metric_unit'     => $defs[0]['unit'] ?? null,
                        'area_level'      => 'division',
                        'division_id'     => $division->id,
                        'district_id'     => null,
                        'tehsil_id'       => null,
                        'period_type'     => 'weekly',
                        'year'            => $year,
                        'week_no'         => $weekNo,
                        'month'           => $month,
                        'quarter'         => $quarter,
                        'date_from'       => $dateFrom->toDateString(),
                        'date_to'         => $dateTo->toDateString(),
                        'sort_order'      => 1,
                        'is_active'       => true,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 1500) as $chunk) {
            DB::table('kpi_metric_values')->insert($chunk);
        }
    }

    private function isWaterCategory(?string $slug, string $name): bool
    {
        $slug = (string) $slug;
        return str_contains($slug, 'water') || str_contains(strtolower($name), 'water filtration');
    }

    private function metricDefinitionsForCategory(?string $slug, string $name): array
    {
        $slug = (string) $slug;

        if ($this->isWaterCategory($slug, $name)) {
            return [
                ['key' => 'total_plants', 'title' => 'Total Water Filtration Plants', 'unit' => 'count'],
                ['key' => 'inspected', 'title' => 'Inspected', 'unit' => 'count'],
                ['key' => 'not_inspected', 'title' => 'Not Inspected', 'unit' => 'count'],
                ['key' => 'functional', 'title' => 'Functional', 'unit' => 'count'],
                ['key' => 'non_functional', 'title' => 'Non-Functional', 'unit' => 'count'],
                ['key' => 'cleaned', 'title' => 'Cleaned', 'unit' => 'count'],
                ['key' => 'uncleaned', 'title' => 'Un-cleaned', 'unit' => 'count'],
                ['key' => 'ro_filter_changed', 'title' => 'RO Filter Changed', 'unit' => 'count'],
                ['key' => 'ro_filter_unchanged', 'title' => 'RO Filter Unchanged', 'unit' => 'count'],
            ];
        }

        if (str_contains($slug, 'marriage')) {
            return [
                ['key' => 'total_inspections', 'title' => 'Total Inspections', 'unit' => 'inspections'],
                ['key' => 'compliant', 'title' => 'Compliant', 'unit' => 'count'],
                ['key' => 'violations_found', 'title' => 'Violations Found', 'unit' => 'count'],
                ['key' => 'notices_issued', 'title' => 'Notices Issued', 'unit' => 'actions'],
                ['key' => 'fines_imposed', 'title' => 'Fines Imposed', 'unit' => 'actions'],
            ];
        }

        if (str_contains($slug, 'essential') || str_contains($slug, 'price-control') || str_contains($slug, 'price-control-of-essential')) {
            return [
                ['key' => 'shops_inspected', 'title' => 'Shops Inspected', 'unit' => 'inspections'],
                ['key' => 'price_list_displayed', 'title' => 'Price List Displayed', 'unit' => 'count'],
                ['key' => 'overcharging_found', 'title' => 'Overcharging Found', 'unit' => 'count'],
                ['key' => 'fines_imposed', 'title' => 'Fines Imposed', 'unit' => 'actions'],
                ['key' => 'compliant_shops', 'title' => 'Compliant Shops', 'unit' => 'count'],
            ];
        }

        if (str_contains($slug, 'manhole')) {
            return [
                ['key' => 'total_locations', 'title' => 'Total Locations', 'unit' => 'count'],
                ['key' => 'covered', 'title' => 'Covered', 'unit' => 'count'],
                ['key' => 'uncovered', 'title' => 'Uncovered', 'unit' => 'count'],
                ['key' => 'high_risk', 'title' => 'High Risk', 'unit' => 'count'],
                ['key' => 'resolved', 'title' => 'Resolved', 'unit' => 'count'],
            ];
        }

        if (str_contains($slug, 'stray-dog')) {
            return [
                ['key' => 'areas_inspected', 'title' => 'Areas Inspected', 'unit' => 'inspections'],
                ['key' => 'complaints_verified', 'title' => 'Complaints Verified', 'unit' => 'count'],
                ['key' => 'dogs_spotted', 'title' => 'Dogs Spotted', 'unit' => 'count'],
                ['key' => 'response_completed', 'title' => 'Response Completed', 'unit' => 'count'],
                ['key' => 'follow_up_required', 'title' => 'Follow-up Required', 'unit' => 'count'],
            ];
        }

        // Generic KPI category metrics (works for cards + district comparison charts)
        return [
            ['key' => 'total_records', 'title' => 'Total Records', 'unit' => 'count'],
            ['key' => 'inspected', 'title' => 'Inspected', 'unit' => 'inspections'],
            ['key' => 'compliant', 'title' => 'Compliant', 'unit' => 'count'],
            ['key' => 'non_compliant', 'title' => 'Non-Compliant', 'unit' => 'count'],
            ['key' => 'resolved', 'title' => 'Resolved', 'unit' => 'count'],
        ];
    }

    private function titleForKey(array $defs, string $key): string
    {
        foreach ($defs as $d) {
            if (($d['key'] ?? '') === $key) return (string) ($d['title'] ?? $key);
        }
        return $key;
    }

    private function unitForKey(array $defs, string $key): ?string
    {
        foreach ($defs as $d) {
            if (($d['key'] ?? '') === $key) return $d['unit'] ?? null;
        }
        return null;
    }

    private function valueForProvinceMetric(string $categorySlug, string $metricKey, float $scale): float
    {
        // Simple realistic ranges by key; scaled weekly.
        $base = match ($metricKey) {
            'total_plants' => mt_rand(4200, 6500),
            'inspected' => mt_rand(2800, 5200),
            'not_inspected' => mt_rand(400, 1800),
            'functional' => mt_rand(2500, 5000),
            'non_functional' => mt_rand(80, 450),
            'cleaned' => mt_rand(1800, 4500),
            'uncleaned' => mt_rand(100, 900),
            'ro_filter_changed' => mt_rand(120, 900),
            'ro_filter_unchanged' => mt_rand(200, 1400),

            'total_inspections' => mt_rand(80, 520),
            'compliant' => mt_rand(40, 420),
            'violations_found' => mt_rand(10, 240),
            'notices_issued' => mt_rand(5, 220),
            'fines_imposed' => mt_rand(0, 180),

            'shops_inspected' => mt_rand(120, 1200),
            'price_list_displayed' => mt_rand(80, 1000),
            'overcharging_found' => mt_rand(10, 260),
            'compliant_shops' => mt_rand(80, 1050),

            'total_locations' => mt_rand(200, 2200),
            'covered' => mt_rand(150, 2000),
            'uncovered' => mt_rand(20, 450),
            'high_risk' => mt_rand(10, 180),
            'resolved' => mt_rand(80, 1800),

            'areas_inspected' => mt_rand(40, 520),
            'complaints_verified' => mt_rand(20, 380),
            'dogs_spotted' => mt_rand(10, 320),
            'response_completed' => mt_rand(10, 320),
            'follow_up_required' => mt_rand(0, 220),

            default => mt_rand(60, 1200),
        };

        $val = round($base * $scale, 2);

        // Keep some logical relations for water category cards.
        if ($this->isWaterCategory($categorySlug, $categorySlug)) {
            // no-op here; relations handled via district generation mostly.
        }

        return max(0, $val);
    }

    private function valueForDistrictMetric(string $categorySlug, string $metricKey, float $provinceBase, int $districtId): float
    {
        $variance = (mt_rand(70, 130) / 100);
        $val = $provinceBase > 0 ? ($provinceBase / 41) * $variance * 12 : mt_rand(5, 60); // scaled per district

        // Add deterministic-ish skew so charts look varied per district.
        $skew = (($districtId % 7) - 3) * 0.06; // -0.18..+0.18
        $val = $val * (1 + $skew);

        // For non_functional keep smaller.
        if (in_array($metricKey, ['non_functional', 'ro_filter_changed', 'uncovered', 'high_risk', 'follow_up_required', 'violations_found', 'overcharging_found'], true)) {
            $val = $val * (mt_rand(20, 60) / 100);
        }

        return round(max(0, $val), 2);
    }

    private function scoreFromValue(float $provinceBase, float $districtValue): ?float
    {
        if ($provinceBase <= 0) {
            return null;
        }

        $ratio = min(1.25, max(0, $districtValue / ($provinceBase / 41)));
        $score = ($ratio * 70) + mt_rand(10, 30);
        return round(min(100, max(0, $score)), 2);
    }
}
