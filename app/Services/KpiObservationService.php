<?php

namespace App\Services;

use App\Models\KpiInspection;
use Illuminate\Support\Str;

class KpiObservationService
{
    /** @return list<array<string, mixed>> */
    public function detailsForInspection(KpiInspection $inspection): array
    {
        $detail = is_array($inspection->detail_data) ? $inspection->detail_data : [];
        $slug = (string) ($inspection->kpiCard?->slug ?? '');
        $stored = $detail['structured_observations'] ?? [];

        if (is_array($stored) && $stored !== []) {
            $configuredLabels = $this->fieldMap($slug);
            return collect($stored)->filter(fn ($row) => is_array($row))->map(function (array $row) use ($inspection, $configuredLabels, $slug): array {
                $key = (string) ($row['key'] ?? Str::slug((string) ($row['label'] ?? 'observation'), '_'));

                $evidenceKey = (string) ($row['evidence_key'] ?? $key);
                $evidence = $inspection->attachments->first(fn ($attachment) => $attachment->observation_key === $evidenceKey);

                return [
                    'key' => $key,
                    'label' => (string) ($configuredLabels[$key] ?? $row['label'] ?? Str::headline($key)),
                    'value' => $this->display($row['value'] ?? '—'),
                    'status' => (string) ($row['status'] ?? $this->statusFor($row['value'] ?? null)),
                    'severity' => $row['severity'] ?? null,
                    'remarks' => (string) ($row['remarks'] ?? '—'),
                    'action_required' => (string) ($row['action_required'] ?? '—'),
                    'action_status' => (string) ($row['action_status'] ?? '—'),
                    'evidence_key' => $evidenceKey,
                    'evidence_anchor' => $evidence ? '#evidence-'.$evidenceKey : null,
                    'evidence_url' => $evidence?->resolvedUrl(),
                    'inspector_id' => $inspection->inspected_by,
                    'observed_at' => $inspection->inspection_datetime,
                    'group' => $this->groupFor($slug, $key, (string) ($configuredLabels[$key] ?? $row['label'] ?? '')),
                ];
            })->values()->all();
        }

        $fieldMap = $this->fieldMap($slug);
        if ($fieldMap === []) {
            $excluded = ['issue_summary', 'key_finding', 'overall_finding', 'inspector_remarks', 'corrective_action', 'responsible_department', 'expected_resolution_date', 'follow_up_required', 'follow_up_date', 'structured_observations'];
            $fieldMap = collect($detail)
                ->filter(fn ($value, $key) => ! in_array($key, $excluded, true) && (is_scalar($value) || is_bool($value)))
                ->take(10)
                ->mapWithKeys(fn ($value, $key) => [(string) $key => Str::headline((string) $key)])
                ->all();
        }

        return collect($fieldMap)
            ->map(function (string $label, string $key) use ($detail, $inspection, $slug): ?array {
                if (! array_key_exists($key, $detail)) {
                    return null;
                }

                $evidence = $inspection->attachments->first(fn ($attachment) => $attachment->observation_key === $key);

                return [
                    'key' => $key,
                    'label' => $label,
                    'value' => $this->display($detail[$key]),
                    'status' => $this->statusForField($key, $detail[$key]),
                    'severity' => null,
                    'remarks' => (string) ($detail['inspector_remarks'] ?? '—'),
                    'action_required' => (string) ($detail['corrective_action'] ?? '—'),
                    'action_status' => (string) ($detail['action_status'] ?? '—'),
                    'evidence_key' => $key,
                    'evidence_anchor' => $evidence ? '#evidence-'.$key : null,
                    'evidence_url' => $evidence?->resolvedUrl(),
                    'inspector_id' => $inspection->inspected_by,
                    'observed_at' => $inspection->inspection_datetime,
                    'group' => $this->groupFor($slug, $key, $label),
                ];
            })->filter()->values()->all();
    }

