@props(['type' => 'text'])

<input type="{{ $type }}" {{ $attributes->merge(['class' => \CleaniqueCoders\Eligify\Support\Theme::classes('input')]) }}>
