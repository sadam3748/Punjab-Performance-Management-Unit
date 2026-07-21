<?php

namespace App\Data;

class HealthObservationLabels
{
    public const ATTENTION_HELPER = 'Deficiencies Found';
    /** @return array<string, array{title:string,chart:string,positive:string,negative:string,detail_field:string,evidence:int,group:string}> */
    public static function definitions(): array
    {
        return [
            'observation_deep_cleaning' => self::item('Deep Cleaning of Hospital Areas', 'Deep Cleaning', 'Satisfactory', 'Unsatisfactory', 'deep_cleaning_available', 1, 'Cleanliness and Patient Areas'),
            'observation_staff_availability' => self::item('Doctors and Paramedics Availability', 'Doctors and Paramedics', 'Available', 'Unavailable', 'doctors_paramedics_available', 2, 'Human Resources and Medicines'),
            'observation_medicines_availability' => self::item('Medicines Availability', 'Medicines', 'Available', 'Shortage or Unavailable', 'medicines_available', 3, 'Human Resources and Medicines'),
            'observation_medicine_flex' => self::item('Medicine Availability Flex', 'Medicine Flex', 'Displayed', 'Not Displayed', 'medicine_flex_displayed', 4, 'Human Resources and Medicines'),
            'observation_medicine_led' => self::item('Medicine Information LED', 'Medicine LED', 'Functional', 'Non-Functional', 'medicine_led_functional', 5, 'Human Resources and Medicines'),
            'observation_diagnostic_services' => self::item('Diagnostic Services', 'Diagnostic Services', 'Functional', 'Non-Functional', 'diagnostic_services_functional', 6, 'Diagnostic Services and Compliance'),
            'observation_uhi_compliance' => self::item('UHI Compliance', 'UHI Compliance', 'Compliant', 'Non-Compliant', 'uhi_compliance', 7, 'Diagnostic Services and Compliance'),
            'observation_utilities' => self::item('Utilities Availability', 'Utilities', 'Available/Functional', 'Unavailable/Non-Functional', 'utilities_available', 8, 'Utilities and Drinking Water'),
            'observation_drinking_water' => self::item('Clean Drinking Water', 'Drinking Water', 'Available', 'Unavailable', 'drinking_water_available', 9, 'Utilities and Drinking Water'),
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
            if ($key === $metric || $key === $definition['detail_field']) {
                return $definition + ['mode' => 'availability'];
            }
        }
        return ['title' => str($key)->replace('_', ' ')->title()->toString(), 'positive' => 'Positive', 'negative' => 'Negative', 'detail_field' => $key, 'mode' => 'availability'];
    }

    public static function chartCategories(): array
    {
        return collect(self::definitions())->mapWithKeys(fn (array $d) => [$d['chart'] => $d['detail_field']])->all();
    }

    public static function evidenceKeys(): array
    {
        return collect(self::definitions())->pluck('detail_field')->values()->all();
    }

    public static function outcome(mixed $value): string
    {
        // Normalize common storage variants before mapping to positive/negative.
        // Used by both dashboard aggregation and inspection detail display.
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_int($value) || is_float($value)) {
            $value = ((int) $value);
        }

        $value = strtolower(trim((string) $value));
        if ($value === '') return 'not_recorded';
        if (in_array($value, ['n/a', 'na', 'not_applicable'], true)) return 'not_applicable';
        if (in_array($value, ['partial', 'partially_available', 'partially_functional', 'needs_improvement'], true)) return 'partial';
        if (in_array($value, ['available', 'yes', 'true', '1', 'satisfactory', 'functional', 'compliant', 'displayed', 'present', 'positive'], true)) return 'positive';
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
    public static function negativeDisplayValues(): array { return collect(self::definitions())->pluck('negative')->unique()->values()->all(); }
}