    /** @return list<array{title: string, observations: list<array<string, mixed>>}> */
    public function groupsForInspection(KpiInspection $inspection): array
    {
        $groups = collect($this->detailsForInspection($inspection))
            ->groupBy(fn (array $observation): string => (string) ($observation['group'] ?? 'General Findings'))
            ->map(fn ($observations, string $title): array => [
                'title' => $title,
                'observations' => $observations->unique('key')->take(4)->values()->all(),
            ])
            ->values()
            ->all();

        foreach ($groups as $index => $group) {
            if (count($group['observations']) !== 1 || count($groups) === 1) {
                continue;
            }

            foreach ($groups as $targetIndex => $target) {
                if ($targetIndex === $index || count($target['observations']) >= 5) {
                    continue;
                }

                $groups[$targetIndex]['observations'][] = $group['observations'][0];
                unset($groups[$index]);
                break;
            }
        }

        return array_values($groups);
    }

    public function keyFinding(KpiInspection $inspection): string
    {
        $detail = is_array($inspection->detail_data) ? $inspection->detail_data : [];
        if (filled($detail['key_finding'] ?? null)) {
            return (string) $detail['key_finding'];
        }

        $negative = collect($this->detailsForInspection($inspection))->filter(
            fn (array $row) => in_array($row['status'], ['Non-Compliant', 'Needs Improvement', 'Fault Identified', 'Action Required', 'Pending Action'], true)
        )->pluck('label')->take(2)->implode(' and ');

        return $negative !== '' ? $negative : 'Compliant / satisfactory';
    }

    public function importantFindingForInspection(KpiInspection $inspection): string
    {
        $detail = is_array($inspection->detail_data) ? $inspection->detail_data : [];

        return match ($inspection->kpiCard?->slug) {
            'price-of-roti' => sprintf(
                'Overpricing: %s; underweight: %s',
                $this->display($detail['over_price'] ?? $detail['observed_price'] ?? 'No'),
                $this->display($detail['under_weight'] ?? $detail['observed_weight_g'] ?? 'No'),
            ),
            'dysfunctional-streetlights' => sprintf(
                '%s faulty, %s repaired',
                $this->display($detail['dysfunctional_lights'] ?? 0),
                $this->display($detail['repaired_lights'] ?? 0),
            ),
            'zebra-crossings' => sprintf(
                '%s; repainting %s',
                $this->display($detail['crossing_status'] ?? 'Condition not recorded'),
                $this->display($detail['repainting_required'] ?? 'not recorded'),
            ),
            'repair-of-small-roads-in-both-urban-and-rural-areas' => sprintf(
                '%s m %s',
                $this->display($detail['length_covered_m'] ?? 0),
                $this->display($detail['completion_status'] ?? $detail['repair_type'] ?? 'work recorded'),
            ),
            default => $this->keyFinding($inspection),
        };
    }

    /** @return array<string, mixed> */
    public function summaryForInspection(KpiInspection $inspection): array
    {
        $detail = is_array($inspection->detail_data) ? $inspection->detail_data : [];

        return [
            'overall_finding' => $detail['overall_finding'] ?? $this->keyFinding($inspection),
            'inspector_remarks' => $detail['inspector_remarks'] ?? '—',
            'corrective_action' => $detail['corrective_action'] ?? '—',
            'responsible_officer' => $detail['responsible_department'] ?? '—',
            'expected_completion_date' => $detail['expected_resolution_date'] ?? $detail['completion_date'] ?? '—',
            'follow_up_required' => $detail['follow_up_required'] ?? 'No',
            'follow_up_date' => $detail['follow_up_date'] ?? '—',
        ];
    }

    /** @return list<array{label: string, value: string}> */
    public function summaryItemsForInspection(KpiInspection $inspection): array
    {
        $summary = $this->summaryForInspection($inspection);
        $followUp = (string) $summary['follow_up_required'];

        if (! in_array((string) $summary['follow_up_date'], ['—', 'â€”', ''], true)) {
            $followUp .= ' · '.$summary['follow_up_date'];
        }

        return collect([
            'Overall Inspection Finding' => $summary['overall_finding'],
            'Inspector Remarks' => $summary['inspector_remarks'],
            'Corrective Action Required' => $summary['corrective_action'],
            'Responsible Department / Officer' => $summary['responsible_officer'],
            'Expected Completion Date' => $summary['expected_completion_date'],
            'Follow-Up Required' => $followUp,
        ])->reject(fn ($value): bool => in_array(trim((string) $value), ['—', 'â€”', '-', ''], true))
            ->map(fn ($value, string $label): array => ['label' => $label, 'value' => (string) $value])
            ->values()
            ->all();
    }

