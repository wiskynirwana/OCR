@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-pine']) }}>
        {{ $status }}
    </div>
@endif
