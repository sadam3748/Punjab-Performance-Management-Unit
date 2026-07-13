<?php

namespace App\Data;

class EducationObservationLabels
{
    public const ATTENTION_HELPER = 'Deficiencies Found';

    /** @return list<string> */
    public static function observationMetricFields(): array
    {
        return [
            'observation_cleanliness',
            'observation_teachers_staff',
            'observation_books_learning_material',
            'observation_school_facilities_utilities',
            'observation_drinking_water',
            'observation_student_enrolment',
            'observation_student_attendance',
            'observation_attention_required',
        ];
    }

    /**
     * @return array{title: string, positive: string, negative: string, detail_field: string, mode: 'availability'|'yesno'|'attention'|'attendance', helper?: string}
     */
    public static function meta(string $key): array
    {
        return match ($key) {
            'observation_cleanliness', 'cleanliness' => [
                'title' => 'Cleanliness and General Outlook',
                'positive' => 'Satisfactory',
                'negative' => 'Unsatisfactory',
                'detail_field' => 'cleanliness_available',
                'mode' => 'availability',
            ],
            'cleanliness_available' => [
                'title' => 'Cleanliness and General Outlook',
                'positive' => 'Satisfactory',
                'negative' => 'Unsatisfactory',
                'detail_field' => 'cleanliness_available',
                'mode' => 'availability',
            ],
            'observation_teachers_staff', 'teachers_staff' => [
                'title' => 'Teachers and Staff Attendance',
                'positive' => 'Present',
                'negative' => 'Absent',
                'detail_field' => 'teachers_staff_available',
                'mode' => 'availability',
            ],
            'teachers_staff_available' => [
                'title' => 'Teachers and Staff Attendance',
                'positive' => 'Present',
                'negative' => 'Absent',
                'detail_field' => 'teachers_staff_available',
                'mode' => 'availability',
            ],
            'observation_books_learning_material', 'books_learning_material' => [
                'title' => 'Books and Learning Material',
                'positive' => 'Available',
                'negative' => 'Not Available',
                'detail_field' => 'books_learning_material_available',
                'mode' => 'availability',
            ],
            'books_learning_material_available' => [
                'title' => 'Books and Learning Material',
                'positive' => 'Available',
                'negative' => 'Not Available',
                'detail_field' => 'books_learning_material_available',
                'mode' => 'availability',
            ],
            'observation_school_facilities_utilities', 'school_facilities_utilities' => [
                'title' => 'School Facilities and Utilities',
                'positive' => 'Functional',
                'negative' => 'Non-Functional',
                'detail_field' => 'school_facilities_utilities_available',
                'mode' => 'availability',
            ],
            'school_facilities_utilities_available' => [
                'title' => 'School Facilities and Utilities',
                'positive' => 'Functional',
                'negative' => 'Non-Functional',
                'detail_field' => 'school_facilities_utilities_available',
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
            'observation_student_enrolment', 'student_enrolment' => [
                'title' => 'Student Enrolment Checked',
                'positive' => 'Verified',
                'negative' => 'Not Verified',
                'detail_field' => 'student_enrolment_checked',
                'mode' => 'yesno',
            ],
            'student_enrolment_checked' => [
                'title' => 'Student Enrolment Checked',
                'positive' => 'Verified',
                'negative' => 'Not Verified',
                'detail_field' => 'student_enrolment_checked',
                'mode' => 'yesno',
            ],
            'observation_student_attendance', 'student_attendance' => [
                'title' => 'Student Attendance',
                'positive' => 'Present',
                'negative' => 'Absent',
                'detail_field' => 'students_present',
                'mode' => 'attendance',
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
            'Cleanliness and General Outlook' => 'cleanliness_available',
            'Teachers and Staff Attendance' => 'teachers_staff_available',
            'Books and Learning Material' => 'books_learning_material_available',
            'School Facilities and Utilities' => 'school_facilities_utilities_available',
            'Drinking Water' => 'drinking_water_available',
            'Student Enrolment Checked' => 'student_enrolment_checked',
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
                'yes', 'available', 'verified' => $meta['positive'],
                'no', 'not_available', 'not_verified' => $meta['negative'],
                default => str((string) $value)->replace('_', ' ')->title()->toString(),
            };
        }

        return match ($normalized) {
            'available', 'yes', 'satisfactory', 'functional' => $meta['positive'],
            'not_available', 'no', 'unsatisfactory', 'non_functional' => $meta['negative'],
            default => str((string) $value)->replace('_', ' ')->title()->toString(),
        };
    }

    public static function isNegativeRawValue(string $key, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $normalized = strtolower((string) $value);

        return in_array($normalized, ['not_available', 'no', 'unsatisfactory', 'non_functional', 'not_verified'], true);
    }

    public static function isNegativeDisplayValue(string $key, string $displayValue): bool
    {
        $meta = self::meta($key);

        return $displayValue === $meta['negative'];
    }

    /** @param  array<string, mixed>  $detail */
    public static function studentAttendanceIssue(array $detail): bool
    {
        $enrolled = $detail['students_enrolled'] ?? null;
        $present = $detail['students_present'] ?? null;

        if ($enrolled === null || $enrolled === '' || $present === null || $present === '') {
            return true;
        }

        return (int) $present > (int) $enrolled;
    }

    /** @return list<string> */
    public static function evidenceKeys(): array
    {
        return [
            'overall_condition',
            'attendance_register',
            'empty_classroom',
            'books_learning_material',
            'non_functional_facility',
            'drinking_water_facility',
            'enrolment_register',
        ];
    }
}