    /** @return list<array{title: string, columns: list<string>, rows: list<array<string, mixed>>}> */
    public function repeatedGroups(KpiInspection $inspection): array
    {
        $detail = is_array($inspection->detail_data) ? $inspection->detail_data : [];
        $commodities = $detail['commodities'] ?? [];
        if (! is_array($commodities) || $commodities === []) {
            return [];
        }

        return [[
            'title' => 'Commodity Observations',
            'columns' => ['Commodity', 'Approved Price', 'Observed Price', 'Difference', 'Status', 'Action'],
            'rows' => collect($commodities)->filter(fn ($row) => is_array($row))->map(fn (array $row) => [
                'Commodity' => $row['commodity'] ?? '—',
                'Approved Price' => $row['approved_price'] ?? '—',
                'Observed Price' => $row['observed_price'] ?? '—',
                'Difference' => $row['difference'] ?? '—',
                'Status' => $row['status'] ?? '—',
                'Action' => $row['action'] ?? '—',
            ])->values()->all(),
        ]];
    }

    /** @return array<string, string> */
    private function fieldMap(string $slug): array
    {
        return match ($slug) {
            'price-of-roti' => ['approved_price' => 'Approved Roti Price', 'observed_price' => 'Observed Roti Price', 'approved_weight_g' => 'Approved Roti Weight', 'observed_weight_g' => 'Observed Roti Weight', 'standard_roti_available' => 'Standard Roti Available', 'price_list_displayed' => 'Price List Displayed', 'violation' => 'Violation Type', 'fine' => 'Fine Amount'],
            'price-of-plain-bakery-bread' => ['product_checked' => 'Product / Brand Checked', 'approved_price' => 'Approved Price', 'observed_price' => 'Observed Price', 'plain_bread_available' => 'Plain Bread Available', 'price_list_displayed' => 'Price List Displayed', 'violation' => 'Violation Type', 'fine' => 'Fine Amount'],
            'price-control-of-essential-commodities' => ['commodity' => 'Commodity', 'approved_price' => 'Government-Approved Price', 'observed_price' => 'Observed Sale Price', 'price_difference' => 'Price Difference', 'availability_status' => 'Availability Status', 'violation' => 'Violation', 'fine' => 'Fine Amount'],
            'dysfunctional-streetlights' => ['total_lights' => 'Total Lights Checked', 'functional_lights' => 'Functional Lights', 'dysfunctional_lights' => 'Faulty Lights', 'repaired_lights' => 'Lights Repaired', 'pending_lights' => 'Lights Pending Repair', 'fault_type' => 'Fault Type', 'dark_spot' => 'Dark Spot Identified', 'safety_risk' => 'Safety Risk'],
            'zebra-crossings' => ['crossing_available' => 'Crossing Available', 'crossing_status' => 'Crossing Condition', 'correct_location' => 'Correct Location', 'warning_sign_available' => 'Warning Sign Available', 'repainting_required' => 'Repainting Required', 'new_crossing_required' => 'New Crossing Required', 'action_taken' => 'Corrective Action Status', 'safety_risk' => 'Safety Risk'],
            'repair-of-small-roads-in-both-urban-and-rural-areas' => ['repair_type' => 'Work Type', 'damage_type' => 'Damage Type', 'length_covered_m' => 'Length Repaired (m)', 'pothole_count' => 'Potholes Repaired', 'lane_marking_done' => 'Lane Marking Status', 'work_quality' => 'Surface Quality', 'safety_arrangements' => 'Safety Arrangements', 'rework_required' => 'Rework Required'],
            default => [],
        };
    }

    private function display(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            return collect($value)->filter(fn ($item) => is_scalar($item))->implode(', ');
        }

