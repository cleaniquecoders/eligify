@props(['variant' => 'primary', 'type' => 'button'])

@php($class = \CleaniqueCoders\Eligify\Support\Theme::classes("btn.$variant"))

@if(($attributes['as'] ?? null) === 'a')
    <a {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
    </button>
@endif
