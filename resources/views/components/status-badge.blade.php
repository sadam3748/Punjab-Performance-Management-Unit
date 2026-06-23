@props(['status'])
@php $class = match(strtolower($status)) { 'approved', 'excellent', 'active' => 'success', 'rejected', 'critical', 'inactive' => 'danger', 'submitted', 'good' => 'primary', 'attention', 'needs attention' => 'warning', default => 'warning' }; @endphp
<span {{ $attributes->class(['badge rounded-pill text-bg-'.$class]) }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
