@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center h-full border-b-2 border-pine text-sm font-medium text-ink transition'
            : 'inline-flex items-center h-full border-b-2 border-transparent text-sm font-medium text-ink-soft hover:text-ink hover:border-line transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