        return filled($value) ? (string) $value : '—';
    }

    private function statusFor(mixed $value): string
    {
        $normalized = strtolower(trim($this->display($value)));

        return match (true) {
            in_array($normalized, ['no', 'missing', 'faded', 'non-functional', 'poor', 'over price', 'under weight', 'non-availability'], true) => 'Action Required',
            in_array($normalized, ['pending', 'pending action', 'in progress', 'partial', 'partially functional', 'average'], true) => 'Needs Improvement',
            in_array($normalized, ['n/a', 'not applicable'], true) => 'Not Applicable',
            default => 'Satisfactory',
        };
    }

    private function statusForField(string $key, mixed $value): string
    {
        $normalized = strtolower(trim($this->display($value)));
        $hasIssue = is_numeric($value)
            ? (float) $value > 0
            : ! in_array($normalized, ['', 'no', 'none', '0', 'false', 'not required', 'not applicable', 'n/a'], true);

        if ((str_contains($key, 'fault') || str_contains($key, 'violation') || str_contains($key, 'damage')
            || str_contains($key, 'required') || str_contains($key, 'risk') || str_contains($key, 'unsafe'))
            && $hasIssue) {
            return 'Action Required';
        }

        if (str_contains($key, 'pending') && $hasIssue) {
            return 'Pending Action';
        }

        if ((str_contains($key, 'repaired') || str_contains($key, 'completed') || str_contains($key, 'functional'))
            && in_array($normalized, ['yes', 'completed', 'functional', '1', 'true'], true)) {
            return 'Satisfactory';
        }

        return $this->statusFor($value);
    }

    private function groupFor(string $slug, string $key, string $label): string
    {
        $text = strtolower($key.' '.$label);

        if (in_array($slug, ['price-of-roti', 'price-of-plain-bakery-bread', 'price-control-of-essential-commodities'], true)) {
            return match (true) {
                str_contains($text, 'fine'), str_contains($text, 'action'), str_contains($text, 'seal'), str_contains($text, 'fir') => 'Enforcement Action',
                str_contains($text, 'violation'), str_contains($text, 'price'), str_contains($text, 'weight'), str_contains($text, 'available') => 'Violation Findings',
                default => 'Inspection Findings',
            };
        }

        if ($slug === 'zebra-crossings') {
            return match (true) {
                str_contains($text, 'repaint'), str_contains($text, 'action'), str_contains($text, 'new crossing'), str_contains($text, 'completion') => 'Corrective Action and Resolution',
                str_contains($text, 'warning'), str_contains($text, 'safe'), str_contains($text, 'visibility'), str_contains($text, 'position'), str_contains($text, 'location') => 'Quality and Safety',
                default => 'Field Observations',
            };
        }

        if ($slug === 'repair-of-small-roads-in-both-urban-and-rural-areas') {
            return match (true) {
                str_contains($text, 'quality'), str_contains($text, 'damage'), str_contains($text, 'rework') => 'Quality Findings',
                str_contains($text, 'safety'), str_contains($text, 'status'), str_contains($text, 'completion'), str_contains($text, 'follow') => 'Safety and Completion',
                default => 'Work Details',
            };
        }

        if ($slug === 'dysfunctional-streetlights') {
            return match (true) {
                str_contains($text, 'repair'), str_contains($text, 'pending') => 'Repair Findings',
                str_contains($text, 'safety'), str_contains($text, 'dark'), str_contains($text, 'follow') => 'Safety and Follow-up',
                default => 'Defect Findings',
            };
        }

        if ($slug === 'functional-and-clean-water-filtration-plants') {
            return match (true) {
                str_contains($text, 'filter'), str_contains($text, 'clean') => 'Filter and Cleanliness',
                str_contains($text, 'action'), str_contains($text, 'repair'), str_contains($text, 'follow') => 'Corrective Action',
                default => 'Plant Condition',
            };
        }

        if ($slug === 'covering-of-manholes') {
            return match (true) {
                str_contains($text, 'action'), str_contains($text, 'cover'), str_contains($text, 'resolution') => 'Action and Resolution',
                str_contains($text, 'safety'), str_contains($text, 'net'), str_contains($text, 'risk') => 'Safety Findings',
                default => 'Condition Findings',
            };
        }

        return match (true) {
            str_contains($text, 'action'), str_contains($text, 'repair'), str_contains($text, 'rework'), str_contains($text, 'completion') => 'Corrective Action and Resolution',
            str_contains($text, 'safety'), str_contains($text, 'risk'), str_contains($text, 'warning'), str_contains($text, 'quality') => 'Quality and Safety',
            str_contains($text, 'violation'), str_contains($text, 'fine'), str_contains($text, 'price'), str_contains($text, 'weight') => 'Compliance and Enforcement',
            default => 'Field Observations',
        };
    }
}
