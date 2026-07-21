<?php

namespace App\Data;

class EducationObservationLabels
{
    public const ATTENTION_HELPER = 'Deficiencies Found';
    /** @return array<string, array{title:string,chart:string,positive:string,negative:string,detail_field:string,evidence:int,group:string}> */
    public static function definitions(): array
    {
        return [
            'observation_school_premises' => self::item('School Premises Condition', 'Premises', 'Satisfactory', 'Unsatisfactory', 'school_premises_condition', 1, 'Premises and Learning Environment'),
            'observation_classroom_cleanliness' => self::item('Classroom Cleanliness and Outlook', 'Classrooms', 'Satisfactory', 'Unsatisfactory', 'classroom_cleanliness', 2, 'Premises and Learning Environment'),
            'observation_teachers_staff' => self::item('Teachers and Staff Presence', 'Staff', 'Present', 'Absent', 'teachers_staff_present', 3, 'Staff and Learning'),
            'observation_teacher_dress' => self::item('Teacher Dress/Gown Compliance', 'Dress/Gown', 'Compliant', 'Non-Compliant', 'teacher_dress_compliance', 4, 'Staff and Learning'),
            'observation_learning_material' => self::item('Learning Material Availability', 'Learning Material', 'Available', 'Unavailable', 'learning_material_available', 5, 'Staff and Learning'),
            'observation_electricity_facilities' => self::item('Electricity and School Facilities', 'Electricity', 'Available/Functional', 'Unavailable/Non-Functional', 'electricity_facilities_functional', 6, 'Facilities and Utilities'),
            'observation_drinking_water' => self::item('Clean Drinking Water', 'Drinking Water', 'Available', 'Unavailable', 'drinking_water_available', 7, 'Facilities and Utilities'),
            'observation_toilets' => self::item('Functional and Clean Toilets', 'Toilets', 'Functional and Clean', 'Non-Functional or Unclean', 'toilets_functional_clean', 8, 'Facilities and Utilities'),
            'observation_boundary_wall' => self::item('Proper Boundary Wall', 'Boundary Wall', 'Available', 'Missing or Damaged', 'boundary_wall_available', 9, 'Facilities and Utilities'),
            'observation_playground' => self::item('Playground Condition', 'Playground', 'Maintained', 'Not Maintained', 'playground_maintained', 10, 'Premises and Learning Environment'),
        ];
    }

    private static function item(string $title, string $chart, string $positive, string $negative, string $field, int $evidence, string $group): array
    {
        return compact('title', 'chart', 'positive', 'negative', 'field', 'evidence', 'group') + ['detail_field' => $field];
    }

    public static function observationMetricFields(): array { return array_keys(self::definitions()); }
    public static function meta(string $key): array
    {
        foreach (self::definitions() as $metric => $definition) {
            if ($key === $metric || $key === $definition['detail_field']) return $definition + ['mode' => 'availability'];
        }
        return ['title' => str($key)->replace('_', ' ')->title()->toString(), 'positive' => 'Positive', 'negative' => 'Negative', 'detail_field' => $key, 'mode' => 'availability'];
    }
    public static function chartCategories(): array { return collect(self::definitions())->mapWithKeys(fn (array $d) => [$d['chart'] => $d['detail_field']])->all(); }
    public static function evidenceKeys(): array { return collect(self::definitions())->pluck('detail_field')->values()->all(); }
    public static function outcome(mixed $value): string
    {
        // Normalize common storage variants before mapping to positive/negative.
        // This is reused by both dashboard aggregation and inspection detail rendering.
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_int($value) || is_float($value)) {
            $value = ((int) $value);
        }

        $value = strtolower(trim((string) $value));
        if ($value === '') return 'not_recorded';
        if (in_array($value, ['n/a', 'na', 'not_applicable'], true)) return 'not_applicable';
        if (in_array($value, ['partial', 'partially_available', 'partially_functional', 'needs_improvement'], true)) return 'partial';
        if (in_array($value, ['available', 'yes', 'true', '1', 'satisfactory', 'functional', 'compliant', 'displayed', 'present', 'maintained', 'positive'], true)) return 'positive';
        return 'negative';
    }
    public static function displayValue(string $key, mixed $value): string
    {
        if (self::outcome($value) === 'not_recorded') return 'Not Recorded';
        if (self::outcome($value) === 'not_applicable') return 'Not Applicable';
        if (self::outcome($value) === 'partial') return str((string) $value)->replace('_', ' ')->title()->toString();
        $meta = self::meta($key);
        return self::outcome($value) === 'positive' ? $meta['positive'] : $meta['negative'];
    }
    public static function isNegativeRawValue(string $key, mixed $value): bool { return self::outcome($value) === 'negative'; }
    public static function isNegativeDisplayValue(string $key, string $value): bool { return $value === self::meta($key)['negative']; }
    public static function studentAttendanceIssue(array $detail): bool { return false; }
}
