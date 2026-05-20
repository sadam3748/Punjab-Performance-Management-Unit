@foreach(($weekOptions ?? []) as $value => $label)
    <option value="{{ $value }}" @selected((string)($selectedWeekRange ?? '') === (string)$value)>{{ $label }}</option>
@endforeach

