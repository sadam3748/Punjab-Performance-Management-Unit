<?php

namespace App\Data;

class HealthObservationLabels
{
    public const ATTENTION_HELPER = 'Deficiencies Found';

    /** @return list<string> */
    public static function observationMetricFields(): array
    {
        return [
            'observation_deep_cleaning',
            'observation_staff_availability',
            'observation_medicine_flex',
            'observation_testing_equipment',
            'observation_drinking_water',
            'observation_utilities',
            'observation_uhi_compliance',
            'observation_attention_required',
        ];
    }

    /**
     * @return array{title: string, positive: string, negative: string, detail_field: string, mode: 'availability'|'yesno'|'attention', helper?: string}
     */
    public static function meta(string $key): array
    {
        return match ($key) {
            'observation_deep_cleaning', 'deep_cleaning' => [
                'title' => 'Deep Cleaning',
                'positive' => 'Satisfactory',
                'negative' => 'Unsatisfactory',
                'detail_field' => 'deep_cleaning_available',
                'mode' => 'availability',
            ],
            'deep_cleaning_available' => [
                'title' => 'Deep Cleaning',
                'positive' => 'Satisfactory',
                'negative' => 'Unsatisfactory',
                'detail_field' => 'deep_cleaning_available',
                'mode' => 'availability',
            ],
            'observation_staff_availability', 'staff_availability' => [
                'title' => 'Staff Availability',
                'positive' => 'Present',
                'negative' => 'Absent',
                'detail_field' => 'staff_available',
                'mode' => 'availability',
            ],
            'staff_available' => [
                'title' => 'Staff Availability',
                'positive' => 'Present',
                'negative' => 'Absent',
                'detail_field' => 'staff_available',
                'mode' => 'availability',
            ],
            'observation_medicine_flex', 'medicine_flex' => [
                'title' => 'Medicine Availability',
                'positive' => 'Available',
                'negative' => 'Not Available',
                'detail_field' => 'medicine_flex_available',
                'mode' => 'availability',
            ],
            'medicine_flex_available' => [
                'title' => 'Medicine Availability',
                'positive' => 'Available',
                'negative' => 'Not Available',
                'detail_field' => 'medicine_flex_available',
                'mode' => 'availability',
            ],
            'observation_testing_equipment', 'testing_equipment' => [
                'title' => 'Testing Equipment',
                'positive' => 'Available',
                'negative' => 'Not Available',
                'detail_field' => 'testing_equipment_available',
                'mode' => 'availability',
            ],
            'testing_equipment_available' => [
                'title' => 'Testing Equipment',
                'positive' => 'Available',
                'negative' => 'Not Available',
                'detail_field' => 'testing_equipment_available',
                'mode' => 'availability',
            ],
            'observation_drinking_water', 'drinking_water' => [
                'title' => 'Drinking Water',
                'positive' => 'Available',
                'negative' => 'Not Available',
                'detail_field' => 'drinking_water_available',
                'mode' => 'availability',
            ],
            'drinking_water_available' => [
                'title' => 'Drinking Water',
                'positive' => 'Available',
                'negative' => 'Not Available',
                'detail_field' => 'drinking_water_available',
                'mode' => 'availability',
            ],
            'observation_utilities', 'utilities' => [
                'title' => 'Utilities',
                'positive' => 'Functional',
                'negative' => 'Non-Functional',
                'detail_field' => 'utilities_available',
                'mode' => 'availability',
            ],
            'utilities_available' => [
                'title' => 'Utilities',
                'positive' => 'Functional',
                'negative' => 'Non-Functional',
                'detail_field' => 'utilities_available',
                'mode' => 'availability',
            ],
            'observation_uhi_compliance', 'uhi_compliance' => [
                'title' => 'UHI Compliance',
                'positive' => 'Compliant',
                'negative' => 'Non-Compliant',
                'detail_field' => 'uhi_compliance',
                'mode' => 'yesno',
            ],
            'observation_attention_required', 'attention_required' => [
                'title' => 'Observation Issues',
                'positive' => '',
                'negative' => '',
                'detail_field' => 'observation_attention_required',
                'mode' => 'attention',
                'helper' => self::ATTENTION_HELPER,
            ],
            default => [
                'title' => str((string) $key)->replace('_', ' ')->title()->toString(),
                'positive' => 'Available',
                'negative' => 'Not Available',
                'detail_field' => $key,
                'mode' => 'availability',
            ],
        };
    }

    /** @return array<string, string> */
    public static function chartCategories(): array
    {
        return [
            'Deep Cleaning' => 'deep_cleaning_available',
            'Staff Availability' => 'staff_available',
            'Medicine Availability' => 'medicine_flex_available',
            'Testing Equipment' => 'testing_equipment_available',
            'Drinking Water' => 'drinking_water_available',
            'Utilities' => 'utilities_available',
            'UHI Compliance' => 'uhi_compliance',
        ];
    }

    public static function displayValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn (mixed $item): string => (string) $item, $value));
        }

        $meta = self::meta($key);
        $normalized = strtolower((string) $value);

        if ($meta['mode'] === 'yesno') {
            return match ($normalized) {
                'yes', 'available' => $meta['positive'],
                'no', 'not_available' => $meta['negative'],
                default => str((string) $value)->replace('_', ' ')->title()->toString(),
            };
        }

        return match ($normalized) {
            'available', 'yes' => $meta['positive'],
            'not_available', 'no' => $meta['negative'],
            default => str((string) $value)->replace('_', ' ')->title()->toString(),
        };
    }

    public static function isNegativeRawValue(string $key, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $normalized = strtolower((string) $value);

        return in_array($normalized, ['not_available', 'no'], true);
    }

    public static function isNegativeDisplayValue(string $key, string $displayValue): bool
    {
        $meta = self::meta($key);

        return $displayValue === $meta['negative'];
    }

    /** @return list<string> */
    public static function negativeDisplayValues(): array
    {
        $values = [];
        foreach (self::chartCategories() as $title => $field) {
            $meta = self::meta($field);
            $values[] = $meta['negative'];
        }

        return array_values(array_unique($values));
    }
}
